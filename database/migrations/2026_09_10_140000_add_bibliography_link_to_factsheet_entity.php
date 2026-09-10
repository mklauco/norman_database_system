<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the PDF hyperlink to the Bibliography factsheet section (#26).
     *
     * The legacy factsheet links the phrase "NORMAN Prioritisation framework
     * for emerging substances" inside the citation to the published manual;
     * ours rendered it as plain text. `FactsheetController::processTextData()`
     * reads `link_text` / `link_url` from the section's `data` JSON, so the
     * only change needed in the database is those two keys on one row.
     *
     * `FactsheetEntitySeeder` carries the same values, but it opens with
     * `FactsheetEntity::truncate()` and must NOT be run on production — it
     * would drop every section configuration and rebuild it, discarding any
     * row edited by hand. Hence this targeted migration.
     *
     * Written to touch only the Bibliography row, only the two new keys, and
     * only when they are absent, so it is safe to re-run and cannot disturb a
     * row someone has since edited.
     */
    private const LINK_TEXT = 'NORMAN Prioritisation framework for emerging substances';

    private const LINK_URL = 'https://www.norman-network.net/sites/default/files/norman_prioritisation_manual_15%20April2013_final_for_website.pdf';

    public function up(): void
    {
        $row = DB::table('factsheet_entities')->where('sort_order', 12)->first();

        if ($row === null) {
            return;
        }

        $data = json_decode((string) $row->data, true);

        if (! is_array($data) || ($data['method_of_presentation'] ?? null) !== 'text') {
            return;
        }

        if (isset($data['link_text'], $data['link_url'])) {
            return;
        }

        $data['link_text'] = self::LINK_TEXT;
        $data['link_url'] = self::LINK_URL;

        DB::table('factsheet_entities')
            ->where('id', $row->id)
            ->update(['data' => json_encode($data), 'updated_at' => now()]);
    }

    public function down(): void
    {
        $row = DB::table('factsheet_entities')->where('sort_order', 12)->first();

        if ($row === null) {
            return;
        }

        $data = json_decode((string) $row->data, true);

        if (! is_array($data)) {
            return;
        }

        unset($data['link_text'], $data['link_url']);

        DB::table('factsheet_entities')
            ->where('id', $row->id)
            ->update(['data' => json_encode($data), 'updated_at' => now()]);
    }
};

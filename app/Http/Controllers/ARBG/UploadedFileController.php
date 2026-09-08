<?php

declare(strict_types=1);

namespace App\Http\Controllers\ARBG;

use App\Http\Controllers\Controller;
use App\Models\Backend\File;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The ARB/G list of uploaded files (issue #28).
 *
 * Lists the data files submitted to the module, which is what the records in
 * arbg_bacteria_main and arbg_gene_main were built from. Distinct from DCT
 * Download, which serves the blank templates a provider fills in.
 *
 * The files themselves are deliberately not offered here, only their
 * provenance. Administrators can still reach them through the backend.
 */
class UploadedFileController extends Controller
{
    private const BACTERIA_ENTITY_ID = 14;

    private const GENE_ENTITY_ID = 15;

    /**
     * The files.id that legacy list numbering starts one above, per entity, so
     * the "#" column keeps showing the legacy list id. Notes such as "=> 24"
     * on a superseded row refer to those numbers.
     *
     * @var array<int, int>
     */
    private const LIST_NUMBER_OFFSET = [
        self::BACTERIA_ENTITY_ID => 2999,
        self::GENE_ENTITY_ID => 3002,
    ];

    public function index(): View
    {
        return view('arbg.files.index', [
            'bacteriaFiles' => $this->filesFor(self::BACTERIA_ENTITY_ID),
            'geneFiles' => $this->filesFor(self::GENE_ENTITY_ID),
        ]);
    }

    /**
     * Soft-deleted rows are included on purpose: legacy lists them too, and a
     * superseded upload is part of the records' provenance.
     *
     * @return Collection<int, array{number: int, name: string|null, uploaded_at: string|null, id_from: int|null, id_to: int|null, records: int|null, matrix: string|null, note: string|null, superseded: bool}>
     */
    private function filesFor(int $databaseEntityId): Collection
    {
        return File::query()
            ->where('database_entity_id', $databaseEntityId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (File $file): array => [
                'number' => $file->id - self::LIST_NUMBER_OFFSET[$databaseEntityId],
                'name' => $file->name,
                'uploaded_at' => $file->uploaded_at?->format('Y-m-d'),
                'id_from' => $file->main_id_from,
                'id_to' => $file->main_id_to,
                'records' => $file->analysis_number,
                'matrix' => $file->list_type,
                'note' => $file->note,
                'superseded' => (bool) $file->is_deleted,
            ]);
    }
}

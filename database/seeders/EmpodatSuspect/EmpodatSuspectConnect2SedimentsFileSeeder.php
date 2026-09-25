<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Models\Backend\File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Point the CONNECT 2 SEDIMENTS `files` row (id=10003) at the v2 source file.
 *
 * SCOPE — deliberately two columns, one row
 * ----------------------------------------
 * Only `original_name` and `file_path` are written. Everything else on the row
 * is left exactly as it is: `main_id_from`, `main_id_to`, `number_of_records`,
 * `name`, `description`, `project_id`, `doi`, `note`, `is_protected`,
 * `uploaded_by`, `uploaded_at`, `is_deleted`, `database_entity_id`.
 *
 * This is why it exists instead of reusing {@see EmpodatSuspectFileSeeder}:
 * that seeder rewrites all eight legacy rows (10001–10008) and resets
 * `uploaded_at` and `is_deleted` on each, which would overwrite curation done
 * in the web UI on files this re-upload has nothing to do with.
 *
 * It also never CREATES a row — the row must already exist. A missing row
 * means this is being run against the wrong database, which is a reason to
 * stop, not to invent a `files` record.
 *
 * Idempotent: running it twice is a no-op the second time.
 */
class EmpodatSuspectConnect2SedimentsFileSeeder extends Seeder
{
    use WithoutModelEvents;

    private const int FILE_ID = 10003;

    private const string ORIGINAL_NAME = 'OK_CONNECT 2_suspect screening results_ng g dry weight_1192 - SEDIMENTS v2.xlsx';

    private const string FILE_PATH = 'empodat_suspect/OK_CONNECT 2_suspect screening results_ng g dry weight_1192 - SEDIMENTS v2.xlsx';

    public function run(): void
    {
        $file = File::find(self::FILE_ID);

        if ($file === null) {
            throw new RuntimeException(
                'ABORTED: files row '.self::FILE_ID.' does not exist. This seeder only repoints an '
                .'existing row and never creates one — check you are connected to the right database.'
            );
        }

        $this->command->info('Before: original_name='.$file->original_name);
        $this->command->info('Before: file_path='.$file->file_path);

        $file->update([
            'original_name' => self::ORIGINAL_NAME,
            'file_path' => self::FILE_PATH,
        ]);

        $this->command->info('After:  original_name='.$file->original_name);
        $this->command->info('After:  file_path='.$file->file_path);
        $this->command->info('File ID '.self::FILE_ID.' repointed to the v2 source. No other column was touched.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsFileSeeder

<?php

namespace Database\Seeders\Hazards;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class HazardsJanusSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetTable = 'hazards_janus';
        $path = base_path('database/seeders/seeds/hazards/hazards_janus.xlsx');
        $now = Carbon::now();
        $startTime = microtime(true);

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");
            return;
        }

        $this->command->info("Starting {$targetTable} seeding...");

        Schema::disableForeignKeyConstraints();

        try {
            DB::table($targetTable)->delete();

            $reader = SimpleExcelReader::create($path)
                ->useDelimiter(',')
                ->headersToSnakeCase(false);

            $reader->getRows()
                ->chunk(self::CHUNK_SIZE)
                ->each(function ($rows, $key) use ($targetTable, $now, $path, $startTime): void {
                    $chunkStartTime = microtime(true);
                    $records = [];

                    foreach ($rows as $row) {
                        $normanId = $this->nullableString($this->rowValue($row, [
                            'NORMAN_ID',
                            'norman_id',
                            'normanId',
                        ]));
                        if ($normanId === null) {
                            continue;
                        }

                        $records[] = [
                            'susdat_substance_id' => $this->nullableInt($this->rowValue($row, ['susdat_substance_id'])),
                            'norman_id' => $normanId,
                            'dtxid' => $this->nullableString($this->rowValue($row, ['dtxid', 'DTXSID'])),
                            'smiles' => $this->nullableString($this->rowValue($row, ['SMILES', 'smiles'])),
                            'p_assessment_class' => $this->nullableString($this->rowValue($row, ['P assessment [class]', 'p_assessment_class', 'p_assessment_[class]'])),
                            'p_assessment_index' => $this->nullableFloat($this->rowValue($row, ['P assessment [index]', 'p_assessment_index', 'p_assessment_[index]'])),
                            'p_reliability' => $this->nullableFloat($this->rowValue($row, ['P reliability', 'p_reliability'])),
                            'p_score' => $this->nullableFloat($this->rowValue($row, ['P score', 'p_score'])),
                            'b_assessment_log_units' => $this->nullableFloat($this->rowValue($row, ['B assessment [log units]', 'b_assessment_log_units', 'b_assessment_[log_units]'])),
                            'b_reliability' => $this->nullableFloat($this->rowValue($row, ['B reliability', 'b_reliability'])),
                            'b_score' => $this->nullableFloat($this->rowValue($row, ['B score', 'b_score'])),
                            't_assessment_mg_l' => $this->nullableFloat($this->rowValue($row, ['T assessment [mg/l]', 't_assessment_mg_l', 't_assessment_[mg/l]'])),
                            't_reliability' => $this->nullableFloat($this->rowValue($row, ['T reliability', 't_reliability'])),
                            't_score' => $this->nullableFloat($this->rowValue($row, ['T score', 't_score'])),
                            'c_assessment' => $this->nullableString($this->rowValue($row, ['C assessment', 'c_assessment'])),
                            'c_reliability' => $this->nullableFloat($this->rowValue($row, ['C reliability', 'c_reliability'])),
                            'c_score' => $this->nullableFloat($this->rowValue($row, ['C score', 'c_score'])),
                            'm_assessment' => $this->nullableString($this->rowValue($row, ['M assessment', 'm_assessment'])),
                            'm_reliability' => $this->nullableFloat($this->rowValue($row, ['M reliability', 'm_reliability'])),
                            'm_score' => $this->nullableFloat($this->rowValue($row, ['M score', 'm_score'])),
                            'r_assessment' => $this->nullableString($this->rowValue($row, ['R assessment', 'r_assessment'])),
                            'r_reliability' => $this->nullableFloat($this->rowValue($row, ['R reliability', 'r_reliability'])),
                            'r_score' => $this->nullableFloat($this->rowValue($row, ['R score', 'r_score'])),
                            'ed_assessment_class' => $this->nullableString($this->rowValue($row, ['ED assessment [class]', 'ed_assessment_class', 'ed_assessment_[class]'])),
                            'ed_assessment_index' => $this->nullableFloat($this->rowValue($row, ['ED assessment [index]', 'ed_assessment_index', 'ed_assessment_[index]'])),
                            'ed_reliability' => $this->nullableFloat($this->rowValue($row, ['ED reliability', 'ed_reliability'])),
                            'ed_score' => $this->nullableFloat($this->rowValue($row, ['ED score', 'ed_score'])),
                            'score_vpvb' => $this->nullableFloat($this->rowValue($row, ['SCORE(vPvB)', 'score_vpvb', 'score(v_pv_b)'])),
                            'score_svhc' => $this->nullableFloat($this->rowValue($row, ['SCORE(SVHC)', 'score_svhc', 'score(svhc)'])),
                            'score_pbt' => $this->nullableFloat($this->rowValue($row, ['SCORE(PBT)', 'score_pbt', 'score(pbt)'])),
                            'remarks' => $this->nullableString($this->rowValue($row, ['Remarks', 'remarks'])),
                            'source_file_name' => basename($path),
                            'imported_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (empty($records)) {
                        return;
                    }

                    DB::table($targetTable)->insert($records);

                    $chunkEndTime = microtime(true);
                    $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                    $totalElapsedTime = round($chunkEndTime - $startTime, 2);

                    $this->command->info("Processed chunk ".($key + 1)." with ".count($records)." JANUS records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
                });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command->info("{$targetTable} seeding completed.");
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function rowValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }
}

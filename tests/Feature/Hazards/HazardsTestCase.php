<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\Susdat\Substance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

/**
 * Base of the hazards feature tests. Every test that touches seed files runs against a
 * throw-away storage path, so the real storage/app/hazards/ is never read or written.
 */
abstract class HazardsTestCase extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected const HAZARDS_TABLES = [
        'hazards_api_runs',
        'hazards_comptox_payloads',
        'hazards_parse_runs',
        'hazards_comptox_detail_records',
        'hazards_comptox_property_records',
        'hazards_comptox_fate_records',
        'hazards_janus',
        'hazards_pikme',
        'hazards_comptox_substance_data',
        'hazards_derivation_selections',
        'hazards_derivation_metadata',
        'hazards_classification_votes',
        'hazards_substance_classifications',
        'hazards_classification_supports',
    ];

    protected ?string $tempStorage = null;

    protected function tearDown(): void
    {
        if ($this->tempStorage !== null && is_dir($this->tempStorage)) {
            File::deleteDirectory($this->tempStorage);
        }

        parent::tearDown();
    }

    protected function useTempStorage(): string
    {
        $this->tempStorage = sys_get_temp_dir().'/hazards_test_'.bin2hex(random_bytes(6));
        mkdir($this->tempStorage.'/app/hazards', 0777, true);
        $this->app->useStoragePath($this->tempStorage);

        return $this->tempStorage.'/app/hazards';
    }

    protected function hazardsDir(): string
    {
        return storage_path('app/hazards');
    }

    protected function makeSubstance(string $code, ?string $dtxid = null): Substance
    {
        return Substance::factory()->create(['code' => $code, 'dtxid' => $dtxid ?? 'DTXSID'.$code]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function janusRow(string $normanId, array $overrides = []): array
    {
        return array_merge([
            'NORMAN_ID' => $normanId,
            'SMILES' => 'CCO',
            'P assessment [class]' => 'vP',
            'P assessment [index]' => 0.9,
            'P reliability' => 0.8,
            'P score' => 0.7,
            'B assessment [log units]' => 3.5,
            'B reliability' => 0.6,
            'B score' => 0.5,
            'T assessment [mg/l]' => 0.01,
            'T reliability' => 0.7,
            'T score' => 0.6,
            'C assessment' => 'Non-carcinogen',
            'C reliability' => 0.9,
            'C score' => 0.1,
            'M assessment' => 'Non-mutagen',
            'M reliability' => 0.9,
            'M score' => 0.1,
            'R assessment' => 'Non-toxicant',
            'R reliability' => 0.9,
            'R score' => 0.1,
            'ED assessment [class]' => 'Low',
            'ED assessment [index]' => 0.2,
            'ED reliability' => 0.8,
            'ED score' => 0.2,
            'SCORE(vPvB)' => 0.3,
            'SCORE(SVHC)' => 0.4,
            'SCORE(PBT)' => 0.5,
            'Remarks' => 'fixture',
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function writeJanus(array $rows): void
    {
        $writer = SimpleExcelWriter::create($this->hazardsDir().'/hazards_janus.xlsx');
        $writer->nameCurrentSheet('FINAL');
        $writer->addHeader(array_keys($rows[0] ?? $this->janusRow('NS0')));
        foreach ($rows as $row) {
            $writer->addRow($row);
        }
        $writer->close();
    }

    /**
     * @return array<string, mixed>
     */
    protected function pikmeRow(string $dtxid, array $overrides = []): array
    {
        return array_merge([
            'dtxsid' => $dtxid,
            'logkoc_pred_opera' => 2.5,
            'koc_predrange_opera' => '1-3',
            'conf_index_koc_opera' => 0.8,
            'ad_koc_opera' => '1',
            'ad_index_koc_opera' => 0.9,
            'logbcf_pred_opera' => 1.5,
            'bcf_predrange_opera' => '1-2',
            'conf_index_bcf_opera' => 0.7,
            'ad_bcf_opera' => '1',
            'ad_index_bcf_opera' => 0.8,
            'biodeg_loghalflife_pred_opera' => 1.1,
            'biodeg_predrange_opera' => '1-2',
            'conf_index_biodeg_opera' => 0.6,
            'ad_biodeg_opera' => '1',
            'ad_index_biodeg_opera' => 0.7,
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function writePikme(array $rows): void
    {
        $writer = SimpleExcelWriter::create($this->hazardsDir().'/hazards_pikme.csv');
        $writer->addHeader(array_keys($rows[0] ?? $this->pikmeRow('X')));
        foreach ($rows as $row) {
            $writer->addRow($row);
        }
        $writer->close();
    }

    /**
     * Ordered, JSON-encoded content of the given tables, for before/after comparison.
     *
     * @param  list<string>  $tables
     * @return array<string, string>
     */
    protected function snapshot(array $tables): array
    {
        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = json_encode(DB::table($table)->orderBy('id')->get()->all());
        }

        return $snapshot;
    }

    /**
     * Snapshot of every table the hazards seeders must never touch.
     *
     * @return array<string, string>
     */
    protected function nonHazardsSnapshot(): array
    {
        $snapshot = $this->snapshot(['susdat_substances', 'roles', 'users']);
        $snapshot['model_has_roles'] = json_encode(DB::table('model_has_roles')->orderBy('role_id')->orderBy('model_id')->get()->all());
        $snapshot['database_entities_non_hazards'] = json_encode(
            DB::table('database_entities')->where('code', '!=', 'hazards')->orderBy('id')->get()->all()
        );

        if (\Illuminate\Support\Facades\Schema::hasTable('factsheet_entities')) {
            $snapshot['factsheet_entities'] = json_encode(DB::table('factsheet_entities')->orderBy('id')->get()->all());
        }

        return $snapshot;
    }

    protected function countRows(string $table): int
    {
        return DB::table($table)->count();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\Statistic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateEmpodatSuspectStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:generate-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all statistics for Empodat Suspect module';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Empodat Suspect statistics generation...');

        // Get empodat_suspect database entity
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            $this->error('Empodat Suspect database entity not found.');

            return 1;
        }

        try {
            // 1. Total number of substances
            $this->info('Generating total substances statistic...');
            $totalSubstances = EmpodatSuspectMain::distinct('substance_id')
                ->whereNotNull('substance_id')
                ->count('substance_id');

            Statistic::create([
                'database_entity_id' => $empodatSuspectEntity->id,
                'key' => 'empodat_suspect.total_substances',
                'meta_data' => [
                    'count' => $totalSubstances,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
            $this->info("✓ Total substances: {$totalSubstances}");

            // 2. Number of substances per sample code (xlsx_name)
            $this->info('Generating substances by sample code statistic...');
            $substancesBySampleCode = DB::table('empodat_suspect_main as esm')
                ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
                ->select(
                    'mapping.xlsx_name as sample_code',
                    DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
                )
                ->whereNotNull('esm.substance_id')
                ->whereNotNull('mapping.xlsx_name')
                ->groupBy('mapping.xlsx_name')
                ->orderBy('mapping.xlsx_name')
                ->get()
                ->pluck('substance_count', 'sample_code')
                ->toArray();

            Statistic::create([
                'database_entity_id' => $empodatSuspectEntity->id,
                'key' => 'empodat_suspect.substances_by_sample_code',
                'meta_data' => [
                    'data' => $substancesBySampleCode,
                    'generated_at' => now()->toISOString(),
                    'total_sample_codes' => count($substancesBySampleCode),
                ],
            ]);
            $this->info('✓ Substances by sample code: '.count($substancesBySampleCode).' sample codes');

            // 3. Number of records per sample code (xlsx_name)
            $this->info('Generating records by sample code statistic...');
            $recordsBySampleCode = DB::table('empodat_suspect_main as esm')
                ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
                ->select(
                    'mapping.xlsx_name as sample_code',
                    DB::raw('COUNT(*) as record_count')
                )
                ->whereNotNull('mapping.xlsx_name')
                ->groupBy('mapping.xlsx_name')
                ->orderBy('mapping.xlsx_name')
                ->get()
                ->pluck('record_count', 'sample_code')
                ->toArray();

            Statistic::create([
                'database_entity_id' => $empodatSuspectEntity->id,
                'key' => 'empodat_suspect.records_by_sample_code',
                'meta_data' => [
                    'data' => $recordsBySampleCode,
                    'generated_at' => now()->toISOString(),
                    'total_sample_codes' => count($recordsBySampleCode),
                ],
            ]);
            $this->info('✓ Records by sample code: '.count($recordsBySampleCode).' sample codes');

            // 4. Number of substances per country
            $this->info('Generating substances by country statistic...');
            $substancesByCountry = DB::table('empodat_suspect_main as esm')
                ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
                ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
                ->select(
                    'lc.name as country_name',
                    'lc.code as country_code',
                    DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
                )
                ->whereNotNull('esm.substance_id')
                ->whereNotNull('es.country_id')
                ->groupBy('lc.name', 'lc.code')
                ->orderBy('lc.name')
                ->get();

            $substancesByCountryData = [];
            foreach ($substancesByCountry as $stat) {
                $substancesByCountryData[$stat->country_name] = [
                    'code' => $stat->country_code,
                    'count' => $stat->substance_count,
                ];
            }

            Statistic::create([
                'database_entity_id' => $empodatSuspectEntity->id,
                'key' => 'empodat_suspect.substances_by_country',
                'meta_data' => [
                    'data' => $substancesByCountryData,
                    'generated_at' => now()->toISOString(),
                    'total_countries' => count($substancesByCountryData),
                ],
            ]);
            $this->info('✓ Substances by country: '.count($substancesByCountryData).' countries');

            // 5. Number of records per country
            $this->info('Generating records by country statistic...');
            $recordsByCountry = DB::table('empodat_suspect_main as esm')
                ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
                ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
                ->select(
                    'lc.name as country_name',
                    'lc.code as country_code',
                    DB::raw('COUNT(*) as record_count')
                )
                ->whereNotNull('es.country_id')
                ->groupBy('lc.name', 'lc.code')
                ->orderBy('lc.name')
                ->get();

            $recordsByCountryData = [];
            foreach ($recordsByCountry as $stat) {
                $recordsByCountryData[$stat->country_name] = [
                    'code' => $stat->country_code,
                    'count' => $stat->record_count,
                ];
            }

            Statistic::create([
                'database_entity_id' => $empodatSuspectEntity->id,
                'key' => 'empodat_suspect.records_by_country',
                'meta_data' => [
                    'data' => $recordsByCountryData,
                    'generated_at' => now()->toISOString(),
                    'total_countries' => count($recordsByCountryData),
                ],
            ]);
            $this->info('✓ Records by country: '.count($recordsByCountryData).' countries');

            $this->info('');
            $this->info('All statistics generated successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error generating statistics: '.$e->getMessage());

            return 1;
        }
    }
}

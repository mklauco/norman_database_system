<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\EmpodatStationSeeder;
use Database\Seeders\Migrators\SusdatSusdatMigrator;
use Database\Seeders\SusdatSusdatCategoryJoinSeeder;
use Database\Seeders\Migrators\SuspectListExchangeMigrator;


class DatabaseSeeder extends Seeder
{
    /**
    * Seed the application's database.
    */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            /*
            AdminSeeder::class,
            RolesAndPermissionsSeeder::class,
            DatabaseEntitySeeder::class,

            // **************************************
            // LISTs
            // **************************************
            ListCountrySeeder::class,
            ListCoordinatePrecisionSeeder::class,
            ListConcentrationIndicatorSeeder::class,
            ListSamplingTechniqueSeeder::class,
            ListTreatmentLessSeeder::class,

            // Analytical methods
            ListCoverageFactorSeeder::class,
            ListSamplePreparationMethodSeeder::class,
            ListAnalyticalMethodSeeder::class,
            ListStandardisedMethodSeeder::class,
            ListValidatedMethodSeeder::class,
            ListYesNoQuestionSeeder::class,
            ListSummaryPerformanceSeeder::class,
            ListSamplingMethodSeeder::class,
            ListSamplingCollectionDeviceSeeder::class,
            EmpodatAnalyticalMethodSeeder::class,
            QualityEmpodatAnalyticalMethodsSeeder::class,



            // Data source
            ListTypeDataSourceSeeder::class,
            ListTypeMonitoringSeeder::class,
            ListDataAccessibilitySeeder::class,
            ListDataSourceLaboratorySeeder::class,
            ListDataSourceOrganisationSeeder::class,
            ListMatricesSeeder::class,
            EmpodatDataSourceSeeder::class,

            //ListTypeStationSeeder::class,

            // SUSDAT
            SusdatSusdatMigrator::class,
            SuspectListExchangeSourceSeeder::class,
            SusdatCategorySeeder::class,
            SusdatSourceSubstanceJoinSeeder::class,
            SusdatCategorySubstanceJoinSeeder::class,


            // Migrators for SLE
            // SuspectListExchangeMigrator::class,
            // SuspectListExchangeSourceJoinSeeder::class,

            // EMPODAT
            EmpodatStationSeeder::class,

            //BIOASSAY SEEDER
            BioassayMonitorXSeeder::class,
            BioassaysMonitorDataSourceSeeder::class,
            BioassaysMonitoringDataSeeder::class,
            BioassayFieldStudySeeder::class,

            //Backend
            ProjectSeeder::class, // testing data
            SarsCov2SourceSeeder::class,
            SarsCov2Part1Seeder::class,
            SarsCov2Part2Seeder::class,
            */

            // Indoor
            /*
            IndoorDataSeeder::class,
            IndoorMainSeeder::class,
            */

            // Passive Sampling
            /*
            PassiveDataSeeder::class,
            PassiveMainSeeder::class,
            */

            // ARGBG
            /*
            ARBGDataSeeder::class,
            ARBGBacteriaMainSeeder::class,
            ARBGGeneMainSeeder::class,
            ARBGBacteriaCoordinateSeeder::class,
            ARBGGeneCoordinateSeeder::class,
            ARBGBacteriaDataSourceSeeder::class,
            ARBGGeneDataSourceSeeder::class,
            */
        ]);
    }
}

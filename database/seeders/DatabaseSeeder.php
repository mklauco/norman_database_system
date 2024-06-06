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
            // LISTs
            ListCountrySeeder::class,
            ListCoordinatePrecisionSeeder::class,
            ListConcentrationIndicatorSeeder::class,
            ListSamplingTechniqueSeeder::class,
            ListTreatmentLessSeeder::class,
            ListTypeDataSourceSeeder::class,
            ListTypeMonitoringSeeder::class,

            //EmpodatStationSeeder::class,
            AdminSeeder::class,
            RolesAndPermissionsSeeder::class,
            DatabaseEntitySeeder::class,


            // SUSDAT
<<<<<<< HEAD
            SusdatCategorySeeder::class,
            SuspectListExchangeSourceSeeder::class,

            // Migrators
            SusdatSusdatMigrator::class,
            SuspectListExchangeSourceJoinSeeder::class,
            SuspectListExchangeMigrator::class,
=======
            //SusdatCategorySeeder::class,

            // Migrators
            //SusdatSusdatMigrator::class,
            //SusdatSusdatCategoryJoinSeeder::class,

>>>>>>> feature/supporting_tables
            
        ]);
    }
}

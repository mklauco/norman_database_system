<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatSusdatSubstanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Substance::query()->delete();

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // $command = 'PGPASSWORD=root psql -h 127.0.0.1 -U postgres -d norman -f _dbdata/susdat_substance-2024_06_04_21_29_53-dump.sql';

        // if ($isWindows) {
            $command = "psql -q -h localhost -U postgres -d norman -f _dbdata/susdat_substance-2024_06_04_21_29_53-dump.sql";
        // } else {
        //     $command = "PGPASSWORD=root psql -h 127.0.0.1 -U postgres -d norman -f _dbdata/susdat_substance-2024_06_04_21_29_53-dump.sql";
        // }

        // Running the command
        $output = null;
        $return_var = null;
        exec($command, $output, $return_var);

       // Output the result
       echo implode("\n", $output);

       // Check if the command was successful
       if ($return_var !== 0) {
           throw new \Exception("Failed to execute psql command: " . implode("\n", $output));
       }

    }
}

<?php

namespace Database\Seeders\migrators;

use App\Models\MariaDB\DCTAnalysis as MariaDB_DCTAnalysis;
use Illuminate\Database\Seeder;

class EmpodatDCTAnalysisMigrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $data = MariaDB_DCTAnalysis::where('id', '<=', 10)->get();
        dd($data);
    }
}

// php artisan db:seed --class=Database\Seeders\migrators\EmpodatDCTAnalysisMigrator

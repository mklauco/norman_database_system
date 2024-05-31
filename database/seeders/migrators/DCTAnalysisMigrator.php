<?php

namespace Database\Seeders\migrators;

use Illuminate\Database\Seeder;
use App\Models\MariaDB\DCTAnalysis as MariaDB_DCTAnalysis;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DCTAnalysisMigrator extends Seeder
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

// php artisan db:seed --class=Database\Seeders\migrators\DCTAnalysisMigrator

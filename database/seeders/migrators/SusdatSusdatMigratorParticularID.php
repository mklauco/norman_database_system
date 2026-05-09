<?php

namespace Database\Seeders\Migrators;

use App\Models\Susdat\Substance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SusdatSusdatMigratorParticularID extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $id = 67141;
        // $id = 102736;
        // $id = 158751003;
        $p = [];

        $p[] = [
            'id' => $id,
            'code' => $id,
            'name' => 'Unknown',
            'added_by' => 1,
        ];
        Substance::insert($p);
    }
}

// php artisan db:seed --class=Database\Seeders\migrators\SusdatSusdatMigratorParticularID

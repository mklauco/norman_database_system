<?php

namespace Database\Seeders\Migrators;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Models\MariaDB\Susdat as OldData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SusdatSusdatMigratorParticularID extends Seeder
{
  /**
  * Run the database seeds.
  */
  public function run(): void
  {
    //
    // $id = 67141;
    // $id = 102736;
    $id = 158751003;
    $p = [];
    
    $p[] = [
      'id'                => $id,
      'code'              => $id,
      'name'              => 'Unknown',
      'added_by'          => 1,
    ];
    Substance::insert($p);
  }
  
  
}


// php artisan db:seed --class=Database\Seeders\migrators\SusdatSusdatMigratorParticularID

<?php

namespace Database\Seeders;

use App\Models\Backend\Project;
use Illuminate\Database\Seeder;
use App\Models\Backend\ProjectUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectUserSeeder extends Seeder
{
  /**
  * Run the database seeds.
  */
  public function run(): void
  {
    //
    $now = now();
    ProjectUser::insert([
      [
        'project_id' => 1,
        'user_id' => 1,
      ],
      [
        'project_id' => 3,
        'user_id' => 1,
      ],
      [
        'project_id' => 5,
        'user_id' => 1,
      ],
      [
        'project_id' => 2,
        'user_id' => 2,
      ],
      [
        'project_id' => 5,
        'user_id' => 2,
      ],
    ]);
  }
}

// php artisan db:seed --class=ProjectUserSeeder
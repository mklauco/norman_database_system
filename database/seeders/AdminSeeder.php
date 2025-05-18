<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
    * Run the database seeds.
    */
    public function run(): void
    {
        User::insert([
            [
                'id' => 2,
                'first_name'          => 'Martin',
                'last_name'           => 'Klaučo',
                'email'               => 'martin@klauco.com',
                'email_verified_at'   => now(),
                'password'            => Hash::make('password'),
                'remember_token'      => Str::random(10),
            ],
            // user already exists with ID: 545
            // [
            //     'id' => 3,
            //     'first_name'          => 'Luboš',
            //     'last_name'           => 'Čirka',
            //     'email'               => 'lubos.cirka@stuba.sk',
            //     'email_verified_at'   => now(),
            //     'password'            => Hash::make('password'),
            //     'remember_token'      => Str::random(10),
            // ],
        ]);
    }
}

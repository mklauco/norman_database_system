<?php

namespace Database\Seeders;

use App\Models\Backend\ServerPayment;
use Illuminate\Database\Seeder;

class ServerPaymentsInitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServerPayment::firstOrCreate(
            [
                'period_start_date' => '2025-09-03',
                'period_end_date' => '2025-10-02',
            ],
            [
                'status' => 'paid',
                'amount_without_vat' => 500,
                'variable_symbol' => '20250009',
            ]
        );
    }
}

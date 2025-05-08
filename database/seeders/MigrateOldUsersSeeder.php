<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class MigrateOldUsersSeeder extends Seeder
{
    /**
     * Migrate users from a CSV file to the new database.
     */
    public function run(): void
    {
        $this->command->info('Starting optimized user migration from CSV...');
        
        // Correct way to handle constraints in PostgreSQL
        Schema::disableForeignKeyConstraints();
        
        try {
            // Clear the users table before migration
            DB::table('users')->truncate();
            $this->command->info('Users table truncated.');
        } catch (\Exception $e) {
            $this->command->error("Error truncating users table: " . $e->getMessage());
            // If truncate fails, try delete all
            DB::table('users')->delete();
            $this->command->info('Users deleted using DELETE instead of TRUNCATE.');
        }
        
        // Path to the CSV file
        $path = base_path('database/seeders/seeds/users.csv');
        
        // Check if the file exists
        if (!file_exists($path)) {
            // Re-enable foreign key constraints before exiting
            Schema::enableForeignKeyConstraints();
            $this->command->error("CSV file not found at: {$path}");
            return;
        }
        
        $now = Carbon::now();
        $startTime = microtime(true);
        
        // Use a larger chunk size for better performance
        $chunkSize = 100;
        $totalRows = 0;
        
        // Use Spatie's SimpleExcelReader for efficient CSV processing
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(',')
            ->headersToSnakeCase();
        
        // Process in chunks for batch insertion
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows) use (&$totalRows, $now, $startTime) {
                $chunkStartTime = microtime(true);
                $default_password = Hash::make(Str::random(12));
                $users = [];
                foreach ($rows as $row) {
                    try {
                        // Build record for batch insertion
                        $users[] = [
                            'id' => $row['id'],
                            'username' => $row['username'] ?? null,
                            'first_name' => $row['firstname'] ?? null,
                            'last_name' => $row['surname'] ?? null,
                            'salutation' => $row['mr_ms'] ?? null,
                            'email' => $row['email'],
                            'email_verified_at' => $now,
                            'organisation' => $row['organisation'] ?? null,
                            'organisation_id' => null, // Will be populated later if needed
                            'organisation_other' => $row['organisation_other'] ?? null,
                            'country' => $row['country'] ?? null,
                            'country_id' => null, // Will be populated later if needed
                            'active' => isset($row['active']) ? (bool)$row['active'] : true,
                            'password' => $default_password,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        
                    } catch (\Exception $e) {
                        // Just log the error and continue
                        Log::error("User import error: " . $e->getMessage(), [
                            'email' => $row['email'] ?? 'unknown',
                            'row' => json_encode($row)
                        ]);
                    }
                }
                
                // Batch insert all users in this chunk
                if (!empty($users)) {
                    try {
                        // For PostgreSQL, we can use insert but need to handle potential errors differently
                        DB::table('users')->insert($users);
                        $totalRows += count($users);
                    } catch (\Exception $e) {
                        $this->command->error("Batch insert error: " . $e->getMessage());
                        Log::error("Batch insert error: " . $e->getMessage());
                        
                        // If batch insert fails, try inserting one by one
                        $this->command->info("Trying individual inserts...");
                        foreach ($users as $user) {
                            try {
                                DB::table('users')->insert([$user]);
                                $totalRows++;
                            } catch (\Exception $innerE) {
                                Log::error("Individual insert error: " . $innerE->getMessage(), [
                                    'email' => $user['email'] ?? 'unknown'
                                ]);
                            }
                        }
                    }
                }
                
                $chunkEndTime = microtime(true);
                $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                $totalElapsedTime = round($chunkEndTime - $startTime, 2);
                
                $this->command->info("Processed chunk with " . count($users) . " records. Chunk time: {$chunkElapsedTime}s, Total: {$totalRows}, Elapsed: {$totalElapsedTime}s");
            });
        
        // Reset sequence for the id column to ensure future inserts work correctly
        // This is a PostgreSQL-specific operation
        try {
            DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");
            $this->command->info("User ID sequence reset.");
        } catch (\Exception $e) {
            $this->command->error("Error resetting sequence: " . $e->getMessage());
        }
        
        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();
        
        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 2);
        
        $this->command->info("Migration completed in {$totalTime} seconds.");
        $this->command->info("Total records imported: {$totalRows}");
    }
}
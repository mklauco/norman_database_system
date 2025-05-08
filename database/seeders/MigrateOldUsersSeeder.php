<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;

class MigrateOldUsersSeeder extends Seeder
{
    /**
     * Migrate users from a CSV file to the new database.
     */
    public function run(): void
    {
        $this->command->info('Starting user migration from CSV...');
        
        // Path to the CSV file
        $path = base_path('database/seeders/seeds/users.csv');
        
        // Check if the file exists
        if (!file_exists($path)) {
            $this->command->error("CSV file not found at: {$path}");
            return;
        }
        
        $now = Carbon::now();
        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $startTime = microtime(true);
        
        // Use Spatie's SimpleExcelReader for efficient CSV processing
        $reader = SimpleExcelReader::create($path)
            ->useDelimiter(',')  // Specify your delimiter if not comma
            ->headersToSnakeCase(); // Convert headers to snake_case
        
        // Process in chunks to conserve memory
        $chunkSize = 100;
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows) use (&$totalRows, &$successCount, &$errorCount, $now, $startTime) {
                $chunkStartTime = microtime(true);
                
                foreach ($rows as $row) {
                    try {
                        // Check if the user already exists in the new system
                        $existingUser = DB::table('users')->where('email', $row['email'])->first();
                        
                        // Map organisation and country IDs if needed
                        $organisationId = null;
                        $countryId = null;
                        
                        // Organisation ID mapping
                        // Uncomment and customize if you need to map organisation names to IDs
                        // if (!empty($row['organisation'])) {
                        //     $organisationId = DB::table('organisations')
                        //         ->where('name', $row['organisation'])
                        //         ->value('id');
                        // }
                        
                        // Country ID mapping
                        // Uncomment and customize if you need to map country names to IDs
                        // if (!empty($row['country'])) {
                        //     $countryId = DB::table('countries')
                        //         ->where('name', $row['country'])
                        //         ->value('id');
                        // }
                        
                        if ($existingUser) {
                            // Update existing user
                            DB::table('users')
                                ->where('id', $existingUser->id)
                                ->update([
                                    'username' => $row['username'] ?? null,
                                    'salutation' => $row['mr_ms'] ?? null,
                                    'organisation' => $row['organisation'] ?? null,
                                    'organisation_id' => $organisationId,
                                    'organisation_other' => $row['organisation_other'] ?? null,
                                    'country' => $row['country'] ?? null,
                                    'country_id' => $countryId,
                                    'active' => isset($row['active']) ? (bool)$row['active'] : true,
                                    'updated_at' => $now,
                                ]);
                        } else {
                            // Insert new user
                            DB::table('users')->insert([
                                'username' => $row['username'] ?? null,
                                'first_name' => $row['firstname'] ?? null,
                                'last_name' => $row['surname'] ?? null,
                                'salutation' => $row['mr_ms'] ?? null,
                                'email' => $row['email'],
                                'email_verified_at' => $now,  // Assume all imported users are verified
                                'organisation' => $row['organisation'] ?? null,
                                'organisation_id' => $organisationId,
                                'organisation_other' => $row['organisation_other'] ?? null,
                                'country' => $row['country'] ?? null,
                                'country_id' => $countryId,
                                'active' => isset($row['active']) ? (bool)$row['active'] : true,
                                'password' => Hash::make($row['passwd'] ?? str_random(12)),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                        
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->command->error("Error processing user {". $row['email'] ?? 'unknown' ." }: " . $e->getMessage());
                        Log::error("User import error: " . $e->getMessage(), ['row' => $row]);
                    }
                    
                    $totalRows++;
                }
                
                $chunkEndTime = microtime(true);
                $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                $totalElapsedTime = round($chunkEndTime - $startTime, 2);
                
                $this->command->info("Processed chunk with " . count($rows) . " records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
            });
        
        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 2);
        
        $this->command->info("Migration completed in {$totalTime} seconds.");
        $this->command->info("Total records: {$totalRows}, Successful: {$successCount}, Errors: {$errorCount}");
    }
}
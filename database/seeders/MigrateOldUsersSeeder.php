<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class MigrateOldUsersSeeder extends Seeder
{
    /**
     * Migrate users from a CSV file to the new database.
     */
    public function run(): void
    {
        $this->command->info('Starting user migration from CSV...');

        // 1. First seed the admin user(s)
        $this->call(AdminSeeder::class);
        $this->command->info('Admin user(s) seeded.');
        
        // 2. Path to the CSV file
        $path = base_path('database/seeders/seeds/users.csv');
        
        if (!file_exists($path)) {
            $this->command->error("CSV file not found at: {$path}");
            return;
        }
        
        $now = Carbon::now();
        $startTime = microtime(true);
        $totalRows = 0;
        $default_password = Hash::make(Str::random(12));
        
        // Disable foreign key constraints
        Schema::disableForeignKeyConstraints();
        
        // 3. Get existing users to check for updates vs inserts
        $existingUserIds = DB::table('users')->pluck('id')->toArray();
        
        // 4. Process CSV in chunks
        SimpleExcelReader::create($path)
            ->useDelimiter(',')
            ->headersToSnakeCase()
            ->getRows()
            ->chunk(100)
            ->each(function ($rows) use (&$totalRows, $now, $default_password, $existingUserIds) {
                $insertRecords = [];
                
                foreach ($rows as $row) {
                    // Skip if no ID or email
                    if (empty($row['id']) || empty($row['email'])) {
                        continue;
                    }
                    
                    $userId = (int)$row['id'];
                    
                    // Create standard user record
                    $userData = [
                        'username' => $row['username'] ?? null,
                        'first_name' => $row['firstname'] ?? null,
                        'last_name' => $row['surname'] ?? null,
                        'salutation' => $row['mr_ms'] ?? null,
                        'email' => $row['email'],
                        'email_verified_at' => $now,
                        'organisation' => $row['organisation'] ?? null,
                        'organisation_id' => null,
                        'organisation_other' => $row['organisation_other'] ?? null,
                        'country' => $row['country'] ?? null,
                        'country_id' => null,
                        'active' => isset($row['active']) ? (bool)$row['active'] : true,
                        'updated_at' => $now,
                    ];
                    
                    // If user exists (including ID=1), update it
                    if (in_array($userId, $existingUserIds)) {
                        DB::table('users')
                            ->where('id', $userId)
                            ->update($userData);
                    } else {
                        // For new users, add ID, password and created_at
                        $userData['id'] = $userId;
                        $userData['password'] = $default_password;
                        $userData['created_at'] = $now;
                        $insertRecords[] = $userData;
                    }
                }
                
                // Batch insert new users
                if (!empty($insertRecords)) {
                    DB::table('users')->insert($insertRecords);
                    $totalRows += count($insertRecords);
                }
                
                $this->command->info("Processed batch - Total imported: {$totalRows}");
            });
        
        // Reset PostgreSQL sequence
        try {
            DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");
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
// php artisan db:seed --class=MigrateOldUsersSeeder
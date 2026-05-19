<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LiteratureUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder adds the "literature" role to existing users or creates new users
     * with the "literature" role if they don't exist.
     */
    public function run(): void
    {
        // Fix PostgreSQL sequence if needed
        $this->fixPostgresSequence();

        // Ensure the roles exist
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'literature']);

        // List of users to add literature role to
        $users = [
            ['name' => 'Barmentlo, S.H. (Henrik)', 'email' => 's.h.barmentlo@cml.leidenuniv.nl'],
            ['name' => 'Kropf, P. (Philipp)', 'email' => 'p.kropf@cml.leidenuniv.nl'],
            ['name' => 'Tom Nolte', 'email' => 'tom.nolte@naturalis.nl'],
            ['name' => 'Paola Movalli', 'email' => 'paola.movalli@naturalis.nl'],
            ['name' => 'Guy Duke', 'email' => 'guy.duke@skynet.be'],
            ['name' => 'Treu, Gabriele', 'email' => 'Gabriele.Treu@uba.de'],
            ['name' => 'Peter Fantke', 'email' => 'peter@substitute.dk'],
            ['name' => 'Nikiforos Alygizakis', 'email' => 'alygizakis@ei.sk'],
            ['name' => 'Peter Oswald', 'email' => 'oswald@ei.sk'],
            ['name' => 'Martina Oswaldova', 'email' => 'oswaldova@ei.sk'],
        ];

        foreach ($users as $userData) {
            $this->createOrUpdateUser($userData['name'], $userData['email']);
        }

        $this->command->info('Literature users seeder completed successfully.');
    }

    /**
     * Create or update a user with the literature role
     */
    private function createOrUpdateUser(string $name, string $email): void
    {
        // Parse the name into first and last name
        $parsedName = $this->parseName($name);

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            // User exists - add literature role if they don't have it
            if (! $user->hasRole('literature')) {
                $user->assignRole('literature');
                $this->command->info("✓ Added 'literature' role to existing user: {$email}");
            } else {
                $this->command->info("○ User {$email} already has 'literature' role");
            }
        } else {
            // User doesn't exist - create new user
            $user = User::create([
                'first_name' => $parsedName['first_name'],
                'last_name' => $parsedName['last_name'],
                'email' => $email,
                'password' => Hash::make('Literature2024!'), // Default password
                'active' => true,
            ]);

            // Assign roles
            $user->assignRole(['user', 'literature']);

            $this->command->info("✓ Created new user: {$email} (Password: Literature2024!)");
        }
    }

    /**
     * Parse name string into first and last name
     * Handles formats like:
     * - "Last, First (Nickname)" → First: Nickname (or First if no nickname), Last: Last
     * - "First Last" → First: First, Last: Last
     */
    private function parseName(string $name): array
    {
        // Remove extra spaces
        $name = trim($name);

        // Check if name has comma (format: "Last, First (Nickname)")
        if (strpos($name, ',') !== false) {
            $parts = explode(',', $name, 2);
            $lastName = trim($parts[0]);
            $firstPart = trim($parts[1]);

            // Check for nickname in parentheses
            if (preg_match('/\(([^)]+)\)/', $firstPart, $matches)) {
                $firstName = trim($matches[1]); // Use nickname as first name
            } else {
                // No nickname, use the part after comma (might include initials)
                $firstName = trim(preg_replace('/\s*\([^)]*\)/', '', $firstPart));
            }

            return [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        // Format: "First Last" or "First Middle Last"
        $parts = explode(' ', $name);

        if (count($parts) >= 2) {
            $firstName = $parts[0];
            $lastName = implode(' ', array_slice($parts, 1)); // Everything after first name
        } else {
            $firstName = $parts[0];
            $lastName = $parts[0]; // Fallback
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    /**
     * Fix PostgreSQL sequence for users table if it's out of sync
     * This prevents "duplicate key value violates unique constraint" errors
     */
    private function fixPostgresSequence(): void
    {
        // Only run for PostgreSQL
        if (config('database.default') !== 'pgsql') {
            return;
        }

        try {
            \DB::statement("SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users))");
            $this->command->info('✓ PostgreSQL sequence synchronized');
        } catch (\Exception $e) {
            $this->command->warn('Could not synchronize PostgreSQL sequence: '.$e->getMessage());
        }
    }
}

// Run with: php artisan db:seed --class=LiteratureUsersSeeder

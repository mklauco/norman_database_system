<?php

namespace Database\Seeders\Literature;

use App\Models\List\Tissue;
use App\Models\List\TissueSubcategory;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListTissuesUpdateSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder safely updates the list_tissues table and populates
     * the list_tissues_subcategory table without breaking existing relationships.
     *
     * It performs two operations:
     * 1. Insert new tissues from list_tissues_2.csv (skip if already exists)
     * 2. Populate subcategories from list_tissues_subcategories.csv
     */
    public function run(): void
    {
        $this->command->info('Starting safe tissue update and subcategory seeding...');

        // Step 1: Update list_tissues table (insert only new entries)
        $this->updateTissues();

        // Step 2: Populate subcategories table
        $this->seedSubcategories();

        $this->command->info('Tissue update and subcategory seeding completed successfully!');
    }

    /**
     * Update list_tissues table - insert only if not exists
     */
    protected function updateTissues(): void
    {
        $this->command->info('Updating list_tissues table (insert only new entries)...');

        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/literature/list_tissues_2.csv';

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $rows = SimpleExcelReader::create($path)->getRows();
        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $r) {
            // Skip empty rows
            if (empty($r['main_category'])) {
                continue;
            }

            // Clean the value and capitalize first letter
            $cleanedValue = $this->cleanValue($r['main_category']);

            // Skip if value is null, empty, or 'NA' after cleaning
            if ($cleanedValue === null || $cleanedValue === '' || strtoupper($cleanedValue) === 'NA') {
                continue;
            }

            // Apply ucfirst to capitalize first letter
            $cleanedValue = ucfirst(strtolower($cleanedValue));

            // Check if tissue already exists (case-insensitive)
            $exists = Tissue::whereRaw('LOWER(name) = ?', [strtolower($cleanedValue)])->exists();

            if ($exists) {
                $skippedCount++;
                $this->command->line("  Skipped (exists): {$cleanedValue}");
            } else {
                // Insert new tissue
                Tissue::create([
                    'name' => $cleanedValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertedCount++;
                $this->command->line("  Inserted: {$cleanedValue}");
            }
        }

        $this->command->info("List tissues update complete: {$insertedCount} inserted, {$skippedCount} skipped (already exist)");
    }

    /**
     * Seed the subcategories table
     */
    protected function seedSubcategories(): void
    {
        $this->command->info('Seeding list_tissues_subcategory table...');

        $path = base_path().'/database/seeders/seeds/literature/list_tissues_subcategories.csv';

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        // Truncate subcategories table (safe because it's new)
        TissueSubcategory::truncate();
        $this->command->info('Cleared existing subcategories...');

        // Read the CSV file
        $file = fopen($path, 'r');

        // Read the header row (tissue names)
        $headerRow = fgetcsv($file);

        // Remove BOM if present
        if (isset($headerRow[0])) {
            $headerRow[0] = str_replace("\xEF\xBB\xBF", '', $headerRow[0]);
        }

        // Map tissue names to their IDs
        $tissueMap = [];
        foreach ($headerRow as $index => $tissueName) {
            $tissueName = trim($tissueName);
            if (empty($tissueName)) {
                continue;
            }

            // Find the tissue ID (case-insensitive)
            $tissue = Tissue::whereRaw('LOWER(name) = ?', [strtolower($tissueName)])->first();

            if ($tissue) {
                $tissueMap[$index] = [
                    'id' => $tissue->id,
                    'name' => $tissue->name,
                ];
            } else {
                $this->command->warn("Warning: Tissue '{$tissueName}' not found in list_tissues table");
            }
        }

        $now = Carbon::now();
        $subcategories = [];
        $totalCount = 0;

        // Read each row and extract subcategories
        while (($row = fgetcsv($file)) !== false) {
            foreach ($row as $columnIndex => $subcategoryName) {
                // Skip if no tissue mapping for this column
                if (! isset($tissueMap[$columnIndex])) {
                    continue;
                }

                // Clean and validate subcategory name
                $cleanedSubcategory = $this->cleanValue($subcategoryName);

                if ($cleanedSubcategory === null || $cleanedSubcategory === '') {
                    continue;
                }

                // Apply ucfirst to capitalize first letter
                $cleanedSubcategory = ucfirst(strtolower($cleanedSubcategory));

                // Add to batch insert array
                $subcategories[] = [
                    'tissue_id' => $tissueMap[$columnIndex]['id'],
                    'name' => $cleanedSubcategory,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalCount++;
            }
        }

        fclose($file);

        // Insert subcategories in chunks
        if (! empty($subcategories)) {
            $chunkSize = 500;
            $chunks = array_chunk($subcategories, $chunkSize);
            $chunkCount = count($chunks);

            foreach ($chunks as $index => $chunk) {
                $this->command->info('Inserting subcategories chunk '.($index + 1)."/{$chunkCount}");
                DB::table('list_tissues_subcategory')->insert($chunk);
            }

            $this->command->info("Successfully seeded {$totalCount} tissue subcategories.");
        } else {
            $this->command->warn('No subcategories found to seed.');
        }
    }

    /**
     * Clean and trim the value, return null if empty
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function cleanValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Trim whitespace
        $cleaned = trim($value);

        // Return null if empty after trimming
        if ($cleaned === '') {
            return null;
        }

        return $cleaned;
    }
}

// To run this seeder:
// php artisan db:seed --class=Database\\Seeders\\Literature\\ListTissuesUpdateSeeder

<?php

namespace Database\Seeders;

use App\Models\Backend\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class UpdateTemplateFilesizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This is a temporary seeder to populate filesize for existing templates.
     * Run manually: php artisan db:seed --class=UpdateTemplateFilesizeSeeder
     */
    public function run(): void
    {
        $this->command->info('Starting filesize update for existing templates...');

        $templates = Template::whereNotNull('file_path')
            ->whereNull('filesize')
            ->get();

        $updated = 0;
        $notFound = 0;

        foreach ($templates as $template) {
            $filePath = $template->file_path;

            // Try different storage paths
            $possiblePaths = [
                $filePath,                    // Original path
                'public/'.$filePath,        // With public prefix
                'templates/'.basename($filePath), // Just filename in templates
                'public/templates/'.basename($filePath), // Public templates with just filename
            ];

            $fileFound = false;
            $actualPath = null;
            $filesize = 0;

            foreach ($possiblePaths as $path) {
                if (Storage::exists($path)) {
                    $filesize = Storage::size($path);
                    $actualPath = $path;
                    $fileFound = true;
                    break;
                }
            }

            if ($fileFound) {
                $template->update(['filesize' => $filesize]);
                $updated++;
                $this->command->info("Updated template #{$template->id}: {$template->name} - {$filesize} bytes (found at: {$actualPath})");
            } else {
                $notFound++;
                $this->command->warn("File not found for template #{$template->id}: {$template->name} - {$filePath}");
            }
        }

        $this->command->info('Filesize update completed!');
        $this->command->info("Templates updated: {$updated}");
        $this->command->info("Files not found: {$notFound}");
    }
}

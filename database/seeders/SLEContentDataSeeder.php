<?php

namespace Database\Seeders;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SLEContentDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting HTML table import...');

        // Load HTML from file
        $htmlContent = file_get_contents(base_path('database/seeders/seeds/suspect_list_table/main.html'));

        // Parse HTML
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlContent);

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');

        $now = now();

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells->length >= 6) {
                // Get code from first column - try anchor id first, then text content
                $codeAnchor = $xpath->query('.//a[@id]', $cells->item(0))->item(0);
                $code = $codeAnchor ? $codeAnchor->getAttribute('id') : trim($cells->item(0)->textContent);

                if ($code) {
                    // Get name from second column - try anchor id first, then text content
                    $nameAnchor = $xpath->query('.//a[@id]', $cells->item(1))->item(0);
                    $name = $nameAnchor ? $nameAnchor->getAttribute('id') : trim($cells->item(1)->textContent);

                    // Get description from third column
                    $description = trim($cells->item(2)->textContent);

                    // Get innerHTML for link columns
                    $linkFullList = $this->getInnerHTML($cells->item(3));
                    $linkInchikeyList = $this->getInnerHTML($cells->item(4));
                    $linkReferences = $this->getInnerHTML($cells->item(5));

                    // Update or insert
                    DB::table('suspect_list_exchange_sources')->updateOrInsert(
                        ['code' => $code],
                        [
                            'name' => $name,
                            'description' => $description,
                            'link_full_list' => $linkFullList,
                            'link_inchikey_list' => $linkInchikeyList,
                            'link_references' => $linkReferences,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );

                    $this->command->info("Processed: {$code}");
                }
            }
        }

        $this->command->info('HTML table import completed!');
    }

    private function getInnerHTML($element): ?string
    {
        if (! $element) {
            return null;
        }

        $innerHTML = '';
        foreach ($element->childNodes as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return trim($innerHTML) ?: null;
    }
}

// php artisan db:seed --class=SLEContentDataSeeder

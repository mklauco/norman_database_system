<?php

declare(strict_types=1);

namespace Tests\Feature\ARBG;

use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadedFilesPageTest extends TestCase
{
    use RefreshDatabase;

    private const BACTERIA_ENTITY_ID = 14;

    private const GENE_ENTITY_ID = 15;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseEntity::query()->insert([
            ['id' => self::BACTERIA_ENTITY_ID, 'code' => 'arbg.bacteria', 'name' => 'Antibiotic Resistant Bacteria/Genes'],
            ['id' => self::GENE_ENTITY_ID, 'code' => 'arbg.gene', 'name' => 'Antibiotic Resistant Bacteria/Genes'],
        ]);

        File::query()->insert([
            [
                'id' => 3000, 'database_entity_id' => self::BACTERIA_ENTITY_ID,
                'name' => 'ANSWER DCT_ARB Sewage Sludge_ESR10',
                'original_name' => 'ANSWER DCT_ARB Sewage Sludge_ESR10.xlsx',
                'file_path' => 'arbg/ANSWER DCT_ARB Sewage Sludge_ESR10.xlsx',
                'uploaded_at' => '2021-12-05', 'main_id_from' => 1, 'main_id_to' => 22,
                'analysis_number' => 22, 'list_type' => 'Sewage Sludge', 'note' => null, 'is_deleted' => false,
            ],
            [
                'id' => 3019, 'database_entity_id' => self::GENE_ENTITY_ID,
                'name' => 'ANSWER DCT_ARG Wastewater_ESR3v2',
                'original_name' => 'ANSWER DCT_ARG Wastewater_ESR3v2.xlsx',
                'file_path' => 'arbg/ANSWER DCT_ARG Wastewater_ESR3v2.xlsx',
                'uploaded_at' => '2021-12-25', 'main_id_from' => 1728, 'main_id_to' => 1769,
                'analysis_number' => 42, 'list_type' => 'Wastewater', 'note' => '=> 24',
                'is_deleted' => true,
            ],
            [
                'id' => 3026, 'database_entity_id' => self::GENE_ENTITY_ID,
                'name' => 'ANSWER DCT_ARG Wastewater_ESR3v3',
                'original_name' => 'ANSWER DCT_ARG Wastewater_ESR3v3.xlsx',
                'file_path' => 'arbg/ANSWER DCT_ARG Wastewater_ESR3v3.xlsx',
                'uploaded_at' => '2024-04-11', 'main_id_from' => 1728, 'main_id_to' => 1769,
                'analysis_number' => 42, 'list_type' => 'Wastewater', 'note' => null, 'is_deleted' => false,
            ],
        ]);
    }

    public function test_the_page_is_public(): void
    {
        $this->get(route('arbg.files.index'))->assertOk();
    }

    public function test_it_lists_the_submitted_files_with_their_record_ranges(): void
    {
        $response = $this->get(route('arbg.files.index'));

        $response->assertSee('List of Uploaded Files');
        $response->assertSee('ANSWER DCT_ARB Sewage Sludge_ESR10');
        $response->assertSee('ANSWER DCT_ARG Wastewater_ESR3v3');
        $response->assertSee('Sewage Sludge');
        $response->assertSee('2021-12-05');
    }

    public function test_it_numbers_the_rows_with_their_legacy_list_id(): void
    {
        $response = $this->get(route('arbg.files.index'));

        $html = $response->getContent();

        $this->assertStringContainsString('>1</td>', $html, 'ARB file 3000 should show as legacy list id 1');
        $this->assertStringContainsString('>24</td>', $html, 'ARG file 3026 should show as legacy list id 24');
    }

    public function test_superseded_files_are_listed_as_legacy_lists_them(): void
    {
        $response = $this->get(route('arbg.files.index'));

        $response->assertSee('ANSWER DCT_ARG Wastewater_ESR3v2');
        $response->assertSee('(superseded)');
        $response->assertSee('=&gt; 24', false);
    }

    public function test_it_does_not_offer_the_source_files_for_download(): void
    {
        $response = $this->get(route('arbg.files.index'));

        $response->assertDontSee('.xlsx');
        $response->assertDontSee(route('files.download', 3000));
    }
}

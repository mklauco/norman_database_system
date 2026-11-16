<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Livewire\Empodat\FileSearchTest;
use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FileSearchIdRangesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function makeFile(string $name, int $from, int $to): File
    {
        if (! DatabaseEntity::query()->whereKey(2)->exists()) {
            DatabaseEntity::factory()->create(['id' => 2]);
        }

        return File::query()->create([
            'name' => $name,
            'original_name' => $name,
            'database_entity_id' => 2,
            'main_id_from' => $from,
            'main_id_to' => $to,
        ]);
    }

    public function test_selected_files_show_their_id_ranges_and_combined_legacy_string(): void
    {
        $later = $this->makeFile('range_later.csv', 163220615, 163220887);
        $earlier = $this->makeFile('range_earlier.csv', 159109497, 159109520);

        Livewire::test(FileSearchTest::class, ['existingFiles' => [$later->id, $earlier->id]])
            ->assertSee('IDs 159109497 – 159109520', false)
            ->assertSee('IDs 163220615 – 163220887', false)
            ->assertSee('ID from 159109497 to 159109520 & 163220615 to 163220887')
            ->assertSeeHtml('name="fileSearch[]"');
    }

    public function test_no_range_line_without_selected_files(): void
    {
        Livewire::test(FileSearchTest::class)
            ->assertDontSee('ID from')
            ->assertDontSeeHtml('data-testid="id-ranges"');
    }
}

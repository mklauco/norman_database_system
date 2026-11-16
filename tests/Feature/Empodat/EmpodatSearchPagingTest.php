<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Models\Empodat\EmpodatMain;
use App\Services\Empodat\EmpodatSearchPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * A file-filtered EMPODAT search with few matches goes through the probe path
 * of EmpodatSearchPaginator and must still return the rows in id order with
 * the right total.
 *
 * Runs against the dedicated `norman_test` database (per `phpunit.xml`) and
 * wraps its inserts in a transaction that tearDown rolls back.
 */
class EmpodatSearchPagingTest extends TestCase
{
    private int $wantedFileId;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: '.$e->getMessage());
        }

        DB::beginTransaction();

        $this->wantedFileId = DB::table('files')->insertGetId(['name' => 'wanted', 'original_name' => 'w.xlsx']);
        $otherFileId = DB::table('files')->insertGetId(['name' => 'other', 'original_name' => 'o.xlsx']);

        for ($i = 0; $i < 205; $i++) {
            DB::table('empodat_main')->insert(['file_id' => $this->wantedFileId]);
            DB::table('empodat_main')->insert(['file_id' => $otherFileId]);
        }
    }

    protected function tearDown(): void
    {
        Paginator::currentPageResolver(fn (): int => 1);
        DB::rollBack();
        parent::tearDown();
    }

    public function test_length_aware_paging_returns_ordered_rows_and_exact_total(): void
    {
        $first = $this->paginate(1, false);

        $this->assertInstanceOf(LengthAwarePaginator::class, $first);
        $this->assertSame(205, $first->total());
        $this->assertCount(200, $first->items());
        $this->assertContainsOnlyInstancesOf(EmpodatMain::class, $first->items());

        $firstIds = collect($first->items())->pluck('id')->all();
        $sorted = $firstIds;
        sort($sorted);
        $this->assertSame($sorted, $firstIds);

        $second = $this->paginate(2, false);
        $secondIds = collect($second->items())->pluck('id')->all();

        $this->assertCount(5, $secondIds);
        $this->assertGreaterThan(max($firstIds), min($secondIds));
        $this->assertSame([$this->wantedFileId], collect($first->items())->pluck('file_id')->unique()->values()->all());
    }

    public function test_simple_paging_knows_whether_more_pages_exist(): void
    {
        $first = $this->paginate(1, true);
        $second = $this->paginate(2, true);

        $this->assertCount(200, $first->items());
        $this->assertTrue($first->hasMorePages());
        $this->assertCount(5, $second->items());
        $this->assertFalse($second->hasMorePages());
        $this->assertGreaterThan(
            collect($first->items())->max('id'),
            collect($second->items())->min('id'),
        );
    }

    private function paginate(int $page, bool $simple): \Illuminate\Contracts\Pagination\Paginator
    {
        Paginator::currentPageResolver(fn (): int => $page);

        return app(EmpodatSearchPaginator::class)
            ->paginate(EmpodatMain::query()->byFiles([$this->wantedFileId]), $simple);
    }
}

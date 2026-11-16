<?php

declare(strict_types=1);

namespace Tests\Feature\Empodat;

use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTimingTest extends TestCase
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

    private function makeLog(string $databaseKey = 'empodat'): QueryLog
    {
        return QueryLog::query()->create([
            'database_key' => $databaseKey,
            'content' => json_encode(['countExecutionTime' => '0.01 s']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function content(QueryLog $log): array
    {
        return json_decode((string) $log->fresh()->content, true);
    }

    public function test_it_stores_the_times_for_a_fresh_empodat_row(): void
    {
        $log = $this->makeLog();

        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 1234, 'page_ms' => 2500])
            ->assertNoContent();

        $content = $this->content($log);
        $this->assertSame('1.23 s', $content['dataTime']);
        $this->assertSame('2.50 s', $content['pageTime']);
        $this->assertSame('0.01 s', $content['countExecutionTime']);
    }

    public function test_second_post_does_not_overwrite_the_times(): void
    {
        $log = $this->makeLog();

        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 1000, 'page_ms' => 2000])->assertNoContent();
        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 5000, 'page_ms' => 9000])->assertNoContent();

        $content = $this->content($log);
        $this->assertSame('1.00 s', $content['dataTime']);
        $this->assertSame('2.00 s', $content['pageTime']);
    }

    public function test_it_ignores_a_non_empodat_row(): void
    {
        $log = $this->makeLog('susdat');

        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 1000, 'page_ms' => 2000])->assertNoContent();

        $this->assertArrayNotHasKey('dataTime', $this->content($log));
    }

    public function test_it_rejects_invalid_values(): void
    {
        $log = $this->makeLog();

        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 'abc', 'page_ms' => 10])->assertStatus(422);
        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 3600001, 'page_ms' => 3600001])->assertStatus(422);
        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 2000, 'page_ms' => 1000])->assertStatus(422);
        $this->postJson(route('codsearch.timing', $log), ['data_ms' => 100])->assertStatus(422);

        $this->assertArrayNotHasKey('dataTime', $this->content($log));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\EmpodatLegacyBackfill;

use Database\Seeders\EmpodatLegacy\Backfill\BackfillOptions;
use Database\Seeders\EmpodatLegacy\Backfill\ExecutionGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExecutionGuardTest extends TestCase
{
    public function test_default_options_are_a_dry_run_that_never_writes(): void
    {
        $options = $this->options([]);
        $identity = $this->identity('nds');

        $this->assertSame(BackfillOptions::MODE_DRY_RUN, $options->mode);
        $this->assertFalse($options->executeFlag);
        $this->assertFalse(ExecutionGuard::writesAllowed($options, $identity));
        $this->assertFalse(ExecutionGuard::mustAbort($options, $identity));
    }

    public function test_execute_flag_alone_does_not_enable_writing(): void
    {
        $options = $this->options(['execute' => 'true', 'expect_pg_database' => 'nds']);
        $identity = $this->identity('nds');

        $this->assertFalse(ExecutionGuard::writesAllowed($options, $identity));
        $this->assertFalse(ExecutionGuard::mustAbort($options, $identity));
        $this->assertStringContainsString('dry-run', implode(' ', ExecutionGuard::violations($options, $identity)));
    }

    public function test_execute_mode_without_the_flag_aborts(): void
    {
        $options = $this->options(['mode' => 'execute', 'expect_pg_database' => 'nds']);
        $identity = $this->identity('nds');

        $this->assertFalse(ExecutionGuard::writesAllowed($options, $identity));
        $this->assertTrue(ExecutionGuard::mustAbort($options, $identity));
        $this->assertStringContainsString('LEGACY_BACKFILL_EXECUTE', implode(' ', ExecutionGuard::violations($options, $identity)));
    }

    public function test_execute_mode_without_an_expected_database_aborts(): void
    {
        $options = $this->options(['mode' => 'execute', 'execute' => true]);

        $this->assertTrue(ExecutionGuard::mustAbort($options, $this->identity('nds')));
        $this->assertStringContainsString('EXPECT_PG_DATABASE', implode(' ', ExecutionGuard::violations($options, $this->identity('nds'))));
    }

    public function test_execute_mode_against_the_wrong_database_aborts(): void
    {
        $options = $this->options(['mode' => 'execute', 'execute' => 'true', 'expect_pg_database' => 'nds']);
        $identity = $this->identity('nds_staging');

        $this->assertFalse(ExecutionGuard::writesAllowed($options, $identity));
        $this->assertTrue(ExecutionGuard::mustAbort($options, $identity));
        $this->assertStringContainsString('"nds_staging"', implode(' ', ExecutionGuard::violations($options, $identity)));
    }

    public function test_execute_mode_with_every_guard_satisfied_may_write(): void
    {
        $options = $this->options(['mode' => 'execute', 'execute' => 'true', 'expect_pg_database' => 'nds']);
        $identity = $this->identity('nds');

        $this->assertTrue(ExecutionGuard::writesAllowed($options, $identity));
        $this->assertFalse(ExecutionGuard::mustAbort($options, $identity));
        $this->assertSame([], ExecutionGuard::violations($options, $identity));
    }

    public function test_the_expected_host_must_match_the_configured_host_or_the_server_address(): void
    {
        $base = ['mode' => 'execute', 'execute' => 'true', 'expect_pg_database' => 'nds'];
        $identity = ['database' => 'nds', 'host' => 'db.internal', 'addr' => '10.0.0.2'];

        $this->assertTrue(ExecutionGuard::writesAllowed($this->options($base + ['expect_pg_host' => 'db.internal']), $identity));
        $this->assertTrue(ExecutionGuard::writesAllowed($this->options($base + ['expect_pg_host' => '10.0.0.2']), $identity));
        $this->assertFalse(ExecutionGuard::writesAllowed($this->options($base + ['expect_pg_host' => 'other.host']), $identity));
    }

    public function test_an_unknown_live_database_name_blocks_writing(): void
    {
        $options = $this->options(['mode' => 'execute', 'execute' => 'true', 'expect_pg_database' => 'nds']);

        $this->assertFalse(ExecutionGuard::writesAllowed($options, ['database' => null, 'host' => '', 'addr' => null]));
    }

    public function test_unknown_mode_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->options(['mode' => 'yolo']);
    }

    public function test_environment_variables_override_config_values(): void
    {
        $env = ['LEGACY_BACKFILL_MODE' => 'verify', 'LEGACY_BACKFILL_CHUNK_ROWS' => '2000'];
        $options = BackfillOptions::fromEnvironment(
            ['mode' => 'dry-run', 'chunk_rows' => 5000, 'report_directory' => '/tmp/reports'],
            static fn (string $name): string|false => $env[$name] ?? false,
        );

        $this->assertSame('verify', $options->mode);
        $this->assertSame(2000, $options->chunkRows);
    }

    public function test_numeric_options_are_clamped_and_aliases_have_safe_defaults(): void
    {
        $options = $this->options(['bucket_size' => 5, 'chunk_rows' => 10_000_000, 'scan_window' => 1]);

        $this->assertSame(1000, $options->bucketSize);
        $this->assertSame(50_000, $options->chunkRows);
        $this->assertSame(10_000, $options->scanWindow);
        $this->assertArrayHasKey('empodat_matrix_biota.packing_material', $options->columnAliases);
        $this->assertNull($options->columnAliases['empodat_matrix_biota.packing_material']);
    }

    public function test_alias_string_overrides_a_default(): void
    {
        $options = $this->options(['column_aliases' => 'empodat_matrix_water_surface.ocean_sea_region_name=sea_region_name; x.y=']);

        $this->assertSame('sea_region_name', $options->columnAliases['empodat_matrix_water_surface.ocean_sea_region_name']);
        $this->assertNull($options->columnAliases['x.y']);
    }

    public function test_table_filter(): void
    {
        $options = $this->options(['only_tables' => 'empodat_minor, empodat_matrix_air']);

        $this->assertTrue($options->includesTable('empodat_minor'));
        $this->assertFalse($options->includesTable('empodat_matrix_biota'));
        $this->assertTrue($this->options([])->includesTable('empodat_matrix_biota'));
    }

    public function test_dry_run_is_limited_by_default_and_execute_is_never_row_capped(): void
    {
        $dryRun = $this->options([]);
        $execute = $this->options(['mode' => 'execute', 'dry_run_max_rows' => 10]);
        $unlimited = $this->options(['dry_run_max_rows' => '0']);

        $this->assertSame(BackfillOptions::DEFAULT_DRY_RUN_MAX_ROWS, $dryRun->rowCap());
        $this->assertSame(0, $execute->rowCap());
        $this->assertSame(0, $unlimited->rowCap());
        $this->assertSame(BackfillOptions::DEFAULT_DRY_RUN_MAX_ROWS, $this->options(['dry_run_max_rows' => ''])->rowCap());
        $this->assertSame(0, $this->options(['mode' => 'verify'])->rowCap());
    }

    public function test_sample_rows_are_clamped(): void
    {
        $this->assertSame(BackfillOptions::DEFAULT_SAMPLE_ROWS, $this->options([])->sampleRows);
        $this->assertSame(50, $this->options(['sample_rows' => 1000])->sampleRows);
        $this->assertSame(0, $this->options(['sample_rows' => -3])->sampleRows);
    }

    public function test_id_window_is_parsed_and_clamps_ranges(): void
    {
        $options = $this->options(['id_from' => '12969000', 'id_to' => '12970999']);

        $this->assertTrue($options->hasWindow());
        $this->assertSame('ids 12969000 to 12970999', $options->windowLabel());
        $this->assertSame([12_969_000, 12_971_000], $options->clampHalfOpen(12_000_000, 13_000_000));
        $this->assertNull($options->clampHalfOpen(13_000_000, 14_000_000));

        $open = $this->options(['id_from' => '163000000']);
        $this->assertSame([163_000_000, 164_000_000], $open->clampHalfOpen(163_000_000, 164_000_000));
        $this->assertSame([163_500_000, 164_000_000], $this->options(['id_from' => 163_500_000])->clampHalfOpen(163_000_000, 164_000_000));
        $this->assertNull($open->clampHalfOpen(1_000_000, 2_000_000));

        $none = $this->options(['id_from' => '', 'id_to' => null]);
        $this->assertFalse($none->hasWindow());
        $this->assertSame('whole id space', $none->windowLabel());
        $this->assertSame([5, 9], $none->clampHalfOpen(5, 9));
    }

    public function test_an_inverted_window_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->options(['id_from' => 20, 'id_to' => 10]);
    }

    public function test_a_non_numeric_window_bound_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->options(['id_to' => 'abc']);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function options(array $config): BackfillOptions
    {
        return BackfillOptions::fromArray($config + ['report_directory' => '/tmp/reports']);
    }

    /**
     * @return array{database: ?string, host: ?string, addr: ?string}
     */
    private function identity(string $database): array
    {
        return ['database' => $database, 'host' => 'db.internal', 'addr' => '10.0.0.2'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectPrioritisationBuild;
use App\Models\User;
use App\Services\EmpodatSuspect\PrioritisationCoverage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The "Prioritisation rows" column on the Uploaded DCT Files page, and the
 * service behind it.
 *
 * Two separate permissions meet on this page and must not be conflated:
 * reading how much data a file contributes is open to any admin, while
 * triggering a rebuild stays super_admin only. A regression that merges them
 * either hides useful information from admins or hands them a production job
 * trigger.
 */
class PrioritisationCoverageColumnTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseEntity $suspectEntity;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // The controller resolves the module by code, not by a hardcoded id.
        $this->suspectEntity = DatabaseEntity::factory()->create(['code' => 'empodat_suspect']);
    }

    /**
     * `id` is not in File::$fillable, so File::create() silently discards it
     * and the row lands on the sequence id instead. Every coverage lookup here
     * keys on the reserved 100xx id, so the id has to be forced.
     */
    private function suspectFile(int $id, string $name = 'DCT source.xlsx'): File
    {
        return File::forceCreate([
            'id' => $id,
            'name' => $name,
            'original_name' => $name,
            'database_entity_id' => $this->suspectEntity->id,
        ]);
    }

    private function build(int $fileId, string $status, ?int $rowCount, ?int $durationMs = 38123): EmpodatSuspectPrioritisationBuild
    {
        return EmpodatSuspectPrioritisationBuild::create([
            'file_id' => $fileId,
            'status' => $status,
            'row_count' => $rowCount,
            'duration_ms' => $durationMs,
            'started_at' => now()->subMinutes(5),
            'finished_at' => $status === EmpodatSuspectPrioritisationBuild::STATUS_RUNNING ? null : now(),
            'triggered_by' => 'cli',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function indexUrl(?int $entityId = null): string
    {
        return route('files.index', ['database_entity_id' => $entityId ?? $this->suspectEntity->id]);
    }

    public function test_admin_sees_the_coverage_column_but_not_the_rebuild_button(): void
    {
        $this->suspectFile(10009);
        $this->build(10009, EmpodatSuspectPrioritisationBuild::STATUS_SUCCESS, 1053111);

        $response = $this->actingAs($this->userWithRole('admin'))->get($this->indexUrl());

        $response->assertOk();
        $response->assertSee('Prioritisation rows');
        // House formatting: space as the thousand separator.
        $response->assertSee('1 053 111');
        $response->assertDontSee('Queue empodat-suspect:refresh-prioritisation --file=10009');
        // Build duration is super_admin-only operational detail.
        $response->assertDontSee('38.1 s');
    }

    public function test_super_admin_sees_both_the_coverage_column_and_the_rebuild_button(): void
    {
        $this->suspectFile(10009);
        $this->build(10009, EmpodatSuspectPrioritisationBuild::STATUS_SUCCESS, 1053111);

        $response = $this->actingAs($this->userWithRole('super_admin'))->get($this->indexUrl());

        $response->assertOk();
        $response->assertSee('Prioritisation rows');
        $response->assertSee('1 053 111');
        $response->assertSee('Queue empodat-suspect:refresh-prioritisation --file=10009');
        $response->assertSee('38.1 s');
    }

    public function test_duration_is_not_shown_when_the_only_build_failed(): void
    {
        $this->suspectFile(10013);
        // A failed attempt has a duration too — how long it took to break.
        // Reporting it next to a row count it did not produce would be a lie.
        $this->build(10013, EmpodatSuspectPrioritisationBuild::STATUS_FAILED, null, 26);

        $response = $this->actingAs($this->userWithRole('super_admin'))->get($this->indexUrl());

        $response->assertOk();
        $response->assertSee('No successful build');
        $response->assertDontSee('0.0 s');
    }

    public function test_user_without_an_admin_role_does_not_see_the_coverage_column(): void
    {
        $this->suspectFile(10009);
        $this->build(10009, EmpodatSuspectPrioritisationBuild::STATUS_SUCCESS, 1053111);

        $response = $this->actingAs(User::factory()->create())->get($this->indexUrl());

        $response->assertOk();
        $response->assertDontSee('Prioritisation rows');
        $response->assertDontSee('1 053 111');
    }

    public function test_coverage_column_is_absent_for_other_database_entities(): void
    {
        $otherEntity = DatabaseEntity::factory()->create(['code' => 'empodat']);
        File::forceCreate([
            'id' => 501,
            'name' => 'Other.xlsx',
            'database_entity_id' => $otherEntity->id,
        ]);

        $response = $this->actingAs($this->userWithRole('admin'))->get($this->indexUrl($otherEntity->id));

        $response->assertOk();
        $response->assertDontSee('Prioritisation rows');
    }

    public function test_file_with_no_build_row_reads_never_built(): void
    {
        $this->suspectFile(10016);

        $response = $this->actingAs($this->userWithRole('admin'))->get($this->indexUrl());

        $response->assertOk();
        $response->assertSee('Never built');
    }

    public function test_file_whose_every_build_failed_reads_no_successful_build(): void
    {
        $this->suspectFile(10013);
        $this->build(10013, EmpodatSuspectPrioritisationBuild::STATUS_FAILED, null);

        $response = $this->actingAs($this->userWithRole('admin'))->get($this->indexUrl());

        $response->assertOk();
        $response->assertSee('No successful build');
    }

    public function test_coverage_reports_the_last_success_and_flags_a_later_failure_as_stale(): void
    {
        $this->build(10012, EmpodatSuspectPrioritisationBuild::STATUS_SUCCESS, 7946089, 151791);
        $this->build(10012, EmpodatSuspectPrioritisationBuild::STATUS_FAILED, null, 26);

        $coverage = app(PrioritisationCoverage::class)->forFiles([10012]);

        // The count and the duration both survive a later failed attempt — the
        // partition still holds those rows, built in that time — but the file
        // is flagged so nobody reads it as current.
        $this->assertSame(7946089, $coverage[10012]['row_count']);
        $this->assertSame(151791, $coverage[10012]['duration_ms']);
        $this->assertSame(EmpodatSuspectPrioritisationBuild::STATUS_FAILED, $coverage[10012]['latest_status']);
        $this->assertTrue($coverage[10012]['is_stale']);
    }

    public function test_a_killed_run_left_in_running_is_reported_as_stale_not_as_current(): void
    {
        // Exactly the production case: file 10012 sat in `running` for twelve
        // days after the process was killed, because the terminal status is
        // written from PHP and never ran.
        $this->build(10012, EmpodatSuspectPrioritisationBuild::STATUS_RUNNING, null);

        $coverage = app(PrioritisationCoverage::class)->forFiles([10012]);

        $this->assertNull($coverage[10012]['row_count']);
        $this->assertTrue($coverage[10012]['is_stale']);
    }

    public function test_coverage_omits_files_that_have_no_build_history(): void
    {
        $coverage = app(PrioritisationCoverage::class)->forFiles([10016]);

        $this->assertArrayNotHasKey(10016, $coverage);
    }

    public function test_coverage_short_circuits_on_an_empty_file_list(): void
    {
        $this->assertSame([], app(PrioritisationCoverage::class)->forFiles([]));
    }
}

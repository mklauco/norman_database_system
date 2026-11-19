<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Actions\EmpodatSuspect\QueueEmpodatSuspectCommand;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaleCommandRunsTest extends TestCase
{
    use RefreshDatabase;

    private function makeRun(string $status, ?\DateTimeInterface $queuedAt = null, ?\DateTimeInterface $startedAt = null): EmpodatSuspectCommandRun
    {
        return EmpodatSuspectCommandRun::create([
            'command_key' => 'refresh_prioritisation',
            'arguments' => [],
            'queued_at' => $queuedAt ?? now(),
            'started_at' => $startedAt,
            'status' => $status,
        ]);
    }

    private function insertQueuedJob(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'exports',
            'payload' => '{"displayName":"App\\\\Jobs\\\\EmpodatSuspect\\\\RunEmpodatSuspectCommandJob"}',
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
    }

    public function test_old_running_run_is_closed(): void
    {
        $run = $this->makeRun('running', now()->subHours(3), now()->subHours(3));

        $this->assertFalse(app(QueueEmpodatSuspectCommand::class)->hasActiveRun());

        $run->refresh();
        $this->assertSame('failed', $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertStringContainsString('Run interrupted', $run->output);
    }

    public function test_fresh_running_run_is_kept(): void
    {
        $run = $this->makeRun('running', now()->subMinutes(5), now()->subMinutes(5));

        $this->assertTrue(app(QueueEmpodatSuspectCommand::class)->hasActiveRun());
        $this->assertSame('running', $run->refresh()->status);
    }

    public function test_old_queued_run_without_queued_job_is_closed(): void
    {
        $run = $this->makeRun('queued', now()->subMinutes(30));

        $this->assertFalse(app(QueueEmpodatSuspectCommand::class)->hasActiveRun());
        $this->assertSame('failed', $run->refresh()->status);
    }

    public function test_old_queued_run_with_queued_job_is_kept(): void
    {
        $run = $this->makeRun('queued', now()->subMinutes(30));
        $this->insertQueuedJob();

        $this->assertTrue(app(QueueEmpodatSuspectCommand::class)->hasActiveRun());
        $this->assertSame('queued', $run->refresh()->status);
    }

    public function test_recent_queued_run_is_kept(): void
    {
        $run = $this->makeRun('queued', now()->subMinutes(2));

        $this->assertTrue(app(QueueEmpodatSuspectCommand::class)->hasActiveRun());
        $this->assertSame('queued', $run->refresh()->status);
    }

    public function test_command_exits_zero_without_active_runs(): void
    {
        $this->makeRun('running', now()->subHours(3), now()->subHours(3));

        $this->artisan('empodat-suspect:check-active-runs')->assertExitCode(0);
    }

    public function test_command_exits_two_with_active_runs(): void
    {
        $this->makeRun('running', now()->subMinutes(5), now()->subMinutes(5));

        $this->artisan('empodat-suspect:check-active-runs')->assertExitCode(2);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\BackupStatus;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardBackupStatusTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'http://uptime-kuma:3001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'services.backup_status.url' => self::BASE_URL,
            'services.backup_status.slug' => 'nds-backups',
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-10-20 12:00:00', 'UTC'));

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_admin_sees_the_backup_status(): void
    {
        $this->fakeKuma();

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Backups')
            ->assertSee('Last backup: 2026-10-20 01:42, 4.2 GB, OK');
    }

    public function test_super_admin_sees_the_backup_status(): void
    {
        $this->fakeKuma();

        $this->actingAs($this->userWithRole('super_admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Backups');
    }

    public function test_user_without_an_admin_role_does_not_see_it_and_kuma_is_not_called(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Last backup');

        Http::assertNothingSent();
    }

    public function test_unreachable_kuma_does_not_break_the_dashboard(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Backup status unavailable');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function fakeKuma(): void
    {
        Http::fake([
            self::BASE_URL.'/api/status-page/heartbeat/nds-backups' => Http::response($this->fixture('heartbeats_all_ok.json')),
            self::BASE_URL.'/api/status-page/nds-backups' => Http::response($this->fixture('status_page.json')),
        ]);
    }

    /**
     * @return array<mixed>
     */
    private function fixture(string $name): array
    {
        $json = file_get_contents(base_path('tests/Fixtures/backup_status/'.$name));

        return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }
}

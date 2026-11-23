<?php

declare(strict_types=1);

namespace Tests\Feature\Backend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsageStatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_roles_can_open_the_pages(): void
    {
        $plain = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $this->actingAs($plain)->get(route('usage-statistics.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('usage-statistics.export', ['table' => 'users']))->assertForbidden();

        foreach (['usage-statistics.index', 'usage-statistics.users', 'usage-statistics.logins', 'usage-statistics.guest'] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_export_returns_an_xlsx_file(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($admin)
            ->get(route('usage-statistics.export', ['table' => 'searches', 'year' => 'all']))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=usage_searches_all.xlsx');
    }
}

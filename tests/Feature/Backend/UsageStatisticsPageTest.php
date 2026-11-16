<?php

declare(strict_types=1);

namespace Tests\Feature\Backend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class UsageStatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(?string $role): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }

        return $user;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function pageUrls(): array
    {
        return [
            'overview' => ['usage-statistics.index'],
            'users' => ['usage-statistics.users'],
            'guest' => ['usage-statistics.guest'],
            'logins' => ['usage-statistics.logins'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function exportTables(): array
    {
        return [
            'searches' => ['searches'],
            'downloads' => ['downloads'],
            'users' => ['users'],
            'logins-month' => ['logins-month'],
            'logins-geo' => ['logins-geo'],
            'logins-profile' => ['logins-profile'],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('usage-statistics.index'))->assertRedirect(route('login'));
        $this->get(route('usage-statistics.export', ['table' => 'searches']))->assertRedirect(route('login'));
    }

    #[DataProvider('pageUrls')]
    public function test_user_without_role_is_forbidden(string $routeName): void
    {
        $this->actingAs($this->userWithRole(null))->get(route($routeName))->assertForbidden();
    }

    public function test_user_without_role_cannot_see_user_page_or_export(): void
    {
        $user = $this->userWithRole(null);

        $this->actingAs($user)->get(route('usage-statistics.users.show', $user))->assertForbidden();
        $this->actingAs($user)->get(route('usage-statistics.export', ['table' => 'searches']))->assertForbidden();
    }

    #[DataProvider('pageUrls')]
    public function test_admin_roles_can_open_pages(string $routeName): void
    {
        foreach (['super_admin', 'admin'] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route($routeName))->assertOk();
        }
    }

    public function test_admin_can_open_per_user_page(): void
    {
        $subject = User::factory()->create();

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('usage-statistics.users.show', $subject))
            ->assertOk()
            ->assertSee($subject->first_name);
    }

    public function test_year_parameter_is_accepted(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))
            ->get(route('usage-statistics.index', ['year' => (int) date('Y')]))
            ->assertOk();
    }

    public function test_invalid_year_is_rejected(): void
    {
        $admin = $this->userWithRole('super_admin');

        foreach (['abc', '1999', (string) ((int) date('Y') + 2)] as $year) {
            $this->actingAs($admin)
                ->from(route('usage-statistics.index'))
                ->get(route('usage-statistics.index', ['year' => $year]))
                ->assertRedirect(route('usage-statistics.index'))
                ->assertSessionHasErrors('year');
        }
    }

    public function test_export_rejects_unknown_user(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('usage-statistics.export', ['table' => 'searches', 'user' => 999999]))
            ->assertSessionHasErrors('user');
    }

    #[DataProvider('exportTables')]
    public function test_export_returns_xlsx_download(string $table): void
    {
        $response = $this->actingAs($this->userWithRole('admin'))
            ->get(route('usage-statistics.export', ['table' => $table, 'year' => (int) date('Y')]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString(
            'usage_'.str_replace('-', '_', $table).'_'.date('Y').'.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_export_for_a_user_and_for_guests_names_the_file_accordingly(): void
    {
        $admin = $this->userWithRole('admin');
        $subject = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('usage-statistics.export', ['table' => 'searches', 'user' => $subject->id, 'year' => 2026]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=usage_searches_2026_user_'.$subject->id.'.xlsx');

        $this->actingAs($admin)
            ->get(route('usage-statistics.export', ['table' => 'downloads', 'guest' => 1, 'year' => 2026]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=usage_downloads_2026_guest.xlsx');
    }

    public function test_unknown_export_table_returns_not_found(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))
            ->get('/backend/usage-statistics/export/nonsense')
            ->assertNotFound();
    }
}

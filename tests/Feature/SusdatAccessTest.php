<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Susdat\Substance;
use App\Models\User;
use Database\Factories\Susdat\SubstanceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SusdatAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function userWithRole(?string $role): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }

        return $user;
    }

    private function makeSubstance(): Substance
    {
        return SubstanceFactory::new()->create();
    }

    /**
     * Resolve a URL for a restricted route. Substance-dependent URLs use a real factory substance.
     */
    private function restrictedUrl(string $name): string
    {
        $substance = $this->makeSubstance();

        return match ($name) {
            'duplicates.filter' => route('duplicates.filter', ['pivot_id' => 'cas_number']),
            'duplicates.records' => route('duplicates.records', ['pivot' => 'cas_number', 'pivot_value' => $substance->cas_number]),
            'duplicates.mergeHistory' => route('duplicates.mergeHistory'),
            'duplicates.index' => route('duplicates.index'),
            'substances.create' => route('substances.create'),
            'substances.edit' => route('substances.edit', $substance->id),
            'substances.audited' => route('substances.audited'),
            'substances.audits' => route('substances.audits', $substance->id),
            'duplicates.handleDuplicates' => route('duplicates.handleDuplicates'),
            'duplicates.restore' => route('duplicates.restore', 1),
            'substances.store' => route('substances.store'),
            'substances.update' => route('substances.update', $substance->id),
            'substances.destroy' => route('substances.destroy', $substance->id),
            'duplicates.store' => route('duplicates.store'),
            'duplicates.update' => route('duplicates.update', 1),
            'duplicates.destroy' => route('duplicates.destroy', 1),
        };
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function restrictedGetRoutes(): array
    {
        return [
            'duplicates filter' => ['duplicates.filter'],
            'duplicates records' => ['duplicates.records'],
            'duplicates merge history' => ['duplicates.mergeHistory'],
            'duplicates index' => ['duplicates.index'],
            'substances create' => ['substances.create'],
            'substances edit' => ['substances.edit'],
            'substances audited' => ['substances.audited'],
            'substance audits' => ['substances.audits'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function restrictedWriteRoutes(): array
    {
        return [
            'duplicates handle POST' => ['post', 'duplicates.handleDuplicates'],
            'duplicates restore POST' => ['post', 'duplicates.restore'],
            'substances store POST' => ['post', 'substances.store'],
            'substances update PUT' => ['put', 'substances.update'],
            'substances destroy DELETE' => ['delete', 'substances.destroy'],
            'duplicates store POST' => ['post', 'duplicates.store'],
            'duplicates update PUT' => ['put', 'duplicates.update'],
            'duplicates destroy DELETE' => ['delete', 'duplicates.destroy'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedRoles(): array
    {
        return [
            'susdat' => ['susdat'],
            'admin' => ['admin'],
            'super_admin' => ['super_admin'],
        ];
    }

    #[DataProvider('restrictedGetRoutes')]
    public function test_guest_is_redirected_to_login_on_restricted_get_routes(string $routeName): void
    {
        $this->get($this->restrictedUrl($routeName))->assertRedirect(route('login'));
    }

    #[DataProvider('restrictedGetRoutes')]
    public function test_user_without_role_gets_403_on_restricted_get_routes(string $routeName): void
    {
        $this->actingAs($this->userWithRole(null))
            ->get($this->restrictedUrl($routeName))
            ->assertForbidden();
    }

    #[DataProvider('restrictedGetRoutes')]
    public function test_user_with_unrelated_role_gets_403_on_restricted_get_routes(string $routeName): void
    {
        $this->actingAs($this->userWithRole('ecotox'))
            ->get($this->restrictedUrl($routeName))
            ->assertForbidden();
    }

    #[DataProvider('allowedRoles')]
    public function test_allowed_roles_can_open_every_restricted_get_route(string $role): void
    {
        $user = $this->userWithRole($role);

        foreach (array_keys(self::restrictedGetRoutes()) as $label) {
            $routeName = self::restrictedGetRoutes()[$label][0];
            $response = $this->actingAs($user)->get($this->restrictedUrl($routeName));

            $this->assertNotSame(403, $response->getStatusCode(), "{$routeName} returned 403 for {$role}");
            $response->assertOk();
        }
    }

    #[DataProvider('restrictedWriteRoutes')]
    public function test_guest_is_redirected_to_login_on_restricted_write_routes(string $method, string $routeName): void
    {
        $this->{$method}($this->restrictedUrl($routeName))->assertRedirect(route('login'));
    }

    #[DataProvider('restrictedWriteRoutes')]
    public function test_user_without_role_gets_403_on_restricted_write_routes(string $method, string $routeName): void
    {
        $this->actingAs($this->userWithRole(null))
            ->{$method}($this->restrictedUrl($routeName))
            ->assertForbidden();
    }

    #[DataProvider('restrictedWriteRoutes')]
    public function test_user_with_unrelated_role_gets_403_on_restricted_write_routes(string $method, string $routeName): void
    {
        $this->actingAs($this->userWithRole('ecotox'))
            ->{$method}($this->restrictedUrl($routeName))
            ->assertForbidden();
    }

    public function test_public_susdat_pages_stay_public_for_guests(): void
    {
        $substance = $this->makeSubstance();

        $this->get(route('substances.index'))->assertOk();
        $this->get(route('substances.show', $substance->id))->assertOk();
        $this->get(route('substances.search.filter'))->assertOk();
        $this->get(route('susdat.batch.index'))->assertOk();
    }
}

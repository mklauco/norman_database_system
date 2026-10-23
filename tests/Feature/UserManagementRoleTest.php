<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'user', 'user_manager', 'empodat', 'server_payment_admin', 'server_payment_viewer'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }

    public function test_user_manager_can_not_create_a_super_admin(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);

        $response = $this->actingAs($manager)->post(route('users.store'), $this->payload([
            'email' => 'new@example.com',
            'roles' => ['user', 'super_admin'],
        ]));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_user_manager_can_not_grant_super_admin_or_admin_on_update(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);
        $target = $this->userWithRoles(['user']);

        foreach (['super_admin', 'admin'] as $role) {
            $response = $this->actingAs($manager)->put(route('users.update', $target->id), $this->payload([
                'email' => $target->email,
                'roles' => ['user', $role],
            ]));

            $response->assertSessionHas('error');
            $this->assertFalse($target->fresh()->hasRole($role));
        }
    }

    public function test_user_manager_can_not_grant_super_admin_to_themselves(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);

        $this->actingAs($manager)->put(route('users.update', $manager->id), $this->payload([
            'email' => $manager->email,
            'roles' => ['user', 'user_manager', 'super_admin'],
        ]));

        $this->assertFalse($manager->fresh()->hasRole('super_admin'));
    }

    public function test_user_manager_can_not_edit_a_super_admin_account(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);
        $superAdmin = $this->userWithRoles(['user', 'super_admin']);

        $this->actingAs($manager)->get(route('users.edit', $superAdmin->id))->assertForbidden();

        $this->actingAs($manager)->put(route('users.update', $superAdmin->id), $this->payload([
            'email' => 'attacker@example.com',
            'roles' => ['user'],
        ]))->assertForbidden();

        $superAdmin->refresh();
        $this->assertNotSame('attacker@example.com', $superAdmin->email);
        $this->assertTrue($superAdmin->hasRole('super_admin'));
    }

    public function test_admin_can_not_grant_super_admin_or_edit_a_super_admin(): void
    {
        $admin = $this->userWithRoles(['user', 'admin']);
        $target = $this->userWithRoles(['user']);
        $superAdmin = $this->userWithRoles(['user', 'super_admin']);

        $this->actingAs($admin)->put(route('users.update', $target->id), $this->payload([
            'email' => $target->email,
            'roles' => ['user', 'super_admin'],
        ]))->assertSessionHas('error');
        $this->assertFalse($target->fresh()->hasRole('super_admin'));

        $this->actingAs($admin)->get(route('users.edit', $superAdmin->id))->assertForbidden();
    }

    public function test_user_manager_can_assign_ordinary_roles(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);
        $target = $this->userWithRoles(['user']);

        $response = $this->actingAs($manager)->put(route('users.update', $target->id), $this->payload([
            'email' => $target->email,
            'roles' => ['user', 'empodat'],
        ]));

        $response->assertSessionMissing('error');
        $this->assertTrue($target->fresh()->hasRole('empodat'));
    }

    public function test_admin_can_not_edit_an_account_holding_a_role_admin_can_not_assign(): void
    {
        $admin = $this->userWithRoles(['user', 'admin']);
        $target = $this->userWithRoles(['user', 'server_payment_viewer']);

        $this->actingAs($admin)->get(route('users.edit', $target->id))->assertForbidden();
    }

    public function test_super_admin_can_grant_super_admin(): void
    {
        $superAdmin = $this->userWithRoles(['user', 'super_admin']);
        $target = $this->userWithRoles(['user']);

        $this->actingAs($superAdmin)->put(route('users.update', $target->id), $this->payload([
            'email' => $target->email,
            'roles' => ['user', 'super_admin'],
        ]))->assertSessionMissing('error');

        $this->assertTrue($target->fresh()->hasRole('super_admin'));
    }

    public function test_edit_form_lists_only_roles_the_actor_can_assign(): void
    {
        $manager = $this->userWithRoles(['user', 'user_manager']);
        $target = $this->userWithRoles(['user']);

        $response = $this->actingAs($manager)->get(route('users.edit', $target->id));

        $response->assertOk();
        $response->assertSee('value="empodat"', false);
        $response->assertDontSee('value="super_admin"', false);
        $response->assertDontSee('value="admin"', false);
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function userWithRoles(array $roles): User
    {
        $user = User::factory()->create();
        $user->syncRoles($roles);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'roles' => ['user'],
            'projects' => [],
            'active' => '1',
        ], $overrides);
    }
}

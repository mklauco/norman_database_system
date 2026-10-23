<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for which roles an account manager may hand out.
 * super_admin assigns every role; admin everything except super_admin and the
 * server payment roles; anyone else (user_manager) additionally not admin.
 */
class UserRoleAssignment
{
    public const BASE_ROLE = 'user';

    /**
     * @var array<int, string>
     */
    private const RESTRICTED_FOR_ADMIN = ['super_admin', 'server_payment_admin', 'server_payment_viewer'];

    /**
     * @var array<int, string>
     */
    private const RESTRICTED_FOR_OTHERS = ['super_admin', 'admin', 'server_payment_admin', 'server_payment_viewer'];

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function assignableRoleNames(User $actor): Collection
    {
        $names = Role::query()->orderBy('name')->pluck('name');

        if ($actor->hasRole('super_admin')) {
            return $names;
        }

        $restricted = $actor->hasRole('admin') ? self::RESTRICTED_FOR_ADMIN : self::RESTRICTED_FOR_OTHERS;

        return $names->reject(fn (string $name): bool => in_array($name, $restricted, true))->values();
    }

    /**
     * An actor may manage only accounts whose every role they could assign themselves,
     * so nobody can take over (e.g. change the email of) an account above their level.
     */
    public function canManage(User $actor, User $target): bool
    {
        $assignable = $this->assignableRoleNames($actor);

        return $target->getRoleNames()->every(fn (string $name): bool => $assignable->contains($name));
    }

    /**
     * Requested roles the actor is not allowed to grant.
     *
     * @param  array<int, string>  $requested
     * @return array<int, string>
     */
    public function forbiddenRequestedRoles(User $actor, array $requested): array
    {
        $assignable = $this->assignableRoleNames($actor);

        return collect($requested)
            ->reject(fn (string $name): bool => $name === self::BASE_ROLE || $assignable->contains($name))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Roles to sync: the requested roles the actor may assign, plus the mandatory base role.
     *
     * @param  array<int, string>  $requested
     * @return array<int, string>
     */
    public function rolesToSync(User $actor, array $requested): array
    {
        $assignable = $this->assignableRoleNames($actor);

        return collect($requested)
            ->filter(fn (string $name): bool => $assignable->contains($name))
            ->push(self::BASE_ROLE)
            ->unique()
            ->values()
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\UserRoleAssignment;

class UserPolicy
{
    public function __construct(private readonly UserRoleAssignment $roleAssignment) {}

    /**
     * Determine whether the actor can edit the target account in user management.
     */
    public function update(User $actor, User $target): bool
    {
        return $this->roleAssignment->canManage($actor, $target);
    }
}

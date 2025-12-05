<?php

declare(strict_types=1);

namespace App\Security\Permission;

use App\Entity\Role;
use App\Entity\User;

class PermissionRegistry
{
    public function catalog(): array
    {
        return array_map(fn (PermissionEnum $enum) => $enum->value, PermissionEnum::cases());
    }

    public function resolve(User $user): array
    {
        $resolved = [];

        foreach ($user->getRoleEntities() as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            foreach ($role->getPermissions() as $permission) {
                $resolved[$permission->getName()] = true;
            }
        }

        return $resolved;
    }

    public function has(User $user, string $permission): bool
    {
        $resolved = $this->resolve($user);

        return $resolved[$permission] ?? false;
    }
}

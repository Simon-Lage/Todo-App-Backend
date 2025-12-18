<?php

declare(strict_types=1);

namespace App\Role\View;

use App\Entity\Role;
use App\Security\Permission\PermissionEnum;

final class RoleViewFactory
{
    public function make(Role $role): array
    {
        $payload = [
            'id' => $role->getId()?->toRfc4122(),
            'name' => $role->getName(),
        ];

        $assigned = [];
        foreach ($role->getPermissions() as $permission) {
            $name = $permission->getName();
            if ($name !== null) {
                $assigned[$name] = true;
            }
        }

        foreach (PermissionEnum::cases() as $permission) {
            $payload[$permission->value] = $assigned[$permission->value] ?? false;
        }

        return $payload;
    }
}

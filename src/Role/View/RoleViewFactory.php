<?php

declare(strict_types=1);

namespace App\Role\View;

use App\Entity\Role;
use App\Security\Permission\PermissionRegistry;

final class RoleViewFactory
{
    public function make(Role $role): array
    {
        $payload = [
            'id' => $role->getId()?->toRfc4122(),
            'name' => $role->getName(),
        ];

        foreach (PermissionRegistry::MAP as $key => $getter) {
            $payload[$key] = (bool) $role->$getter();
        }

        return $payload;
    }
}

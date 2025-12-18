<?php

declare(strict_types=1);

namespace App\User\View;

use App\Entity\Role;
use App\Entity\User;

final class UserListViewFactory
{
    public function make(User $user): array
    {
        $roles = [];
        foreach ($user->getRoleEntities() as $role) {
            if ($role instanceof Role) {
                $roles[] = [
                    'id' => $role->getId()?->toRfc4122(),
                    'name' => $role->getName(),
                ];
            }
        }

        return [
            'id' => $user->getId()?->toRfc4122(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()?->format(DATE_ATOM),
            'last_login_at' => $user->getLastLoginAt()?->format(DATE_ATOM),
            'profile_image_id' => $user->getProfileImage()?->getId()?->toRfc4122(),
            'roles' => $roles,
        ];
    }
}

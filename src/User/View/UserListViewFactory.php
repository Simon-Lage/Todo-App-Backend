<?php

declare(strict_types=1);

namespace App\User\View;

use App\Entity\User;

final class UserListViewFactory
{
    public function make(User $user): array
    {
        return [
            'id' => $user->getId()?->toRfc4122(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()?->format(DATE_ATOM),
        ];
    }
}

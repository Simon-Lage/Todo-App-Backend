<?php

declare(strict_types=1);

namespace App\User\View;

use App\Entity\User;

final class UserViewFactory
{
    public function make(User $user): array
    {
        return [
            'id' => $user->getId()?->toRfc4122(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'active' => $user->isActive(),
            'is_password_temporary' => $user->isPasswordTemporary(),
            'created_at' => $user->getCreatedAt()?->format(DATE_ATOM),
            'temporary_password_created_at' => $user->getTemporaryPasswordCreatedAt()?->format(DATE_ATOM),
            'last_login_at' => $user->getLastLoginAt()?->format(DATE_ATOM),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Permission\PermissionRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermissionVoter extends Voter
{
    public function __construct(private readonly PermissionRegistry $permissionRegistry)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'perm:');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        $permission = substr($attribute, 5);

        try {
            return $this->permissionRegistry->has($user, $permission);
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}

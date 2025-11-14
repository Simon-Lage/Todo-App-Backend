<?php

declare(strict_types=1);

namespace App\Role\Service;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\UserRepository;
use App\Security\Permission\PermissionRegistry;
use App\User\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;

final class RoleService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserRepository $userRepository, private readonly UserService $userService)
    {
    }

    public function create(string $name, array $permissions): Role
    {
        $role = new Role();
        $role->setName($name);
        $this->applyPermissions($role, $permissions);
        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    public function update(Role $role, ?string $name, array $permissions): Role
    {
        if ($name !== null) {
            $role->setName($name);
        }

        $current = $this->extractPermissions($role);
        foreach ($permissions as $permission => $value) {
            $current[$permission] = (bool) $value;
        }
        $this->applyPermissions($role, $current);
        $this->entityManager->flush();

        return $role;
    }

    public function delete(Role $role): void
    {
        $usage = $this->userRepository->countUsersByRole($role);
        if ($usage > 0) {
            throw ApiProblemException::conflict('Role cannot be deleted while assigned to users.');
        }

        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }

    public function assignRolesToUser(User $user, array $roleIds): User
    {
        return $this->userService->update($user, null, null, null, $roleIds);
    }

    private function applyPermissions(Role $role, array $permissions): void
    {
        foreach (PermissionRegistry::MAP as $key => $getter) {
            $setter = 'set'.substr($getter, 2);
            $role->$setter($permissions[$key] ?? false);
        }
    }

    private function extractPermissions(Role $role): array
    {
        $values = [];
        foreach (PermissionRegistry::MAP as $key => $getter) {
            $values[$key] = (bool) $role->$getter();
        }

        return $values;
    }
}

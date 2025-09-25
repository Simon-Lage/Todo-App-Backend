<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class UserService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserRepository $userRepository, private readonly RoleRepository $roleRepository, private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function create(string $name, string $email, string $password, bool $active, array $roleIds): User
    {
        $conflict = $this->userRepository->findOneBy(['email' => strtolower($email)]);
        if ($conflict instanceof User) {
            throw ApiProblemException::conflict('Email is already in use.');
        }

        $conflict = $this->userRepository->findOneBy(['name' => $name]);
        if ($conflict instanceof User) {
            throw ApiProblemException::conflict('Name is already in use.');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail(strtolower($email));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsPasswordTemporary(false);
        $user->setActive($active);
        $user->setTemporaryPasswordCreatedAt(new \DateTimeImmutable());
        $user->setCreatedAt(new \DateTimeImmutable());

        $roles = $this->resolveRoles($roleIds);
        $user->replaceRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, ?string $name, ?string $email, ?bool $active, ?array $roleIds): User
    {
        if ($name !== null && $name !== $user->getName()) {
            $conflict = $this->userRepository->findOneBy(['name' => $name]);
            if ($conflict instanceof User && $conflict->getId()?->equals($user->getId()) === false) {
                throw ApiProblemException::conflict('Name is already in use.');
            }
            $user->setName($name);
        }

        if ($email !== null && strtolower($email) !== strtolower((string) $user->getEmail())) {
            $conflict = $this->userRepository->findOneBy(['email' => strtolower($email)]);
            if ($conflict instanceof User && $conflict->getId()?->equals($user->getId()) === false) {
                throw ApiProblemException::conflict('Email is already in use.');
            }
            $user->setEmail($email);
        }

        if ($active !== null) {
            $user->setActive($active);
        }

        if ($roleIds !== null) {
            $roles = $this->resolveRoles($roleIds);
            $user->replaceRoles($roles);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function updateSelf(User $user, ?string $name): User
    {
        if ($name !== null && $name !== $user->getName()) {
            $conflict = $this->userRepository->findOneBy(['name' => $name]);
            if ($conflict instanceof User && $conflict->getId()?->equals($user->getId()) === false) {
                throw ApiProblemException::conflict('Name is already in use.');
            }
            $user->setName($name);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function setTemporaryPassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setIsPasswordTemporary(true);
        $user->setTemporaryPasswordCreatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function setPassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setIsPasswordTemporary(false);
        $user->setTemporaryPasswordCreatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function deactivate(User $user): void
    {
        $user->setActive(false);
        $this->entityManager->flush();
    }

    public function reactivate(User $user): void
    {
        $user->setActive(true);
        $this->entityManager->flush();
    }

    public function generateTemporaryPassword(int $length = 16): string
    {
        $bytes = random_bytes($length);
        $base = rtrim(strtr(base64_encode($bytes), '+/', 'AB'), '=');

        return substr($base, 0, $length);
    }

    private function resolveRoles(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $roles = [];
        foreach ($roleIds as $roleId) {
            try {
                $uuid = Uuid::fromString((string) $roleId);
            } catch (\InvalidArgumentException) {
                throw ApiProblemException::validation(['roles' => ['Invalid role id provided.']]);
            }

            $role = $this->roleRepository->find($uuid);
            if (!$role instanceof Role) {
                throw ApiProblemException::validation(['roles' => ['Role not found.']]);
            }

            $roles[] = $role;
        }

        return $roles;
    }
}

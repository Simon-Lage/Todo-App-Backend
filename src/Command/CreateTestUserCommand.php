<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Security\Permission\PermissionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test:create-user',
    description: 'Create a test admin user for testing'
)]
final class CreateTestUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existingUser = $this->userRepository->findOneBy(['email' => 'admin@changeit.de']);
        if ($existingUser) {
            $output->writeln('Test user already exists');
            return Command::SUCCESS;
        }

        $adminRole = $this->roleRepository->findOneBy(['name' => 'Administrator']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setName('Administrator');
            $this->em->persist($adminRole);

            foreach (PermissionEnum::cases() as $permEnum) {
                $perm = $this->permissionRepository->findOneBy(['name' => $permEnum->value]);
                if (!$perm) {
                    $perm = new Permission();
                    $perm->setName($permEnum->value);
                    $this->em->persist($perm);
                }
                $adminRole->addPermission($perm);
            }
        }

        $user = new User();
        $user->setEmail('admin@changeit.de');
        $user->setName('Test Admin');
        $password = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($password);
        $user->assignRole($adminRole);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Test user created: admin@changeit.de / password123');
        return Command::SUCCESS;
    }
}


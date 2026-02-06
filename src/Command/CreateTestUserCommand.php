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
            $adminRole->setName('admin');
            $this->em->persist($adminRole);

            $allowed = [
                PermissionEnum::CAN_CREATE_USER,
                PermissionEnum::CAN_EDIT_USER,
                PermissionEnum::CAN_READ_USER,
                PermissionEnum::CAN_DELETE_USER,
                PermissionEnum::CAN_CREATE_ROLES,
                PermissionEnum::CAN_EDIT_ROLES,
                PermissionEnum::CAN_READ_ROLES,
                PermissionEnum::CAN_DELETE_ROLES,
                PermissionEnum::CAN_CREATE_TASKS,
                PermissionEnum::CAN_EDIT_TASKS,
                PermissionEnum::CAN_READ_ALL_TASKS,
                PermissionEnum::CAN_DELETE_TASKS,
                PermissionEnum::CAN_ASSIGN_TASKS_TO_USER,
                PermissionEnum::CAN_ASSIGN_TASKS_TO_PROJECT,
                PermissionEnum::CAN_CREATE_PROJECTS,
                PermissionEnum::CAN_EDIT_PROJECTS,
                PermissionEnum::CAN_READ_PROJECTS,
                PermissionEnum::CAN_DELETE_PROJECTS,
                PermissionEnum::CAN_READ_LOGS,
            ];

            foreach ($allowed as $permEnum) {
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

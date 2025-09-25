<?php

declare(strict_types=1);

namespace App\Security\Permission;

use App\Entity\Role;
use App\Entity\User;

final class PermissionRegistry
{
    public const MAP = [
        'perm_can_crate_user' => 'isPermCanCrateUser',
        'perm_can_edit_user' => 'isPermCanEditUser',
        'perm_can_read_user' => 'isPermCanReadUser',
        'perm_can_delete_user' => 'isPermCanDeleteUser',
        'perm_can_create_tasks' => 'isPermCanCreateTasks',
        'perm_can_edit_tasks' => 'isPermCanEditTasks',
        'perm_can_read_all_tasks' => 'isPermCanReadAllTasks',
        'perm_can_delete_tasks' => 'isPermCanDeleteTasks',
        'perm_can_assign_tasks_to_user' => 'isPermCanAssignTasksToUser',
        'perm_can_assign_tasks_to_project' => 'isPermCanAssignTasksToProject',
        'perm_can_create_projects' => 'isPermCanCreateProjects',
        'perm_can_edit_projects' => 'isPermCanEditProjects',
        'perm_can_read_projects' => 'isPermCanReadProjects',
        'perm_can_delete_projects' => 'isPermCanDeleteProjects',
    ];

    public function catalog(): array
    {
        return array_keys(self::MAP);
    }

    public function resolve(User $user): array
    {
        $resolved = array_fill_keys($this->catalog(), false);

        foreach ($user->getRoleEntities() as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            foreach (self::MAP as $key => $method) {
                if ($resolved[$key]) {
                    continue;
                }

                if ($role->$method()) {
                    $resolved[$key] = true;
                }
            }
        }

        return $resolved;
    }

    public function has(User $user, string $permission): bool
    {
        $resolved = $this->resolve($user);

        if (!array_key_exists($permission, $resolved)) {
            throw new \InvalidArgumentException(sprintf('Unknown permission "%s".', $permission));
        }

        return $resolved[$permission];
    }
}

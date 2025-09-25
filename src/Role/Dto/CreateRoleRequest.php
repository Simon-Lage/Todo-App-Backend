<?php

declare(strict_types=1);

namespace App\Role\Dto;

use App\Http\Request\JsonRequestDto;
use App\Security\Permission\PermissionRegistry;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoleRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Collection(
            fields: [
                'perm_can_crate_user' => new Assert\Type('bool'),
                'perm_can_edit_user' => new Assert\Type('bool'),
                'perm_can_read_user' => new Assert\Type('bool'),
                'perm_can_delete_user' => new Assert\Type('bool'),
                'perm_can_create_tasks' => new Assert\Type('bool'),
                'perm_can_edit_tasks' => new Assert\Type('bool'),
                'perm_can_read_all_tasks' => new Assert\Type('bool'),
                'perm_can_delete_tasks' => new Assert\Type('bool'),
                'perm_can_assign_tasks_to_user' => new Assert\Type('bool'),
                'perm_can_assign_tasks_to_project' => new Assert\Type('bool'),
                'perm_can_create_projects' => new Assert\Type('bool'),
                'perm_can_edit_projects' => new Assert\Type('bool'),
                'perm_can_read_projects' => new Assert\Type('bool'),
                'perm_can_delete_projects' => new Assert\Type('bool'),
            ],
            allowMissingFields: true,
            allowExtraFields: false
        )]
        public readonly array $permissions
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $values = [];
        foreach (PermissionRegistry::MAP as $permission => $_) {
            $values[$permission] = array_key_exists($permission, $payload) ? (bool) $payload[$permission] : false;
        }

        return new self($values);
    }
}

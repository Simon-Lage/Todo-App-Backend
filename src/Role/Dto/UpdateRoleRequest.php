<?php

declare(strict_types=1);

namespace App\Role\Dto;

use App\Http\Request\JsonRequestDto;
use App\Security\Permission\PermissionRegistry;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateRoleRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 100)]
        #[Assert\NotBlank(allowNull: true)]
        public readonly ?string $name,
        #[Assert\Collection(
            fields: [
                'perm_can_create_user' => new Assert\Type('bool'),
                'perm_can_edit_user' => new Assert\Type('bool'),
                'perm_can_read_user' => new Assert\Type('bool'),
                'perm_can_delete_user' => new Assert\Type('bool'),
                'perm_can_create_roles' => new Assert\Type('bool'),
                'perm_can_edit_roles' => new Assert\Type('bool'),
                'perm_can_read_roles' => new Assert\Type('bool'),
                'perm_can_delete_roles' => new Assert\Type('bool'),
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
        $provided = [];
        foreach (PermissionRegistry::MAP as $permission => $_) {
            if (array_key_exists($permission, $payload)) {
                $provided[$permission] = (bool) $payload[$permission];
            }
        }

        $name = array_key_exists('name', $payload) ? (string) $payload['name'] : null;
        if ($name !== null) {
            $name = trim($name);

            if ($name == '') {
                $name = null;
            }
        }

        if ($provided === [] && $name === null) {
            throw new \InvalidArgumentException('No role updates provided.');
        }

        return new self($name, $provided);
    }
}

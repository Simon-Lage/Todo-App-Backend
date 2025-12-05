<?php

declare(strict_types=1);

namespace App\Role\Dto;

use App\Http\Request\JsonRequestDto;
use App\Security\Permission\PermissionEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoleRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public readonly string $name,
        public readonly array $permissions
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $values = [];
        foreach (PermissionEnum::cases() as $enum) {
            $permission = $enum->value;
            $values[$permission] = array_key_exists($permission, $payload) ? (bool) $payload[$permission] : false;
        }

        if (!array_key_exists('name', $payload)) {
            throw new \InvalidArgumentException('Role name is required.');
        }

        $name = trim((string) $payload['name']);
        if ($name === '') {
            throw new \InvalidArgumentException('Role name is required.');
        }

        return new self($name, $values);
    }
}

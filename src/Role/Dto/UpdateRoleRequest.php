<?php

declare(strict_types=1);

namespace App\Role\Dto;

use App\Http\Request\JsonRequestDto;
use App\Security\Permission\PermissionEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateRoleRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 100)]
        #[Assert\NotBlank(allowNull: true)]
        public readonly ?string $name,
        public readonly array $permissions
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $provided = [];
        foreach (PermissionEnum::cases() as $enum) {
            $permission = $enum->value;
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

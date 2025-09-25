<?php

declare(strict_types=1);

namespace App\Role\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class AssignRolesRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\All([new Assert\Uuid()])]
        public readonly array $roles
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('roles', $payload)) {
            throw new \InvalidArgumentException('Roles array is required.');
        }

        $roles = array_map(static fn($value) => (string) $value, (array) $payload['roles']);

        return new self($roles);
    }
}

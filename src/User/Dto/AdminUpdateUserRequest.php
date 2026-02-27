<?php

declare(strict_types=1);

namespace App\User\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class AdminUpdateUserRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\Length(max: 32)]
        public readonly ?string $name,
        #[Assert\Email]
        #[Assert\Length(max: 128)]
        public readonly ?string $email,
        public readonly ?bool $active,
        #[Assert\All([new Assert\Uuid()])]
        public readonly ?array $roles
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $name = array_key_exists('name', $payload) ? (string) $payload['name'] : null;
        $email = array_key_exists('email', $payload) ? (string) $payload['email'] : null;
        $active = array_key_exists('active', $payload) ? (bool) $payload['active'] : null;
        $roles = array_key_exists('roles', $payload) ? array_map(static fn($role) => (string) $role, (array) $payload['roles']) : null;

        if ($name === null && $email === null && $active === null && $roles === null) {
            throw new \InvalidArgumentException('No updatable fields provided.');
        }

        return new self($name, $email, $active, $roles);
    }
}

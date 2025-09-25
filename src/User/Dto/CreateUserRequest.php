<?php

declare(strict_types=1);

namespace App\User\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public readonly string $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 128)]
        public readonly string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 255)]
        public readonly string $password,
        #[Assert\Type('bool')]
        public readonly bool $active,
        #[Assert\All([new Assert\Uuid()])]
        public readonly array $roles
    ) {
    }

    public static function fromArray(array $payload): static
    {
        if (!array_key_exists('name', $payload) || !array_key_exists('email', $payload) || !array_key_exists('password', $payload)) {
            throw new \InvalidArgumentException('Missing required user attributes.');
        }

        $active = array_key_exists('active', $payload) ? (bool) $payload['active'] : true;
        $roles = array_key_exists('roles', $payload) ? (array) $payload['roles'] : [];

        return new self(
            (string) $payload['name'],
            (string) $payload['email'],
            (string) $payload['password'],
            $active,
            array_map(static fn($role) => (string) $role, $roles)
        );
    }
}

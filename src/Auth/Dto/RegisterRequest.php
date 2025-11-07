<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest implements JsonRequestDto
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
        public readonly ?bool $active = null
    ) {
    }

    public static function fromArray(array $payload): static
    {
        foreach (['name', 'email', 'password'] as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new \InvalidArgumentException(sprintf('%s is required.', ucfirst($field)));
            }
        }

        $active = array_key_exists('active', $payload) ? (bool) $payload['active'] : null;

        return new self(
            (string) $payload['name'],
            (string) $payload['email'],
            (string) $payload['password'],
            $active
        );
    }
}

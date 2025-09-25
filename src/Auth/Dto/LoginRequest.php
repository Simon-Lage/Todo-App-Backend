<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,
        #[Assert\NotBlank]
        public readonly string $password
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $email = array_key_exists('email', $payload) ? (string) $payload['email'] : '';
        $password = array_key_exists('password', $payload) ? (string) $payload['password'] : '';

        return new self($email, $password);
    }
}

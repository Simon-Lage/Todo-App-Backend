<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordConfirmRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $token,
        #[Assert\NotBlank]
        #[Assert\Length(min: 12)]
        public readonly string $newPassword
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $token = array_key_exists('token', $payload) ? (string) $payload['token'] : '';
        $new = array_key_exists('new_password', $payload) ? (string) $payload['new_password'] : '';

        return new self($token, $new);
    }
}

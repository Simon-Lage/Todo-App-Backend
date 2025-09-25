<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Http\Request\JsonRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class LogoutRequest implements JsonRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $refreshToken
    ) {
    }

    public static function fromArray(array $payload): static
    {
        $token = array_key_exists('refresh_token', $payload) ? (string) $payload['refresh_token'] : '';

        return new self($token);
    }
}

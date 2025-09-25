<?php

declare(strict_types=1);

namespace App\Auth\Dto;

final class TokenPair
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $accessTokenExpiresIn,
        public readonly \DateTimeImmutable $refreshTokenExpiresAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'access_token_expires_in' => $this->accessTokenExpiresIn,
            'refresh_token' => $this->refreshToken,
            'refresh_token_expires_at' => $this->refreshTokenExpiresAt->format(DATE_ATOM),
        ];
    }
}

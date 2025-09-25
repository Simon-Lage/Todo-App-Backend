<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Dto\TokenPair;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class AuthTokenService
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly UserRepository $userRepository,
        private readonly int $accessTokenTtl,
        private readonly int $refreshTokenTtl
    ) {
    }

    public function issue(User $user): TokenPair
    {
        $this->refreshTokenManager->revokeAllInvalid();
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return $this->buildTokenPair($user, $refreshToken);
    }

    public function refresh(string $refreshTokenValue): array
    {
        $storedToken = $this->refreshTokenManager->get($refreshTokenValue);

        if (!$storedToken instanceof RefreshTokenInterface) {
            throw ApiProblemException::unauthorized('Refresh token is invalid.');
        }

        if (!$storedToken->isValid()) {
            $this->refreshTokenManager->delete($storedToken);
            throw ApiProblemException::unauthorized('Refresh token expired.');
        }

        $user = $this->userRepository->findOneBy(['email' => $storedToken->getUsername()]);

        if (!$user instanceof User || !$user->isActive()) {
            $this->refreshTokenManager->delete($storedToken);
            throw ApiProblemException::forbidden('Account is inactive.');
        }

        $this->refreshTokenManager->delete($storedToken);

        return ['tokenPair' => $this->issue($user), 'user' => $user];
    }

    public function revoke(string $refreshTokenValue): void
    {
        $storedToken = $this->refreshTokenManager->get($refreshTokenValue);

        if ($storedToken instanceof RefreshTokenInterface) {
            $this->refreshTokenManager->delete($storedToken);
        }
    }

    public function revokeForUser(string $refreshTokenValue, User $user): void
    {
        $storedToken = $this->refreshTokenManager->get($refreshTokenValue);

        if ($storedToken instanceof RefreshTokenInterface && strtolower($storedToken->getUsername()) === strtolower($user->getEmail() ?? '')) {
            $this->refreshTokenManager->delete($storedToken);
        }
    }

    private function buildTokenPair(User $user, RefreshTokenInterface $refreshToken): TokenPair
    {
        $refreshExpiry = $refreshToken->getValid();
        $refreshExpiresAt = $refreshExpiry instanceof DateTimeImmutable ? $refreshExpiry : DateTimeImmutable::createFromMutable($refreshExpiry);

        return new TokenPair(
            $this->jwtManager->create($user),
            $refreshToken->getRefreshToken(),
            $this->accessTokenTtl,
            $refreshExpiresAt ?? new DateTimeImmutable()
        );
    }
}

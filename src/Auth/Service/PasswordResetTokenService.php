<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

final class PasswordResetTokenService
{
    public function __construct(private readonly PasswordResetTokenRepository $repository, private readonly EntityManagerInterface $entityManager, private readonly int $tokenTtl)
    {
    }

    public function create(User $user): string
    {
        $tokenValue = bin2hex(random_bytes(32));
        $digest = hash('sha256', $tokenValue);
        $expiresAt = (new DateTimeImmutable())->modify(sprintf('+%d seconds', $this->tokenTtl));

        $this->repository->deleteAllForUser($user);

        $token = new PasswordResetToken($user, $digest, $expiresAt);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $tokenValue;
    }

    public function consume(string $tokenValue): PasswordResetToken
    {
        $digest = hash('sha256', $tokenValue);
        $token = $this->repository->findActiveByDigest($digest);

        if (!$token instanceof PasswordResetToken) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'Reset token is invalid.', 'TOKEN_INVALID');
        }

        if ($token->isExpired()) {
            $token->markUsed();
            $this->entityManager->flush();
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'Reset token expired.', 'TOKEN_INVALID');
        }

        $token->markUsed();
        $this->entityManager->flush();

        return $token;
    }
}

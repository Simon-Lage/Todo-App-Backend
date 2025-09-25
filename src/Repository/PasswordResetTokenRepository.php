<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findActiveByDigest(string $digest): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->andWhere('prt.tokenDigest = :digest')
            ->andWhere('prt.usedAt IS NULL')
            ->setParameter('digest', $digest)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteAllForUser(User $user): void
    {
        $this->_em->createQueryBuilder()
            ->delete(PasswordResetToken::class, 'prt')
            ->where('prt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}

<?php

namespace App\Repository;

use App\Entity\Log;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getApproxSizeBytes(): int
    {
        $entityManager = $this->getEntityManager();
        $detailsSize = (int) $entityManager->createQuery('SELECT COALESCE(SUM(LENGTH(l.details)), 0) FROM App\\Entity\\Log l')
            ->getSingleScalarResult();
        $count = $this->countAll();

        return $detailsSize + ($count * 512);
    }

    public function deleteOldestBatch(int $limit): int
    {
        $ids = $this->createQueryBuilder('l')
            ->select('l.id')
            ->orderBy('l.performed_at', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        if ($ids === []) {
            return 0;
        }

        $ids = array_map(static fn(array $row) => $row['id'], $ids);

        return $this->getEntityManager()->createQuery('DELETE FROM App\\Entity\\Log l WHERE l.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->execute();
    }

    public function findOldestTimestamp(): ?\DateTimeImmutable
    {
        $result = $this->getEntityManager()->createQuery('SELECT l.performed_at AS performed_at FROM App\\Entity\\Log l ORDER BY l.performed_at ASC')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($result === null) {
            return null;
        }

        return $result['performed_at'] ?? null;
    }

    public function findLatestTimestamp(): ?\DateTimeImmutable
    {
        $result = $this->getEntityManager()->createQuery('SELECT l.performed_at AS performed_at FROM App\\Entity\\Log l ORDER BY l.performed_at DESC')
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($result === null) {
            return null;
        }

        return $result['performed_at'] ?? null;
    }
}

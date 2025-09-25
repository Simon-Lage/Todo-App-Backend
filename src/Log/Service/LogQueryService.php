<?php

declare(strict_types=1);

namespace App\Log\Service;

use App\Entity\Log;
use App\Repository\LogRepository;
use DateTimeImmutable;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Uid\Uuid;

final class LogQueryService
{
    private const SORTABLE_FIELDS = [
        'performed_at' => 'l.performed_at',
        'action' => 'l.action',
    ];

    public function __construct(private readonly LogRepository $logRepository)
    {
    }

    public function list(array $filters, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->logRepository->createQueryBuilder('l');

        if ($filters['action'] !== null) {
            $qb->andWhere('l.action LIKE :action')->setParameter('action', '%'.$filters['action'].'%');
        }

        if ($filters['performed_by_user_id'] !== null) {
            $qb->andWhere('IDENTITY(l.performed_by_user) = :userId')->setParameter('userId', $filters['performed_by_user_id']);
        }

        if ($filters['from'] instanceof DateTimeImmutable) {
            $qb->andWhere('l.performed_at >= :from')->setParameter('from', $filters['from']);
        }

        if ($filters['to'] instanceof DateTimeImmutable) {
            $qb->andWhere('l.performed_at <= :to')->setParameter('to', $filters['to']);
        }

        if ($filters['q'] !== null) {
            $qb->andWhere('l.details LIKE :query')->setParameter('query', '%'.$filters['q'].'%');
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['performed_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        $qb->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $total,
            'items' => iterator_to_array($paginator->getIterator(), false),
        ];
    }

    public function stats(): array
    {
        return [
            'total' => $this->logRepository->countAll(),
            'approx_size_bytes' => $this->logRepository->getApproxSizeBytes(),
            'oldest' => $this->logRepository->findOldestTimestamp()?->format(DATE_ATOM),
            'newest' => $this->logRepository->findLatestTimestamp()?->format(DATE_ATOM),
        ];
    }
}

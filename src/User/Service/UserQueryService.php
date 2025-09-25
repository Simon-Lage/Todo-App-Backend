<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Uid\Uuid;

final class UserQueryService
{
    private const SORTABLE_FIELDS = [
        'name' => 'u.name',
        'email' => 'u.email',
        'created_at' => 'u.created_at',
        'last_login_at' => 'u.last_login_at',
    ];

    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function list(array $filters, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        if (array_key_exists('q', $filters) && $filters['q'] !== null) {
            $term = strtolower((string) $filters['q']);
            $qb->andWhere('LOWER(u.name) LIKE :term OR LOWER(u.email) LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null) {
            $qb->andWhere('u.active = :active')->setParameter('active', (bool) $filters['active']);
        }

        if (array_key_exists('role_id', $filters) && $filters['role_id'] !== null) {
            $qb->innerJoin('u.roleEntities', 'r')->andWhere('r.id = :roleId')->setParameter('roleId', Uuid::fromString((string) $filters['role_id']));
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['created_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT u.id)')->getQuery()->getSingleScalarResult();

        $qb->select('DISTINCT u')
            ->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $total,
            'items' => iterator_to_array($paginator->getIterator()),
        ];
    }

    public function usersByRole(string $roleId): array
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->innerJoin('u.roleEntities', 'r')
            ->andWhere('r.id = :roleId')
            ->setParameter('roleId', Uuid::fromString($roleId))
            ->orderBy('u.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}

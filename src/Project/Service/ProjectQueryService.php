<?php

declare(strict_types=1);

namespace App\Project\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Uid\Uuid;

final class ProjectQueryService
{
    private const SORTABLE_FIELDS = [
        'name' => 'p.name',
        'created_at' => 'p.created_at',
    ];

    public function __construct(private readonly ProjectRepository $projectRepository)
    {
    }

    public function list(array $filters, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->projectRepository->createQueryBuilder('p');

        if ($filters['q'] !== null) {
            $term = '%'.strtolower((string) $filters['q']).'%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term')->setParameter('term', $term);
        }

        if ($filters['created_by_user_id'] !== null) {
            $creatorId = Uuid::fromString($filters['created_by_user_id'])->toRfc4122();
            $qb->andWhere('IDENTITY(p.created_by_user) = :creatorId')->setParameter('creatorId', $creatorId);
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['created_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $count = (int) (clone $qb)->select('COUNT(DISTINCT p.id)')->getQuery()->getSingleScalarResult();

        $qb->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $count,
            'items' => array_values(iterator_to_array($paginator->getIterator())),
        ];
    }

    public function listMy(User $user, array $filters, bool $includeTeamLeadProjects, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->projectRepository->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')
            ->leftJoin('t.assignedUsers', 'au')
            ->setParameter('current', $user);

        if ($includeTeamLeadProjects) {
            $qb->leftJoin('p.teamLeads', 'tl');
            $qb->andWhere($qb->expr()->orX('au = :current', 'tl = :current'));
        } else {
            $qb->andWhere('au = :current');
        }

        if (($filters['q'] ?? null) !== null) {
            $term = '%'.strtolower((string) $filters['q']).'%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term')->setParameter('term', $term);
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['created_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $count = (int) (clone $qb)->select('COUNT(DISTINCT p.id)')->getQuery()->getSingleScalarResult();

        $qb->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $count,
            'items' => array_values(iterator_to_array($paginator->getIterator())),
        ];
    }
}

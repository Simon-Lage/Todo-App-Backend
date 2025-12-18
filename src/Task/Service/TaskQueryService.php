<?php

declare(strict_types=1);

namespace App\Task\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class TaskQueryService
{
    private const SORTABLE_FIELDS = [
        'created_at' => 't.created_at',
        'due_date' => 't.due_date',
        'priority' => 't.priority',
        'status' => 't.status',
        'title' => 't.title',
    ];

    public function __construct(private readonly TaskRepository $taskRepository)
    {
    }

    public function list(User $currentUser, array $filters, bool $restrictToUserScope, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->taskRepository->createQueryBuilder('t');

        if ($restrictToUserScope) {
            $qb->leftJoin('t.assignedUsers', 'au');
            $qb->andWhere('t.created_by_user = :current OR au = :current')
                ->setParameter('current', $currentUser);
        }

        if ($filters['status'] !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }

        if ($filters['priority'] !== null) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }

        if ($filters['project_id'] !== null) {
            $qb->andWhere('IDENTITY(t.project) = :projectId')->setParameter('projectId', $filters['project_id']);
        }

        if ($filters['assigned_to_user_id'] !== null) {
            if (!isset($qb->getDQLPart('join')['t']) || !array_key_exists('au', $qb->getDQLPart('join')['t'])) {
                $qb->leftJoin('t.assignedUsers', 'au_filter');
                $qb->andWhere('au_filter.id = :assignedUserId')->setParameter('assignedUserId', $filters['assigned_to_user_id']);
            } else {
                $qb->andWhere('au.id = :assignedUserId')->setParameter('assignedUserId', $filters['assigned_to_user_id']);
            }
        }

        if ($filters['created_by_user_id'] !== null) {
            $qb->andWhere('IDENTITY(t.created_by_user) = :creatorId')->setParameter('creatorId', $filters['created_by_user_id']);
        }

        if ($filters['due_date_from'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date >= :dueFrom')->setParameter('dueFrom', $filters['due_date_from']);
        }

        if ($filters['due_date_to'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date <= :dueTo')->setParameter('dueTo', $filters['due_date_to']);
        }

        if ($filters['q'] !== null) {
            $term = '%'.strtolower($filters['q']).'%';
            $qb->andWhere('LOWER(t.title) LIKE :term OR LOWER(t.description) LIKE :term')->setParameter('term', $term);
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['created_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) (clone $qb)->select('COUNT(DISTINCT t.id)')->getQuery()->getSingleScalarResult();

        $qb->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $total,
            'items' => array_values(iterator_to_array($paginator->getIterator())),
        ];
    }

    public function listForTeamLead(User $teamLead, array $filters, int $offset, int $limit, string $sortBy, string $direction): array
    {
        $qb = $this->taskRepository->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.teamLeads', 'tl')
            ->setParameter('current', $teamLead);

        $qb->andWhere($qb->expr()->orX('tl = :current', 't.project IS NULL AND t.created_by_user = :current'));

        if ($filters['status'] !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }

        if ($filters['priority'] !== null) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }

        if ($filters['project_id'] !== null) {
            $qb->andWhere('IDENTITY(t.project) = :projectId')->setParameter('projectId', $filters['project_id']);
        }

        if ($filters['assigned_to_user_id'] !== null) {
            $qb->leftJoin('t.assignedUsers', 'au_filter');
            $qb->andWhere('au_filter.id = :assignedUserId')->setParameter('assignedUserId', $filters['assigned_to_user_id']);
        }

        if ($filters['created_by_user_id'] !== null) {
            $qb->andWhere('IDENTITY(t.created_by_user) = :creatorId')->setParameter('creatorId', $filters['created_by_user_id']);
        }

        if ($filters['due_date_from'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date >= :dueFrom')->setParameter('dueFrom', $filters['due_date_from']);
        }

        if ($filters['due_date_to'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date <= :dueTo')->setParameter('dueTo', $filters['due_date_to']);
        }

        if ($filters['q'] !== null) {
            $term = '%'.strtolower($filters['q']).'%';
            $qb->andWhere('LOWER(t.title) LIKE :term OR LOWER(t.description) LIKE :term')->setParameter('term', $term);
        }

        $sortField = self::SORTABLE_FIELDS[$sortBy] ?? self::SORTABLE_FIELDS['created_at'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) (clone $qb)->select('COUNT(DISTINCT t.id)')->getQuery()->getSingleScalarResult();

        $qb->orderBy($sortField, $direction)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);

        return [
            'total' => $total,
            'items' => array_values(iterator_to_array($paginator->getIterator())),
        ];
    }
}

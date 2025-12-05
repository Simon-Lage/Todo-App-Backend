<?php

declare(strict_types=1);

namespace App\Search\Service;

use App\Entity\Log;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

final class SearchService
{
    private const DEFAULT_LIMIT = 50;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function searchAll(string $term, int $limit = self::DEFAULT_LIMIT, array $filters = []): array
    {
        return [
            'users' => $this->searchUsers($term, $limit, $filters['users'] ?? []),
            'projects' => $this->searchProjects($term, $limit, $filters['projects'] ?? []),
            'tasks' => $this->searchTasks($term, $limit, $filters['tasks'] ?? []),
            'logs' => $this->searchLogs($term, $limit, $filters['logs'] ?? []),
        ];
    }

    public function searchUsers(string $term, int $limit = self::DEFAULT_LIMIT, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        $this->applyLikeFilter($qb, ['LOWER(u.name)', 'LOWER(u.email)'], $term);

        if (array_key_exists('active', $filters) && $filters['active'] !== null) {
            $qb->andWhere('u.active = :active')->setParameter('active', (bool) $filters['active']);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchProjects(string $term, int $limit = self::DEFAULT_LIMIT, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p');

        $this->applyLikeFilter($qb, ['LOWER(p.name)', 'LOWER(p.description)'], $term);

        if (!empty($filters['created_by_user_id'])) {
            $qb->andWhere('IDENTITY(p.created_by_user) = :creator')->setParameter('creator', $filters['created_by_user_id']);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchTasks(string $term, int $limit = self::DEFAULT_LIMIT, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't');

        $this->applyLikeFilter($qb, ['LOWER(t.title)', 'LOWER(t.description)', 'LOWER(t.status)', 'LOWER(t.priority)'], $term);

        if (!empty($filters['project_id'])) {
            $qb->andWhere('IDENTITY(t.project) = :projectId')->setParameter('projectId', $filters['project_id']);
        }

        if (!empty($filters['assigned_to_user_id'])) {
            $qb->leftJoin('t.assignedUsers', 'au_search');
            $qb->andWhere('au_search.id = :assignedId')->setParameter('assignedId', $filters['assigned_to_user_id']);
        }

        if (!empty($filters['created_by_user_id'])) {
            $qb->andWhere('IDENTITY(t.created_by_user) = :creatorId')->setParameter('creatorId', $filters['created_by_user_id']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('LOWER(t.status) = :status')->setParameter('status', mb_strtolower($filters['status']));
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('LOWER(t.priority) = :priority')->setParameter('priority', mb_strtolower($filters['priority']));
        }

        if (!empty($filters['due_date_from']) && $filters['due_date_from'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date >= :dueFrom')->setParameter('dueFrom', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to']) && $filters['due_date_to'] instanceof DateTimeImmutable) {
            $qb->andWhere('t.due_date <= :dueTo')->setParameter('dueTo', $filters['due_date_to']);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchLogs(string $term, int $limit = self::DEFAULT_LIMIT, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Log::class, 'l');

        $this->applyLikeFilter($qb, ['LOWER(l.action)', "LOWER(COALESCE(l.details, ''))"], $term);

        if (!empty($filters['performed_by_user_id'])) {
            $qb->andWhere('IDENTITY(l.performed_by_user) = :actorId')->setParameter('actorId', $filters['performed_by_user_id']);
        }

        if (!empty($filters['action_exact'])) {
            $qb->andWhere('l.action = :actionExact')->setParameter('actionExact', $filters['action_exact']);
        }

        if (!empty($filters['from']) && $filters['from'] instanceof DateTimeImmutable) {
            $qb->andWhere('l.performed_at >= :fromDate')->setParameter('fromDate', $filters['from']);
        }

        if (!empty($filters['to']) && $filters['to'] instanceof DateTimeImmutable) {
            $qb->andWhere('l.performed_at <= :toDate')->setParameter('toDate', $filters['to']);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function applyLikeFilter(QueryBuilder $qb, array $fields, string $term): void
    {
        $term = mb_strtolower($term);
        $expr = $qb->expr()->orX();
        foreach ($fields as $index => $field) {
            $param = 'term_'.$index;
            $expr->add($qb->expr()->like($field, ':'.$param));
            $qb->setParameter($param, '%'.$term.'%');
        }

        $qb->andWhere($expr);
    }
}

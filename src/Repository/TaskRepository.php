<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    public function countByProjectGroupedByStatus(Project $project): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.status AS status', 'COUNT(t.id) AS total')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->groupBy('t.status')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if ($status === '') {
                continue;
            }
            $result[$status] = (int) ($row['total'] ?? 0);
        }

        return $result;
    }

    public function hasSharedAssignment(User $actor, User $target): bool
    {
        $actorId = $actor->getId();
        $targetId = $target->getId();
        if ($actorId === null || $targetId === null) {
            return false;
        }

        $result = $this->createQueryBuilder('t')
            ->select('t.id')
            ->innerJoin('t.assignedUsers', 'actorUser')
            ->innerJoin('t.assignedUsers', 'targetUser')
            ->andWhere('actorUser.id = :actorId')
            ->andWhere('targetUser.id = :targetId')
            ->setParameter('actorId', $actorId)
            ->setParameter('targetId', $targetId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    public function countAssignedToUser(User $user, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.id)')
            ->innerJoin('t.assignedUsers', 'au')
            ->andWhere('au = :user')
            ->setParameter('user', $user);

        if ($status !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countDoneForDashboard(User $user): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.id)')
            ->leftJoin('t.assignedUsers', 'au')
            ->andWhere('t.status = :status')
            ->andWhere('(au = :user OR t.finalized_by_user = :user)')
            ->setParameter('status', 'done')
            ->setParameter('user', $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

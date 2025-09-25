<?php

namespace App\Repository;

use App\Entity\Image;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function deleteByTask(Task $task): void
    {
        $this->createQueryBuilder('i')
            ->delete()
            ->where('i.task = :task')
            ->setParameter('task', $task)
            ->getQuery()
            ->execute();
    }

    public function deleteByProject(Project $project): void
    {
        $this->createQueryBuilder('i')
            ->delete()
            ->where('i.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->execute();
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('i')
            ->delete()
            ->where('i.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}

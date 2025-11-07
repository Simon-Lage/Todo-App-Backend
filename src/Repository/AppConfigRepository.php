<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AppConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppConfig>
 */
class AppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppConfig::class);
    }

    public function getSingleton(): ?AppConfig
    {
        return $this->createQueryBuilder('c')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Log\Service;

use App\Entity\Log;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class AuditLogger
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LogRetentionService $retentionService)
    {
    }

    public function record(string $action, User $actor, array $details = []): void
    {
        $log = new Log();
        $log->setAction($action);
        $log->setPerformedByUser($actor);
        $log->setPerformedAt(new DateTimeImmutable());
        $log->setDetails($details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR));

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->retentionService->enforce();
    }
}

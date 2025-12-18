<?php

declare(strict_types=1);

namespace App\Project\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\ImageRepository;
use App\Repository\ProjectRepository;
use App\Task\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projectRepository,
        private readonly TaskService $taskService,
        private readonly ImageRepository $imageRepository
    )
    {
    }

    public function create(User $creator, string $name, ?string $description): Project
    {
        $this->assertNameIsAvailable($name);

        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $project->setCreatedByUser($creator);
        $project->addTeamLead($creator);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    public function update(Project $project, ?string $name, ?string $description): Project
    {
        if ($name !== null && $name !== $project->getName()) {
            $this->assertNameIsAvailable($name, $project);
            $project->setName($name);
        }

        if ($description !== null) {
            $project->setDescription($description);
        }

        $this->entityManager->flush();

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->taskService->deleteByProject($project);
        $this->imageRepository->deleteByProject($project);
        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }

    public function addTeamLead(Project $project, User $teamLead): Project
    {
        $project->addTeamLead($teamLead);
        $this->entityManager->flush();

        return $project;
    }

    public function complete(Project $project, User $actor): Project
    {
        $project->setIsCompleted(true);
        $project->setCompletedAt(new \DateTimeImmutable());
        $project->setCompletedByUser($actor);
        $this->entityManager->flush();

        return $project;
    }

    private function assertNameIsAvailable(string $name, ?Project $current = null): void
    {
        $existing = $this->projectRepository->findOneBy(['name' => $name]);
        if ($existing instanceof Project && ($current === null || !$existing->getId()?->equals($current->getId()))) {
            throw ApiProblemException::conflict('Project name is already in use.');
        }
    }
}

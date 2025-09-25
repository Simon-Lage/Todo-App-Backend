<?php

declare(strict_types=1);

namespace App\Task\Service;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\ImageRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Task\Dto\CreateTaskRequest;
use App\Task\Dto\UpdateTaskRequest;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class TaskService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskRepository $taskRepository,
        private readonly UserRepository $userRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly ImageRepository $imageRepository
    ) {
    }

    public function create(User $creator, CreateTaskRequest $request): Task
    {
        $task = new Task();
        $task->setTitle($request->title);
        $task->setDescription($request->description);
        $task->setStatus($request->status);
        $task->setPriority($request->priority);
        $task->setCreatedByUser($creator);

        if ($request->clearDueDate) {
            $task->setDueDate(null);
        } elseif ($request->dueDate !== null) {
            $task->setDueDate($this->parseDueDate($request->dueDate, 'due_date'));
        }

        if ($request->assignedToUserId !== null) {
            $task->setAssignedToUser($this->findUser($request->assignedToUserId));
        }

        if ($request->projectId !== null) {
            $task->setProject($this->findProject($request->projectId));
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function update(Task $task, UpdateTaskRequest $request): Task
    {
        if ($request->title !== null) {
            $task->setTitle($request->title);
        }

        if ($request->description !== null) {
            $task->setDescription($request->description);
        }

        if ($request->priority !== null) {
            $task->setPriority($request->priority);
        }

        if ($request->dueDate !== null) {
            $task->setDueDate($this->parseDueDate($request->dueDate, 'due_date'));
        }

        $task->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        return $task;
    }

    public function changeStatus(Task $task, string $status): Task
    {
        $task->setStatus($status);
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function assignToUser(Task $task, string $userId): Task
    {
        $user = $this->findUser($userId);
        $task->setAssignedToUser($user);
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function unassign(Task $task): Task
    {
        $task->setAssignedToUser(null);
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function moveToProject(Task $task, ?string $projectId): Task
    {
        if ($projectId === null || $projectId === '') {
            $task->setProject(null);
        } else {
            $task->setProject($this->findProject($projectId));
        }

        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function delete(Task $task): void
    {
        $this->imageRepository->deleteByTask($task);
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function deleteByProject(Project $project): void
    {
        $tasks = $this->taskRepository->findBy(['project' => $project]);
        foreach ($tasks as $task) {
            if ($task instanceof Task) {
                $this->delete($task);
            }
        }
    }

    private function findUser(string $userId): User
    {
        try {
            $uuid = Uuid::fromString($userId);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation(['assigned_to_user_id' => ['Invalid user id.']]);
        }

        $user = $this->userRepository->find($uuid);
        if (!$user instanceof User || !$user->isActive()) {
            throw ApiProblemException::validation(['assigned_to_user_id' => ['User not found or inactive.']]);
        }

        return $user;
    }

    private function findProject(string $projectId): Project
    {
        try {
            $uuid = Uuid::fromString($projectId);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation(['project_id' => ['Invalid project id.']]);
        }

        $project = $this->projectRepository->find($uuid);
        if (!$project instanceof Project) {
            throw ApiProblemException::validation(['project_id' => ['Project not found.']]);
        }

        return $project;
    }

    private function parseDueDate(string $value, string $field): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw ApiProblemException::validation([$field => ['Invalid date format.']]);
        }
    }
}

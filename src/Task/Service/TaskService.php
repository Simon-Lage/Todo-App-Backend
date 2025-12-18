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
    private const FINAL_STATUSES = ['done', 'cancelled'];

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

        if ($request->assignedUserIds !== null && count($request->assignedUserIds) > 0) {
            foreach ($request->assignedUserIds as $userId) {
                $task->assignUser($this->findUser($userId));
            }
        }

        if ($request->projectId !== null) {
            $project = $this->findProject($request->projectId);
            if (!$project->isTeamLead($creator)) {
                throw ApiProblemException::forbidden('Only a project teamlead can create tasks for this project.');
            }
            $task->setProject($project);
            $task->setReviewerUser(null);
        } else {
            $task->setReviewerUser($creator);
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

    public function changeStatus(Task $task, string $status, User $actor): Task
    {
        $this->assertCanChangeStatus($task, $status, $actor);

        $task->setStatus($status);
        $task->setUpdatedAt(new DateTimeImmutable());

        if (in_array($status, self::FINAL_STATUSES, true)) {
            $task->setFinalizedByUser($actor);
            $task->setFinalizedAt(new DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $task;
    }

    /**
     * @param string[] $userIds
     */
    public function assignUsers(Task $task, array $userIds, User $actor): Task
    {
        $this->assertCanManageAssignments($task, $actor, 'assign');
        $task->clearAssignedUsers();
        
        foreach ($userIds as $userId) {
            $user = $this->findUser($userId);
            $task->assignUser($user);
        }
        
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function assignUser(Task $task, string $userId, User $actor): Task
    {
        $this->assertCanManageAssignments($task, $actor, 'assign');
        $user = $this->findUser($userId);
        $task->assignUser($user);
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function unassignUser(Task $task, string $userId, User $actor): Task
    {
        $this->assertCanManageAssignments($task, $actor, 'unassign');
        $user = $this->findUser($userId);
        $task->unassignUser($user);
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function clearAssignees(Task $task, User $actor): Task
    {
        $this->assertCanManageAssignments($task, $actor, 'clear');
        $task->clearAssignedUsers();
        $task->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    public function moveToProject(Task $task, ?string $projectId, User $actor): Task
    {
        $this->assertCanManageAssignments($task, $actor, 'move');

        if ($projectId === null || $projectId === '') {
            $task->setProject(null);
            $task->setReviewerUser($task->getCreatedByUser());
        } else {
            $targetProject = $this->findProject($projectId);
            if (!$targetProject->isTeamLead($actor)) {
                throw ApiProblemException::forbidden('Only a teamlead of the target project can move this task to that project.');
            }
            $task->setProject($targetProject);
            $task->setReviewerUser(null);
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

    private function assertCanManageAssignments(Task $task, User $actor, string $action): void
    {
        $project = $task->getProject();
        if ($project instanceof Project) {
            if ($project->isTeamLead($actor)) {
                return;
            }
            throw ApiProblemException::forbidden(sprintf('Only a project teamlead can %s assignments for this task.', $action));
        }

        $creatorId = $task->getCreatedByUser()?->getId();
        if ($creatorId !== null && $creatorId->equals($actor->getId())) {
            return;
        }

        throw ApiProblemException::forbidden(sprintf('Only the task creator can %s assignments for this task.', $action));
    }

    private function assertCanChangeStatus(Task $task, string $newStatus, User $actor): void
    {
        if (!in_array($newStatus, ['open', 'in_progress', 'review', 'done', 'cancelled'], true)) {
            throw ApiProblemException::validation(['status' => ['Invalid status.']]);
        }

        if (!in_array($newStatus, self::FINAL_STATUSES, true)) {
            return;
        }

        $project = $task->getProject();
        if ($project instanceof Project) {
            if ($project->isTeamLead($actor)) {
                return;
            }
            throw ApiProblemException::forbidden('Only a project teamlead can set this task to done or cancelled.');
        }

        $creatorId = $task->getCreatedByUser()?->getId();
        if ($creatorId !== null && $creatorId->equals($actor->getId())) {
            return;
        }

        throw ApiProblemException::forbidden('Only the task creator can set this task to done or cancelled when it has no project.');
    }
}

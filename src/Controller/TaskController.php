<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Repository\TaskRepository;
use App\Security\Permission\PermissionRegistry;
use App\Task\Dto\AssignUserRequest;
use App\Task\Dto\CreateTaskRequest;
use App\Task\Dto\MoveTaskRequest;
use App\Task\Dto\UpdateTaskRequest;
use App\Task\Dto\UpdateTaskStatusRequest;
use App\Task\Service\TaskQueryService;
use App\Task\Service\TaskService;
use App\Task\View\TaskSummaryViewFactory;
use App\Task\View\TaskViewFactory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/task')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly TaskService $taskService,
        private readonly TaskQueryService $taskQueryService,
        private readonly TaskViewFactory $taskViewFactory,
        private readonly TaskSummaryViewFactory $taskSummaryViewFactory,
        private readonly TaskRepository $taskRepository,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('/{id}', name: 'api_task_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $task = $this->findTask($id);

        if (!$this->canViewTask($task, $user)) {
            throw ApiProblemException::forbidden('You are not allowed to view this task.');
        }

        return $this->responseFactory->single($this->taskViewFactory->make($task));
    }

    #[Route('/list', name: 'api_task_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $restrictScope = !$this->hasPermission($user, 'perm_can_read_all_tasks');

        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['created_at', 'due_date', 'priority', 'status', 'title']);

        $filters = [
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'project_id' => $this->optionalUuid($request->query->get('project_id'), 'project_id'),
            'assigned_to_user_id' => $this->optionalUuid($request->query->get('assigned_to_user_id'), 'assigned_to_user_id'),
            'created_by_user_id' => $this->optionalUuid($request->query->get('created_by_user_id'), 'created_by_user_id'),
            'due_date_from' => $this->optionalDate($request->query->get('due_date_from'), 'due_date_from'),
            'due_date_to' => $this->optionalDate($request->query->get('due_date_to'), 'due_date_to'),
            'q' => $request->query->get('q'),
        ];

        $result = $this->taskQueryService->list($user, $filters, $restrictScope, $offset, $limit, $sortBy, $direction);

        $items = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('', name: 'api_task_create', methods: ['POST'])]
    #[IsGranted('perm:perm_can_create_tasks')]
    public function create(CreateTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $task = $this->taskService->create($user, $request);

        $this->auditLogger->record('task.create', $user, [
            'task_id' => $task->getId()?->toRfc4122(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'project_id' => $task->getProject()?->getId()?->toRfc4122(),
            'assigned_to_user_id' => $task->getAssignedToUser()?->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single(
            $this->taskViewFactory->make($task),
            [],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_task_update', methods: ['PATCH'])]
    #[IsGranted('perm:perm_can_edit_tasks')]
    public function update(string $id, UpdateTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $updated = $this->taskService->update($task, $request);

        $this->auditLogger->record('task.update', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'project_id' => $updated->getProject()?->getId()?->toRfc4122(),
            'assigned_to_user_id' => $updated->getAssignedToUser()?->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    #[IsGranted('perm:perm_can_delete_tasks')]
    public function delete(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $taskId = $task->getId()?->toRfc4122();
        $projectId = $task->getProject()?->getId()?->toRfc4122();
        $this->taskService->delete($task);

        $this->auditLogger->record('task.delete', $actor, [
            'task_id' => $taskId,
            'project_id' => $projectId,
        ]);

        return $this->responseFactory->single(['message' => 'Task deleted.']);
    }

    #[Route('/{id}/assign-user', name: 'api_task_assign_user', methods: ['POST'])]
    #[IsGranted('perm:perm_can_assign_tasks_to_user')]
    public function assignUser(string $id, AssignUserRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $previousAssignee = $task->getAssignedToUser()?->getId()?->toRfc4122();
        $updated = $this->taskService->assignToUser($task, $request->userId);

        $this->auditLogger->record('task.assign_user', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'assigned_to_user_id' => $updated->getAssignedToUser()?->getId()?->toRfc4122(),
            'previous_assigned_to_user_id' => $previousAssignee,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/unassign-user', name: 'api_task_unassign_user', methods: ['POST'])]
    #[IsGranted('perm:perm_can_assign_tasks_to_user')]
    public function unassignUser(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $previousAssignee = $task->getAssignedToUser()?->getId()?->toRfc4122();
        $updated = $this->taskService->unassign($task);

        $this->auditLogger->record('task.unassign_user', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'previous_assigned_to_user_id' => $previousAssignee,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/move-to-project', name: 'api_task_move_to_project', methods: ['POST'])]
    #[IsGranted('perm:perm_can_assign_tasks_to_project')]
    public function moveToProject(string $id, MoveTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $previousProjectId = $task->getProject()?->getId()?->toRfc4122();
        $updated = $this->taskService->moveToProject($task, $request->projectId);

        $this->auditLogger->record('task.move_to_project', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'project_id' => $updated->getProject()?->getId()?->toRfc4122(),
            'previous_project_id' => $previousProjectId,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/status', name: 'api_task_update_status', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateStatus(string $id, UpdateTaskStatusRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $task = $this->findTask($id);

        if (!$this->canChangeStatus($task, $user)) {
            throw ApiProblemException::forbidden('You are not allowed to change the status of this task.');
        }

        $previousStatus = $task->getStatus();
        $updated = $this->taskService->changeStatus($task, $request->status);

        $this->auditLogger->record('task.status_update', $user, [
            'task_id' => $task->getId()?->toRfc4122(),
            'status' => $request->status,
            'previous_status' => $previousStatus,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    private function canViewTask(Task $task, User $user): bool
    {
        $userId = $user->getId();
        if ($userId !== null && $task->getCreatedByUser()?->getId()?->equals($userId)) {
            return true;
        }

        if ($userId !== null && $task->getAssignedToUser()?->getId()?->equals($userId)) {
            return true;
        }

        if ($this->hasPermission($user, 'perm_can_read_all_tasks')) {
            return true;
        }

        $project = $task->getProject();
        if ($project instanceof Project) {
            $projectOwnerId = $project->getCreatedByUser()?->getId();
            if ($projectOwnerId !== null && $userId !== null && $projectOwnerId->equals($userId)) {
                return true;
            }
        }

        return false;
    }

    private function canChangeStatus(Task $task, User $user): bool
    {
        if ($this->hasPermission($user, 'perm_can_edit_tasks')) {
            return true;
        }

        $userId = $user->getId();

        if ($userId !== null && $task->getAssignedToUser()?->getId()?->equals($userId)) {
            return true;
        }

        return $userId !== null && $task->getCreatedByUser()?->getId()?->equals($userId);
    }

    private function resolvePagination(Request $request, array $allowedSorts): array
    {
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit = (int) $request->query->get('limit', 100);
        if ($limit < 1) {
            throw ApiProblemException::validation(['limit' => ['Limit must be at least 1.']]);
        }
        $limit = min($limit, 200);

        $sortBy = (string) $request->query->get('sort_by', $allowedSorts[0] ?? 'created_at');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = $allowedSorts[0] ?? 'created_at';
        }

        $direction = strtolower((string) $request->query->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        return [$offset, $limit, $sortBy, $direction];
    }

    private function optionalUuid(?string $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Uuid::fromString($value)->toRfc4122();
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }

    private function optionalDate(?string $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiProblemException::validation([$field => ['Invalid date format.']]);
        }
    }

    private function findTask(string $id): Task
    {
        $uuid = $this->toUuid($id, 'id');
        $task = $this->taskRepository->find($uuid);

        if (!$task instanceof Task) {
            throw ApiProblemException::notFound('Task not found.');
        }

        return $task;
    }

    private function requireUser(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        return $user;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        $resolved = $this->permissionRegistry->resolve($user);

        return $resolved[$permission] ?? false;
    }

    private function toUuid(string $value, string $field): Uuid
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }
}

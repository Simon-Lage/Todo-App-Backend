<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Repository\TaskRepository;
use App\Security\Permission\PermissionEnum;
use App\Security\Permission\PermissionRegistry;
use App\Security\Voter\TaskVoter;
use App\Task\Dto\AssignUserRequest;
use App\Task\Dto\AssignUsersRequest;
use App\Task\Dto\BeautifyTaskRequest;
use App\Task\Dto\CreateTaskRequest;
use App\Task\Dto\MoveTaskRequest;
use App\Task\Dto\UnassignUserRequest;
use App\Task\Dto\UpdateTaskRequest;
use App\Task\Dto\UpdateTaskStatusRequest;
use App\Ai\Service\TaskTextEnhancer;
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
        private readonly AuditLogger $auditLogger,
        private readonly TaskTextEnhancer $taskTextEnhancer
    ) {
    }

    #[Route('/list', name: 'api_task_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);

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

        if ($this->permissionRegistry->has($user, PermissionEnum::CAN_READ_ALL_TASKS->value)) {
            $result = $this->taskQueryService->listForTeamLead($user, $filters, $offset, $limit, $sortBy, $direction);
        } else {
            $result = $this->taskQueryService->list($user, $filters, true, $offset, $limit, $sortBy, $direction);
        }

        $items = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/lead/list', name: 'api_task_lead_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function leadList(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        if (!$this->permissionRegistry->has($user, PermissionEnum::CAN_READ_ALL_TASKS->value)) {
            throw ApiProblemException::forbidden('Only teamleads can access this task list.');
        }

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

        $result = $this->taskQueryService->listForTeamLead($user, $filters, $offset, $limit, $sortBy, $direction);
        $items = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/dashboard-stats', name: 'api_task_dashboard_stats', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function dashboardStats(#[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);

        $assignedTotal = $this->taskRepository->countAssignedToUser($user);
        $assignedInProgress = $this->taskRepository->countAssignedToUser($user, 'in_progress');
        $doneTotal = $this->taskRepository->countDoneForDashboard($user);

        return $this->responseFactory->single([
            'my_tasks_total' => $assignedTotal,
            'my_tasks_in_progress' => $assignedInProgress,
            'my_tasks_done_total' => $doneTotal,
        ]);
    }

    #[Route('/{id}', name: 'api_task_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $this->requireUser($currentUser);
        $task = $this->findTask($id);

        $this->denyAccessUnlessGranted(TaskVoter::VIEW, $task);

        return $this->responseFactory->single($this->taskViewFactory->make($task));
    }

    #[Route('', name: 'api_task_create', methods: ['POST'])]
    #[IsGranted(TaskVoter::CREATE)]
    public function create(CreateTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $task = $this->taskService->create($user, $request);

        $assignedUserIds = [];
        foreach ($task->getAssignedUsers() as $assignedUser) {
            $assignedUserIds[] = $assignedUser->getId()?->toRfc4122();
        }

        $this->auditLogger->record('task.create', $user, [
            'task_id' => $task->getId()?->toRfc4122(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'project_id' => $task->getProject()?->getId()?->toRfc4122(),
            'assigned_user_ids' => $assignedUserIds,
        ]);

        return $this->responseFactory->single(
            $this->taskViewFactory->make($task),
            [],
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/beautify-text', name: 'api_task_beautify_text', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function beautifyText(BeautifyTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $this->requireUser($currentUser);

        $suggestion = $this->taskTextEnhancer->improve($request->description, $request->title);

        return $this->responseFactory->single(['suggestion' => $suggestion]);
    }

    #[Route('/{id}', name: 'api_task_update', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(string $id, UpdateTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $this->denyAccessUnlessGranted(TaskVoter::EDIT, $task);
        
        $updated = $this->taskService->update($task, $request);

        $assignedUserIds = [];
        foreach ($updated->getAssignedUsers() as $assignedUser) {
            $assignedUserIds[] = $assignedUser->getId()?->toRfc4122();
        }

        $this->auditLogger->record('task.update', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'project_id' => $updated->getProject()?->getId()?->toRfc4122(),
            'assigned_user_ids' => $assignedUserIds,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $this->denyAccessUnlessGranted(TaskVoter::DELETE, $task);
        
        $taskId = $task->getId()?->toRfc4122();
        $projectId = $task->getProject()?->getId()?->toRfc4122();
        $this->taskService->delete($task);

        $this->auditLogger->record('task.delete', $actor, [
            'task_id' => $taskId,
            'project_id' => $projectId,
        ]);

        return $this->responseFactory->single(['message' => 'Task deleted.']);
    }

    #[Route('/{id}/assign-users', name: 'api_task_assign_users', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function assignUsers(string $id, AssignUsersRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $previousAssignees = [];
        foreach ($task->getAssignedUsers() as $user) {
            $previousAssignees[] = $user->getId()?->toRfc4122();
        }
        
        $updated = $this->taskService->assignUsers($task, $request->userIds, $actor);

        $newAssignees = [];
        foreach ($updated->getAssignedUsers() as $user) {
            $newAssignees[] = $user->getId()?->toRfc4122();
        }

        $this->auditLogger->record('task.assign_users', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'assigned_user_ids' => $newAssignees,
            'previous_assigned_user_ids' => $previousAssignees,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/assign-user', name: 'api_task_assign_user', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function assignUser(string $id, AssignUserRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $updated = $this->taskService->assignUser($task, $request->userId, $actor);

        $this->auditLogger->record('task.assign_user', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'added_user_id' => $request->userId,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/unassign-user', name: 'api_task_unassign_user', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unassignUser(string $id, UnassignUserRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $updated = $this->taskService->unassignUser($task, $request->userId, $actor);

        $this->auditLogger->record('task.unassign_user', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'removed_user_id' => $request->userId,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/clear-assignees', name: 'api_task_clear_assignees', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function clearAssignees(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        
        $previousAssignees = [];
        foreach ($task->getAssignedUsers() as $user) {
            $previousAssignees[] = $user->getId()?->toRfc4122();
        }
        
        $updated = $this->taskService->clearAssignees($task, $actor);

        $this->auditLogger->record('task.clear_assignees', $actor, [
            'task_id' => $task->getId()?->toRfc4122(),
            'previous_assigned_user_ids' => $previousAssignees,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
    }

    #[Route('/{id}/move-to-project', name: 'api_task_move_to_project', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function moveToProject(string $id, MoveTaskRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $task = $this->findTask($id);
        $previousProjectId = $task->getProject()?->getId()?->toRfc4122();
        $updated = $this->taskService->moveToProject($task, $request->projectId, $actor);

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

        $this->denyAccessUnlessGranted(TaskVoter::STATUS, $task);

        $previousStatus = $task->getStatus();
        $updated = $this->taskService->changeStatus($task, $request->status, $user);

        $this->auditLogger->record('task.status_update', $user, [
            'task_id' => $task->getId()?->toRfc4122(),
            'status' => $request->status,
            'previous_status' => $previousStatus,
        ]);

        return $this->responseFactory->single($this->taskViewFactory->make($updated));
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

        if (!$user->isActive()) {
            throw ApiProblemException::fromStatus(403, 'Forbidden', 'Account is inactive.', 'USED_ACCOUNT_IS_INACTIVE');
        }

        return $user;
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

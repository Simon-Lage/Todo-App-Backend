<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Project\Dto\CreateProjectRequest;
use App\Project\Dto\UpdateProjectRequest;
use App\Project\Service\ProjectQueryService;
use App\Project\Service\ProjectService;
use App\Project\View\ProjectSummaryViewFactory;
use App\Project\View\ProjectViewFactory;
use App\Repository\ProjectRepository;
use App\Security\Permission\PermissionRegistry;
use App\Task\Service\TaskQueryService;
use App\Task\View\TaskSummaryViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/project')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly ProjectService $projectService,
        private readonly ProjectQueryService $projectQueryService,
        private readonly ProjectViewFactory $projectViewFactory,
        private readonly ProjectSummaryViewFactory $projectSummaryViewFactory,
        private readonly TaskSummaryViewFactory $taskSummaryViewFactory,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly ProjectRepository $projectRepository,
        private readonly TaskQueryService $taskQueryService,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('/list', name: 'api_project_list', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_projects')]
    public function list(Request $request): JsonResponse
    {
        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['created_at', 'name']);

        $filters = [
            'q' => $request->query->get('q'),
            'created_by_user_id' => $this->optionalUuid($request->query->get('created_by_user_id'), 'created_by_user_id'),
        ];

        $result = $this->projectQueryService->list($filters, $offset, $limit, $sortBy, $direction);

        $items = array_map(fn(Project $project) => $this->projectSummaryViewFactory->make($project), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/{id}', name: 'api_project_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $project = $this->findProject($id);

        if (!$this->isProjectOwner($project, $user) && !$this->hasPermission($user, 'perm_can_read_projects')) {
            throw ApiProblemException::forbidden('Insufficient permissions to read this project.');
        }

        return $this->responseFactory->single($this->projectViewFactory->make($project));
    }

    #[Route('', name: 'api_project_create', methods: ['POST'])]
    #[IsGranted('perm:perm_can_create_projects')]
    public function create(CreateProjectRequest $request, #[CurrentUser] ?UserInterface $creator): JsonResponse
    {
        $user = $this->requireUser($creator);

        $project = $this->projectService->create($user, $request->name, $request->description);

        $this->auditLogger->record('project.create', $user, [
            'project_id' => $project->getId()?->toRfc4122(),
            'name' => $project->getName(),
        ]);

        return $this->responseFactory->single(
            $this->projectViewFactory->make($project),
            [],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_project_update', methods: ['PATCH'])]
    #[IsGranted('perm:perm_can_edit_projects')]
    public function update(string $id, UpdateProjectRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $project = $this->findProject($id);
        $updated = $this->projectService->update($project, $request->name, $request->description);

        $this->auditLogger->record('project.update', $actor, [
            'project_id' => $project->getId()?->toRfc4122(),
            'name' => $updated->getName(),
        ]);

        return $this->responseFactory->single($this->projectViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_project_delete', methods: ['DELETE'])]
    #[IsGranted('perm:perm_can_delete_projects')]
    public function delete(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $project = $this->findProject($id);
        $projectId = $project->getId()?->toRfc4122();
        $this->projectService->delete($project);

        $this->auditLogger->record('project.delete', $actor, [
            'project_id' => $projectId,
        ]);

        return $this->responseFactory->single(['message' => 'Project deleted.']);
    }

    #[Route('/{id}/tasks', name: 'api_project_tasks', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function tasks(string $id, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $project = $this->findProject($id);

        $owner = $this->isProjectOwner($project, $user);
        if (!$owner && (!$this->hasPermission($user, 'perm_can_read_projects') || !$this->hasPermission($user, 'perm_can_read_all_tasks'))) {
            throw ApiProblemException::forbidden('Insufficient permissions to list tasks for this project.');
        }

        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['created_at', 'due_date', 'priority', 'status']);

        $filters = [
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'project_id' => $project->getId()?->toRfc4122(),
            'assigned_to_user_id' => $this->optionalUuid($request->query->get('assigned_to_user_id'), 'assigned_to_user_id'),
            'created_by_user_id' => $this->optionalUuid($request->query->get('created_by_user_id'), 'created_by_user_id'),
            'due_date_from' => $this->optionalDate($request->query->get('due_date_from'), 'due_date_from'),
            'due_date_to' => $this->optionalDate($request->query->get('due_date_to'), 'due_date_to'),
            'q' => $request->query->get('q'),
        ];

        $restrictScope = false;
        $result = $this->taskQueryService->list($user, $filters, $restrictScope, $offset, $limit, $sortBy, $direction);
        $items = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
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

    private function hasPermission(User $user, string $permission): bool
    {
        $resolved = $this->permissionRegistry->resolve($user);

        return $resolved[$permission] ?? false;
    }

    private function isProjectOwner(Project $project, User $user): bool
    {
        $projectOwnerId = $project->getCreatedByUser()?->getId();
        $userId = $user->getId();

        return $projectOwnerId !== null && $userId !== null && $projectOwnerId->equals($userId);
    }

    private function findProject(string $id): Project
    {
        $uuid = $this->toUuid($id, 'id');
        $project = $this->projectRepository->find($uuid);

        if (!$project instanceof Project) {
            throw ApiProblemException::notFound('Project not found.');
        }

        return $project;
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

        return $this->toUuid($value, $field)->toRfc4122();
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

    private function toUuid(string $value, string $field): Uuid
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }
}

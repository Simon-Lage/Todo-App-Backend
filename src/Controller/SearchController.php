<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\View\LogViewFactory;
use App\Project\View\ProjectSummaryViewFactory;
use App\Search\Service\SearchService;
use App\Security\Permission\PermissionRegistry;
use App\Task\View\TaskSummaryViewFactory;
use App\User\View\UserListViewFactory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/search')]
final class SearchController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly SearchService $searchService,
        private readonly UserListViewFactory $userListViewFactory,
        private readonly ProjectSummaryViewFactory $projectSummaryViewFactory,
        private readonly TaskSummaryViewFactory $taskSummaryViewFactory,
        private readonly LogViewFactory $logViewFactory,
        private readonly PermissionRegistry $permissionRegistry
    ) {
    }

    #[Route('/{term}', name: 'api_search_all', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchAll(string $term, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $term = $this->sanitizeTerm($term);
        $limit = $this->resolveLimit($request->query->get('limit', 20));

        $results = [];

        if ($this->canReadUsers($user)) {
            $users = $this->searchService->searchUsers($term, $limit);
            $results['users'] = array_map(fn(User $u) => $this->userListViewFactory->make($u), $users);
        } else {
            $results['users'] = [];
        }

        if ($this->canReadProjects($user)) {
            $projects = $this->searchService->searchProjects($term, $limit);
            $projects = array_filter($projects, fn(Project $project) => $this->projectVisible($project, $user));
            $results['projects'] = array_map(fn(Project $p) => $this->projectSummaryViewFactory->make($p), $projects);
        } else {
            $results['projects'] = [];
        }

        if ($this->canReadTasks($user)) {
            $tasks = $this->filterTasksByPermission(
                $this->searchService->searchTasks($term, $limit),
                $user,
                $this->hasPermission($user, 'perm_can_read_all_tasks')
            );
            $results['tasks'] = array_map(fn(Task $t) => $this->taskSummaryViewFactory->make($t), $tasks);
        } else {
            $results['tasks'] = [];
        }

        if ($this->canReadLogs($user)) {
            $logs = $this->searchService->searchLogs($term, $limit);
            $results['logs'] = array_map(fn($log) => $this->logViewFactory->make($log), $logs);
        } else {
            $results['logs'] = [];
        }

        return $this->responseFactory->single($results);
    }

    #[Route('/user/{term}', name: 'api_search_user', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function searchUsersRoute(string $term, Request $request): JsonResponse
    {
        $term = $this->sanitizeTerm($term);
        $limit = $this->resolveLimit($request->query->get('limit', 20));
        $active = $request->query->get('active');
        $filters = ['active' => $active === null ? null : in_array(strtolower((string) $active), ['1', 'true', 'yes'], true)];
        $users = $this->searchService->searchUsers($term, $limit, $filters);

        return $this->responseFactory->single([
            'items' => array_map(fn(User $u) => $this->userListViewFactory->make($u), $users),
            'total' => count($users),
        ]);
    }

    #[Route('/project/{term}', name: 'api_search_project', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_projects')]
    public function searchProjectsRoute(string $term, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $term = $this->sanitizeTerm($term);
        $limit = $this->resolveLimit($request->query->get('limit', 20));
        $filters = [];
        if ($request->query->get('created_by_user_id')) {
            $filters['created_by_user_id'] = $this->parseUuid($request->query->get('created_by_user_id'), 'created_by_user_id');
        }

        $projects = $this->searchService->searchProjects($term, $limit, $filters);
        $projects = array_filter($projects, fn(Project $project) => $this->projectVisible($project, $actor));

        return $this->responseFactory->single([
            'items' => array_map(fn(Project $project) => $this->projectSummaryViewFactory->make($project), $projects),
            'total' => count($projects),
        ]);
    }

    #[Route('/task/{term}', name: 'api_search_task', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchTasksRoute(string $term, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $term = $this->sanitizeTerm($term);
        $limit = $this->resolveLimit($request->query->get('limit', 20));
        $filters = $this->collectTaskFilters($request);
        $restrictScope = !$this->hasPermission($actor, 'perm_can_read_all_tasks');

        $tasks = $this->filterTasksByPermission(
            $this->searchService->searchTasks($term, $limit, $filters),
            $actor,
            !$restrictScope
        );

        return $this->responseFactory->single([
            'items' => array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $tasks),
            'total' => count($tasks),
        ]);
    }

    #[Route('/logs/{term}', name: 'api_search_logs', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function searchLogsRoute(string $term, Request $request): JsonResponse
    {
        $term = $this->sanitizeTerm($term);
        $limit = $this->resolveLimit($request->query->get('limit', 20));
        $filters = [];
        if ($request->query->get('performed_by_user_id')) {
            $filters['performed_by_user_id'] = $this->parseUuid($request->query->get('performed_by_user_id'), 'performed_by_user_id');
        }
        if ($request->query->get('action')) {
            $filters['action_exact'] = (string) $request->query->get('action');
        }

        $logs = $this->searchService->searchLogs($term, $limit, $filters);

        return $this->responseFactory->single([
            'items' => array_map(fn($log) => $this->logViewFactory->make($log), $logs),
            'total' => count($logs),
        ]);
    }

    #[Route('', name: 'api_search_complex', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function searchComplex(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'Malformed JSON payload.', 'PAYLOAD_INVALID');
        }

        if (!is_array($payload)) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'JSON payload must be an object.', 'PAYLOAD_INVALID');
        }

        $entity = isset($payload['entity']) ? strtolower(trim((string) $payload['entity'])) : null;
        $term = $this->sanitizeTerm((string) ($payload['q'] ?? ''));
        $limit = $this->resolveLimit($payload['limit'] ?? 20);
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];

        return match ($entity) {
            null => $this->searchAllEntities($term, $limit, $user, $filters),
            'user' => $this->searchUsersComplex($term, $limit, $user, $filters),
            'project' => $this->searchProjectsComplex($term, $limit, $user, $filters),
            'task' => $this->searchTasksComplex($term, $limit, $user, $filters),
            'logs' => $this->searchLogsComplex($term, $limit, $user, $filters),
            default => throw ApiProblemException::validation(['entity' => ['Unsupported entity for search.']]),
        };
    }

    private function searchAllEntities(string $term, int $limit, User $user, array $filters = []): JsonResponse
    {
        $results = [];

        if ($this->canReadUsers($user)) {
            $users = $this->searchService->searchUsers($term, $limit, $filters['users'] ?? []);
            $results['users'] = array_map(fn(User $u) => $this->userListViewFactory->make($u), $users);
        }

        if ($this->canReadProjects($user)) {
            $projectFilters = $this->normalizeProjectFilters($filters['projects'] ?? []);
            $projects = $this->searchService->searchProjects($term, $limit, $projectFilters);
            $projects = array_filter($projects, fn(Project $project) => $this->projectVisible($project, $user));
            $results['projects'] = array_map(fn(Project $p) => $this->projectSummaryViewFactory->make($p), $projects);
        }

        if ($this->canReadTasks($user)) {
            $taskFilters = $this->normalizeTaskFilters($filters['tasks'] ?? []);
            $restrictScope = !$this->hasPermission($user, 'perm_can_read_all_tasks');
            $tasks = $this->filterTasksByPermission(
                $this->searchService->searchTasks($term, $limit, $taskFilters),
                $user,
                !$restrictScope
            );
            $results['tasks'] = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $tasks);
        }

        if ($this->canReadLogs($user)) {
            $logFilters = $this->normalizeLogFilters($filters['logs'] ?? []);
            $logs = $this->searchService->searchLogs($term, $limit, $logFilters);
            $results['logs'] = array_map(fn($log) => $this->logViewFactory->make($log), $logs);
        }

        return $this->responseFactory->single($results);
    }

    private function searchUsersComplex(string $term, int $limit, User $user, array $filters): JsonResponse
    {
        if (!$this->canReadUsers($user)) {
            throw ApiProblemException::forbidden('Missing permission to search users.');
        }

        $users = $this->searchService->searchUsers($term, $limit, $filters);

        return $this->responseFactory->single([
            'items' => array_map(fn(User $u) => $this->userListViewFactory->make($u), $users),
            'total' => count($users),
        ]);
    }

    private function searchProjectsComplex(string $term, int $limit, User $user, array $filters): JsonResponse
    {
        if (!$this->canReadProjects($user)) {
            throw ApiProblemException::forbidden('Missing permission to search projects.');
        }

        $projectFilters = $this->normalizeProjectFilters($filters);
        $projects = $this->searchService->searchProjects($term, $limit, $projectFilters);
        $projects = array_filter($projects, fn(Project $project) => $this->projectVisible($project, $user));

        return $this->responseFactory->single([
            'items' => array_map(fn(Project $p) => $this->projectSummaryViewFactory->make($p), $projects),
            'total' => count($projects),
        ]);
    }

    private function searchTasksComplex(string $term, int $limit, User $user, array $filters): JsonResponse
    {
        $taskFilters = $this->normalizeTaskFilters($filters);
        $restrictScope = !$this->hasPermission($user, 'perm_can_read_all_tasks');
        $tasks = $this->filterTasksByPermission(
            $this->searchService->searchTasks($term, $limit, $taskFilters),
            $user,
            !$restrictScope
        );

        return $this->responseFactory->single([
            'items' => array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $tasks),
            'total' => count($tasks),
        ]);
    }

    private function searchLogsComplex(string $term, int $limit, User $user, array $filters): JsonResponse
    {
        if (!$this->canReadLogs($user)) {
            throw ApiProblemException::forbidden('Missing permission to search logs.');
        }

        $logFilters = $this->normalizeLogFilters($filters);
        $logs = $this->searchService->searchLogs($term, $limit, $logFilters);

        return $this->responseFactory->single([
            'items' => array_map(fn($log) => $this->logViewFactory->make($log), $logs),
            'total' => count($logs),
        ]);
    }

    private function collectTaskFilters(Request $request): array
    {
        $filters = [];

        if ($request->query->get('project_id')) {
            $filters['project_id'] = $this->parseUuid($request->query->get('project_id'), 'project_id');
        }
        if ($request->query->get('assigned_to_user_id')) {
            $filters['assigned_to_user_id'] = $this->parseUuid($request->query->get('assigned_to_user_id'), 'assigned_to_user_id');
        }
        if ($request->query->get('created_by_user_id')) {
            $filters['created_by_user_id'] = $this->parseUuid($request->query->get('created_by_user_id'), 'created_by_user_id');
        }
        if ($request->query->get('status')) {
            $filters['status'] = (string) $request->query->get('status');
        }
        if ($request->query->get('priority')) {
            $filters['priority'] = (string) $request->query->get('priority');
        }
        if ($request->query->get('due_date_from')) {
            $filters['due_date_from'] = $this->parseDate($request->query->get('due_date_from'), 'due_date_from');
        }
        if ($request->query->get('due_date_to')) {
            $filters['due_date_to'] = $this->parseDate($request->query->get('due_date_to'), 'due_date_to');
        }

        return $filters;
    }

    private function normalizeProjectFilters(array $filters): array
    {
        if (isset($filters['created_by_user_id'])) {
            $filters['created_by_user_id'] = $this->parseUuid($filters['created_by_user_id'], 'created_by_user_id');
        }

        return $filters;
    }

    private function normalizeTaskFilters(array $filters): array
    {
        foreach (['project_id', 'assigned_to_user_id', 'created_by_user_id'] as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = $this->parseUuid($filters[$field], $field);
            }
        }

        foreach (['due_date_from', 'due_date_to'] as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = $this->parseDate($filters[$field], $field);
            }
        }

        return $filters;
    }

    private function normalizeLogFilters(array $filters): array
    {
        if (isset($filters['performed_by_user_id'])) {
            $filters['performed_by_user_id'] = $this->parseUuid($filters['performed_by_user_id'], 'performed_by_user_id');
        }
        if (isset($filters['from'])) {
            $filters['from'] = $this->parseDate($filters['from'], 'from');
        }
        if (isset($filters['to'])) {
            $filters['to'] = $this->parseDate($filters['to'], 'to');
        }

        return $filters;
    }

    private function filterTasksByPermission(array $tasks, User $user, bool $hasGlobalPermission): array
    {
        if ($hasGlobalPermission) {
            return $tasks;
        }

        $userId = $user->getId();
        $filtered = [];
        foreach ($tasks as $task) {
            if (!$task instanceof Task) {
                continue;
            }

            if ($userId !== null && ($task->getCreatedByUser()?->getId()?->equals($userId) || $task->getAssignedToUser()?->getId()?->equals($userId))) {
                $filtered[] = $task;
                continue;
            }

            $project = $task->getProject();
            if ($project instanceof Project && $project->getCreatedByUser()?->getId()?->equals($userId)) {
                $filtered[] = $task;
            }
        }

        return $filtered;
    }

    private function projectVisible(Project $project, User $user): bool
    {
        if ($project->getCreatedByUser()?->getId()?->equals($user->getId() ?? Uuid::v4())) {
            return true;
        }

        return $this->hasPermission($user, 'perm_can_read_projects');
    }

    private function canReadUsers(User $user): bool
    {
        return $this->hasPermission($user, 'perm_can_read_user');
    }

    private function canReadProjects(User $user): bool
    {
        return $this->hasPermission($user, 'perm_can_read_projects');
    }

    private function canReadTasks(User $user): bool
    {
        return $this->hasPermission($user, 'perm_can_create_tasks') || $this->hasPermission($user, 'perm_can_read_all_tasks');
    }

    private function canReadLogs(User $user): bool
    {
        return $this->hasPermission($user, 'perm_can_read_user');
    }

    private function requireUser(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        return $user;
    }

    private function sanitizeTerm(string $term): string
    {
        $term = trim($term);
        if ($term === '') {
            throw ApiProblemException::validation(['q' => ['Search term must not be empty.']]);
        }

        return $term;
    }

    private function resolveLimit(mixed $value): int
    {
        $limit = (int) $value;
        if ($limit < 1) {
            $limit = 1;
        }

        return min(200, $limit);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $this->permissionRegistry->resolve($user)[$permission] ?? false;
    }

    private function parseUuid(mixed $value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }

        try {
            return Uuid::fromString($value)->toRfc4122();
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }

    private function parseDate(mixed $value, string $field): DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw ApiProblemException::validation([$field => ['Invalid date.']]);
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiProblemException::validation([$field => ['Invalid date format.']]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\Service\PasswordResetTokenService;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Repository\ProjectRepository;
use App\Repository\RoleRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Log\Service\AuditLogger;
use App\Security\Permission\PermissionRegistry;
use App\Task\View\TaskSummaryViewFactory;
use App\Project\View\ProjectSummaryViewFactory;
use App\User\Dto\AdminUpdateUserRequest;
use App\User\Dto\CreateUserRequest;
use App\User\Dto\ForgotPasswordRequest;
use App\User\Dto\SelfUpdateUserRequest;
use App\User\Service\UserNotificationService;
use App\User\Service\UserQueryService;
use App\User\Service\UserService;
use App\User\View\UserListViewFactory;
use App\User\View\UserViewFactory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly UserService $userService,
        private readonly UserNotificationService $notificationService,
        private readonly PasswordResetTokenService $passwordResetTokenService,
        private readonly UserQueryService $userQueryService,
        private readonly UserViewFactory $userViewFactory,
        private readonly UserListViewFactory $userListViewFactory,
        private readonly TaskSummaryViewFactory $taskSummaryViewFactory,
        private readonly ProjectSummaryViewFactory $projectSummaryViewFactory,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('', name: 'api_user_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $user = $this->requireUser($user);

        return $this->responseFactory->single([
            'user' => $this->userViewFactory->make($user),
            'permissions' => $this->permissionRegistry->resolve($user),
        ]);
    }

    #[Route('/permissions', name: 'api_user_permissions', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function permissions(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $user = $this->requireUser($user);

        return $this->responseFactory->single($this->permissionRegistry->resolve($user));
    }

    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->findUser($id);

        $current = $this->requireUser($currentUser);

        if ($current->getId()?->equals($user->getId()) === false) {
            $this->denyAccessUnlessGranted('perm:perm_can_read_user');
        }

        return $this->responseFactory->single($this->userViewFactory->make($user));
    }

    #[Route('/list', name: 'api_user_list', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function list(Request $request): JsonResponse
    {
        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['name', 'email', 'created_at', 'last_login_at']);

        $filters = [
            'q' => $request->query->get('q'),
            'active' => $this->resolveOptionalBoolean($request->query->get('active')),
            'role_id' => $this->resolveOptionalUuid($request->query->get('role_id'), 'role_id'),
        ];

        $result = $this->userQueryService->list($filters, $offset, $limit, $sortBy, $direction);
        $items = array_map(fn(User $user) => $this->userListViewFactory->make($user), $result['items']);

        return $this->responseFactory->collection($items, $result['total'], $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/by-role/{roleId}', name: 'api_user_by_role', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function byRole(string $roleId): JsonResponse
    {
        $role = $this->roleRepository->find($this->toUuid($roleId, 'role_id'));
        if ($role === null) {
            throw ApiProblemException::notFound('Role not found.');
        }

        $users = $this->userQueryService->usersByRole($roleId);
        $items = array_map(fn(User $user) => $this->userListViewFactory->make($user), $users);

        return $this->responseFactory->single(['items' => $items, 'total' => count($items)]);
    }

    #[Route('', name: 'api_user_create', methods: ['POST'])]
    #[IsGranted('perm:perm_can_crate_user')]
    public function create(CreateUserRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->userService->create($request->name, $request->email, $request->password, $request->active, $request->roles);

        $this->auditLogger->record('user.create', $actor, [
            'user_id' => $user->getId()?->toRfc4122(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $request->roles,
        ]);

        return $this->responseFactory->single(
            ['user' => $this->userViewFactory->make($user)],
            [],
            Response::HTTP_CREATED
        );
    }

    #[Route('', name: 'api_user_update_self', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateSelf(SelfUpdateUserRequest $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $account = $this->requireUser($user);
        $updated = $this->userService->updateSelf($account, $request->name);

        $this->auditLogger->record('user.self_update', $account, [
            'user_id' => $account->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single($this->userViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_user_update_admin', methods: ['PATCH'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function updateAdmin(string $id, AdminUpdateUserRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->findUser($id);
        $updated = $this->userService->update($user, $request->name, $request->email, $request->active, $request->roles);

        $this->auditLogger->record('user.update', $actor, [
            'target_user_id' => $user->getId()?->toRfc4122(),
            'roles' => $request->roles,
        ]);

        return $this->responseFactory->single($this->userViewFactory->make($updated));
    }

    #[Route('/{id}/deactivate', name: 'api_user_deactivate', methods: ['POST'])]
    #[IsGranted('perm:perm_can_delete_user')]
    public function deactivate(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->findUser($id);
        $this->userService->deactivate($user);

        $this->auditLogger->record('user.deactivate', $actor, [
            'target_user_id' => $user->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single(['message' => 'User deactivated.']);
    }

    #[Route('/{id}/reactivate', name: 'api_user_reactivate', methods: ['POST'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function reactivate(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->findUser($id);
        $this->userService->reactivate($user);

        $this->auditLogger->record('user.reactivate', $actor, [
            'target_user_id' => $user->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single(['message' => 'User reactivated.']);
    }

    #[Route('/reset-password/{id}', name: 'api_user_reset_password_admin', methods: ['POST'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function resetPasswordForUser(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->findUser($id);
        $temporaryPassword = $this->userService->generateTemporaryPassword();
        $this->userService->setTemporaryPassword($user, $temporaryPassword);
        $this->notificationService->sendTemporaryPassword($user, $temporaryPassword);

        $this->auditLogger->record('user.reset_password_admin', $actor, [
            'target_user_id' => $user->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single(['message' => 'Temporary password issued.']);
    }

    #[Route('/reset-password', name: 'api_user_reset_password_self', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function resetPasswordSelf(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $account = $this->requireUser($user);

        $token = $this->passwordResetTokenService->create($account);
        $this->notificationService->sendPasswordResetLink($account, $token);

        $this->auditLogger->record('user.reset_password_self', $account, [
            'user_id' => $account->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single(['message' => 'Reset link sent.']);
    }

    #[Route('/forgot-password', name: 'api_user_forgot_password', methods: ['POST'])]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => strtolower($request->email)]);

        if ($user instanceof User && $user->isActive()) {
            $token = $this->passwordResetTokenService->create($user);
            $this->notificationService->sendPasswordResetLink($user, $token);
        }

        return $this->responseFactory->single(['message' => 'If the account exists, a reset link has been sent.']);
    }

    #[Route('/{id}/tasks', name: 'api_user_tasks', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function tasks(string $id, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->findUser($id);

        $actor = $this->requireUser($currentUser);

        if ($actor->getId()?->equals($user->getId()) === false) {
            $this->denyAccessUnlessGranted('perm:perm_can_read_all_tasks');
        }

        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['created_at', 'due_date', 'priority', 'status']);

        $qb = $this->taskRepository->createQueryBuilder('t')
            ->andWhere('t.created_by_user = :user OR t.assigned_to_user = :user')
            ->setParameter('user', $user);

        if ($request->query->get('status') !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $request->query->get('status'));
        }

        if ($request->query->get('priority') !== null) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $request->query->get('priority'));
        }

        if ($request->query->get('project_id') !== null) {
            $projectId = $this->toUuid($request->query->get('project_id'), 'project_id');
            $qb->andWhere('IDENTITY(t.project) = :projectId')->setParameter('projectId', $projectId->toRfc4122());
        }

        if ($request->query->get('due_date_from') !== null) {
            $from = $this->parseDate($request->query->get('due_date_from'), 'due_date_from');
            $qb->andWhere('t.due_date >= :dueFrom')->setParameter('dueFrom', $from);
        }

        if ($request->query->get('due_date_to') !== null) {
            $to = $this->parseDate($request->query->get('due_date_to'), 'due_date_to');
            $qb->andWhere('t.due_date <= :dueTo')->setParameter('dueTo', $to);
        }

        $count = (int) (clone $qb)->select('COUNT(DISTINCT t.id)')->getQuery()->getSingleScalarResult();

        $tasks = $qb
            ->orderBy($this->resolveTaskSortField($sortBy), strtoupper($direction))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = array_map(fn(Task $task) => $this->taskSummaryViewFactory->make($task), $tasks);

        return $this->responseFactory->collection($items, $count, $offset, $limit, $sortBy, strtoupper($direction));
    }

    #[Route('/{id}/projects', name: 'api_user_projects', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function projects(string $id, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->findUser($id);

        $actor = $this->requireUser($currentUser);

        if ($actor->getId()?->equals($user->getId()) === false) {
            $this->denyAccessUnlessGranted('perm:perm_can_read_projects');
        }

        [$offset, $limit, $sortBy, $direction] = $this->resolvePagination($request, ['created_at', 'name']);

        $qb = $this->projectRepository->createQueryBuilder('p')
            ->andWhere('p.created_by_user = :user')
            ->setParameter('user', $user);

        if ($request->query->get('q') !== null) {
            $term = '%'.strtolower((string) $request->query->get('q')).'%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term')->setParameter('term', $term);
        }

        $count = (int) (clone $qb)->select('COUNT(DISTINCT p.id)')->getQuery()->getSingleScalarResult();

        $projects = $qb
            ->orderBy($sortBy === 'name' ? 'p.name' : 'p.created_at', strtoupper($direction))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $items = array_map(fn(Project $project) => $this->projectSummaryViewFactory->make($project), $projects);

        return $this->responseFactory->collection($items, $count, $offset, $limit, $sortBy, strtoupper($direction));
    }

    private function findUser(string $id): User
    {
        $uuid = $this->toUuid($id, 'id');
        $user = $this->userRepository->find($uuid);

        if (!$user instanceof User) {
            throw ApiProblemException::notFound('User not found.');
        }

        return $user;
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

    private function resolveOptionalBoolean(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower($value);
        if (in_array($normalized, ['1', 'true', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no'], true)) {
            return false;
        }

        throw ApiProblemException::validation(['active' => ['Invalid boolean value.']]);
    }

    private function resolveOptionalUuid(?string $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $this->toUuid($value, $field);

        return $value;
    }

    private function toUuid(string $value, string $field): Uuid
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation([$field => ['Invalid UUID.']]);
        }
    }

    private function resolveTaskSortField(string $sortBy): string
    {
        return match ($sortBy) {
            'due_date' => 't.due_date',
            'priority' => 't.priority',
            'status' => 't.status',
            default => 't.created_at',
        };
    }

    private function requireUser(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        return $user;
    }

    private function parseDate(string $value, string $field): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiProblemException::validation([$field => ['Invalid date format.']]);
        }
    }
}

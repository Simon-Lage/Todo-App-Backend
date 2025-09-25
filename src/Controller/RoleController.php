<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Role\Dto\AssignRolesRequest;
use App\Role\Dto\CreateRoleRequest;
use App\Role\Dto\UpdateRoleRequest;
use App\Role\Service\RoleService;
use App\Role\View\RoleViewFactory;
use App\Security\Permission\PermissionRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/role')]
final class RoleController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly RoleRepository $roleRepository,
        private readonly UserRepository $userRepository,
        private readonly RoleService $roleService,
        private readonly RoleViewFactory $roleViewFactory,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('/list', name: 'api_role_list', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function list(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit = (int) $request->query->get('limit', 100);
        if ($limit < 1) {
            throw ApiProblemException::validation(['limit' => ['Limit must be at least 1.']]);
        }
        $limit = min($limit, 200);

        $qb = $this->roleRepository->createQueryBuilder('r')
            ->orderBy('r.id', 'ASC');

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $roles = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = [];
        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $items[] = $this->roleViewFactory->make($role);
            }
        }

        return $this->responseFactory->collection($items, $total, $offset, $limit, 'id', 'ASC');
    }

    #[Route('/{id}', name: 'api_role_show', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function show(string $id): JsonResponse
    {
        $role = $this->findRole($id);

        return $this->responseFactory->single($this->roleViewFactory->make($role));
    }

    #[Route('', name: 'api_role_create', methods: ['POST'])]
    #[IsGranted('perm:perm_can_crate_user')]
    public function create(CreateRoleRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $role = $this->roleService->create($request->permissions);

        $this->auditLogger->record('role.create', $actor, [
            'role_id' => $role->getId()?->toRfc4122(),
            'permissions' => $request->permissions,
        ]);

        return $this->responseFactory->single(
            $this->roleViewFactory->make($role),
            [],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_role_update', methods: ['PATCH'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function update(string $id, UpdateRoleRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $role = $this->findRole($id);
        $updated = $this->roleService->update($role, $request->permissions);

        $this->auditLogger->record('role.update', $actor, [
            'role_id' => $role->getId()?->toRfc4122(),
            'permissions' => $request->permissions,
        ]);

        return $this->responseFactory->single($this->roleViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_role_delete', methods: ['DELETE'])]
    #[IsGranted('perm:perm_can_delete_user')]
    public function delete(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $role = $this->findRole($id);
        $roleId = $role->getId()?->toRfc4122();
        $this->roleService->delete($role);

        $this->auditLogger->record('role.delete', $actor, [
            'role_id' => $roleId,
        ]);

        return $this->responseFactory->single(['message' => 'Role deleted.']);
    }

    #[Route('/by-user/{userId}', name: 'api_role_by_user', methods: ['GET'])]
    #[IsGranted('perm:perm_can_read_user')]
    public function rolesByUser(string $userId): JsonResponse
    {
        $user = $this->findUser($userId);
        $items = [];
        foreach ($user->getRoleEntities() as $role) {
            if ($role instanceof Role) {
                $items[] = $this->roleViewFactory->make($role);
            }
        }

        return $this->responseFactory->single(['items' => $items, 'total' => count($items)]);
    }

    #[Route('/by-user/{userId}', name: 'api_role_assign', methods: ['PATCH'])]
    #[IsGranted('perm:perm_can_edit_user')]
    public function assign(string $userId, AssignRolesRequest $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $actor = $this->requireUser($currentUser);
        $user = $this->findUser($userId);
        $updated = $this->roleService->assignRolesToUser($user, $request->roles);

        $this->auditLogger->record('role.assign', $actor, [
            'user_id' => $user->getId()?->toRfc4122(),
            'roles' => $request->roles,
        ]);

        $items = [];
        foreach ($updated->getRoleEntities() as $role) {
            if ($role instanceof Role) {
                $items[] = $this->roleViewFactory->make($role);
            }
        }

        return $this->responseFactory->single([
            'user_id' => $updated->getId()?->toRfc4122(),
            'roles' => $items,
        ]);
    }

    private function findRole(string $id): Role
    {
        $uuid = $this->toUuid($id, 'id');
        $role = $this->roleRepository->find($uuid);

        if (!$role instanceof Role) {
            throw ApiProblemException::notFound('Role not found.');
        }

        return $role;
    }

    private function findUser(string $id): User
    {
        $uuid = $this->toUuid($id, 'user_id');
        $user = $this->userRepository->find($uuid);

        if (!$user instanceof User) {
            throw ApiProblemException::notFound('User not found.');
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

    private function requireUser(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw ApiProblemException::unauthorized('Authentication is required.');
        }

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Http\Response\ApiResponseFactory;
use App\Log\Service\AuditLogger;
use App\Image\Service\ImageService;
use App\Image\View\ImageViewFactory;
use App\Repository\ImageRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Security\Permission\PermissionRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/image')]
final class ImageController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFactory $responseFactory,
        private readonly ImageService $imageService,
        private readonly ImageViewFactory $imageViewFactory,
        private readonly ImageRepository $imageRepository,
        private readonly UserRepository $userRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly PermissionRegistry $permissionRegistry,
        private readonly AuditLogger $auditLogger
    ) {
    }

    #[Route('', name: 'api_image_upload', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function upload(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);

        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$file->isValid()) {
            throw ApiProblemException::validation(['file' => ['Valid upload is required.']]);
        }

        $type = $request->request->get('type');
        if (!is_string($type) || trim($type) === '') {
            throw ApiProblemException::validation(['type' => ['Type is required.']]);
        }

        $userId = $request->request->get('user_id');
        $projectId = $request->request->get('project_id');
        $taskId = $request->request->get('task_id');

        $this->assertUploadPermissions($user, $userId, $projectId, $taskId);

        $image = $this->imageService->create($user, $file, $type, $userId, $projectId, $taskId);

        $this->auditLogger->record('image.upload', $user, [
            'image_id' => $image->getId()?->toRfc4122(),
            'type' => $image->getType(),
            'project_id' => $image->getProject()?->getId()?->toRfc4122(),
            'task_id' => $image->getTask()?->getId()?->toRfc4122(),
            'user_id' => $image->getUser()?->getId()?->toRfc4122(),
            'file_size' => $image->getFileSize(),
        ]);

        return $this->responseFactory->single($this->imageViewFactory->make($image), [], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_image_download', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function download(string $id, #[CurrentUser] ?UserInterface $currentUser): BinaryFileResponse
    {
        $user = $this->requireUser($currentUser);
        $image = $this->findImage($id);

        if (!$this->canView($image, $user)) {
            throw ApiProblemException::forbidden('You are not allowed to access this image.');
        }

        try {
            $path = $this->imageService->getFilePath($image);
        } catch (\RuntimeException) {
            throw ApiProblemException::notFound('Stored image file not found.');
        }
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $image->getId()?->toRfc4122().'.'.$image->getFileType());
        $response->headers->set('Content-Type', $this->mapMimeType($image->getFileType()));

        return $response;
    }

    #[Route('/{id}', name: 'api_image_update', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(string $id, Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $image = $this->findImage($id);

        if (!$this->canModify($image, $user)) {
            throw ApiProblemException::forbidden('You are not allowed to modify this image.');
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiProblemException::fromStatus(400, 'Bad Request', 'Malformed JSON payload.', 'PAYLOAD_INVALID');
        }

        $type = array_key_exists('type', $payload) ? ($payload['type'] === null ? null : (string) $payload['type']) : null;
        $newUserId = $payload['user_id'] ?? null;
        $newProjectId = $payload['project_id'] ?? null;
        $newTaskId = $payload['task_id'] ?? null;

        if ($type === null && $newUserId === null && $newProjectId === null && $newTaskId === null) {
            throw new \InvalidArgumentException('No updatable fields provided.');
        }

        $this->assertUploadPermissions($user, $newUserId, $newProjectId, $newTaskId, true);

        $updated = $this->imageService->update($image, $type, $newUserId, $newProjectId, $newTaskId);

        $this->auditLogger->record('image.update', $user, [
            'image_id' => $image->getId()?->toRfc4122(),
            'type' => $updated->getType(),
            'project_id' => $updated->getProject()?->getId()?->toRfc4122(),
            'task_id' => $updated->getTask()?->getId()?->toRfc4122(),
            'user_id' => $updated->getUser()?->getId()?->toRfc4122(),
        ]);

        return $this->responseFactory->single($this->imageViewFactory->make($updated));
    }

    #[Route('/{id}', name: 'api_image_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(string $id, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);
        $image = $this->findImage($id);

        if (!$this->canModify($image, $user)) {
            throw ApiProblemException::forbidden('You are not allowed to delete this image.');
        }

        $imageId = $image->getId()?->toRfc4122();
        $this->imageService->delete($image);

        $this->auditLogger->record('image.delete', $user, [
            'image_id' => $imageId,
        ]);

        return $this->responseFactory->single(['message' => 'Image deleted.']);
    }

    #[Route('/list', name: 'api_image_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request, #[CurrentUser] ?UserInterface $currentUser): JsonResponse
    {
        $user = $this->requireUser($currentUser);

        $filters = [
            'user_id' => $request->query->get('user_id'),
            'project_id' => $request->query->get('project_id'),
            'task_id' => $request->query->get('task_id'),
        ];

        $nonNull = array_filter($filters, static fn($value) => $value !== null && $value !== '');
        if (count($nonNull) !== 1) {
            throw ApiProblemException::validation(['filter' => ['Exactly one of user_id, project_id or task_id is required.']]);
        }

        if (isset($nonNull['user_id'])) {
            $targetUser = $this->resolveUser($nonNull['user_id']);
            if (!$this->canListForUser($user, $targetUser)) {
                throw ApiProblemException::forbidden('You are not allowed to list images for this user.');
            }

            $images = $this->imageRepository->findBy(['user' => $targetUser], ['uploaded_at' => 'DESC']);
        } elseif (isset($nonNull['project_id'])) {
            $targetProject = $this->resolveProject($nonNull['project_id']);
            if (!$this->canListForProject($user, $targetProject)) {
                throw ApiProblemException::forbidden('You are not allowed to list images for this project.');
            }

            $images = $this->imageRepository->findBy(['project' => $targetProject], ['uploaded_at' => 'DESC']);
        } else {
            $targetTask = $this->resolveTask($nonNull['task_id']);
            if (!$this->canListForTask($user, $targetTask)) {
                throw ApiProblemException::forbidden('You are not allowed to list images for this task.');
            }

            $images = $this->imageRepository->findBy(['task' => $targetTask], ['uploaded_at' => 'DESC']);
        }

        $items = array_map(fn(Image $image) => $this->imageViewFactory->make($image), $images);

        return $this->responseFactory->single(['items' => $items, 'total' => count($items)]);
    }

    private function canView(Image $image, User $user): bool
    {
        return $this->canListForContext($image, $user);
    }

    private function canModify(Image $image, User $user): bool
    {
        return $this->canListForContext($image, $user, true);
    }

    private function canListForContext(Image $image, User $user, bool $requireEdit = false): bool
    {
        if ($image->getUser() instanceof User) {
            return $this->canListForUser($user, $image->getUser(), $requireEdit);
        }

        if ($image->getProject() !== null) {
            return $this->canListForProject($user, $image->getProject(), $requireEdit);
        }

        if ($image->getTask() !== null) {
            return $this->canListForTask($user, $image->getTask(), $requireEdit);
        }

        return false;
    }

    private function canListForUser(User $actor, User $target, bool $requireEdit = false): bool
    {
        if ($target->getId()?->equals($actor->getId() ?? Uuid::v4())) {
            return true;
        }

        return $requireEdit
            ? $this->hasPermission($actor, 'perm_can_edit_user')
            : $this->hasPermission($actor, 'perm_can_read_user');
    }

    private function canListForProject(User $actor, Project $project, bool $requireEdit = false): bool
    {
        if ($project->getCreatedByUser()?->getId()?->equals($actor->getId() ?? Uuid::v4())) {
            return true;
        }

        return $requireEdit
            ? $this->hasPermission($actor, 'perm_can_edit_projects')
            : $this->hasPermission($actor, 'perm_can_read_projects');
    }

    private function canListForTask(User $actor, Task $task, bool $requireEdit = false): bool
    {
        $userId = $actor->getId();
        if ($userId !== null) {
            if ($task->getCreatedByUser()?->getId()?->equals($userId)) {
                return true;
            }
            if ($task->isAssignedToUser($actor)) {
                return true;
            }
        }

        return $requireEdit
            ? $this->hasPermission($actor, 'perm_can_edit_tasks')
            : $this->hasPermission($actor, 'perm_can_read_all_tasks');
    }

    private function assertUploadPermissions(User $actor, ?string $userId, ?string $projectId, ?string $taskId, bool $allowEmpty = false): void
    {
        $targets = array_filter([
            'user' => $userId,
            'project' => $projectId,
            'task' => $taskId,
        ], static fn($value) => $value !== null && $value !== '');

        if (!$allowEmpty && count($targets) !== 1) {
            throw ApiProblemException::validation(['relation' => ['Exactly one of user_id, project_id or task_id is required.']]);
        }

        if ($allowEmpty && count($targets) === 0) {
            return;
        }

        if (isset($targets['user'])) {
            $target = $this->resolveUser($targets['user']);
            if (!$this->canListForUser($actor, $target, true)) {
                throw ApiProblemException::forbidden('Insufficient permissions for user images.');
            }
        } elseif (isset($targets['project'])) {
            $target = $this->resolveProject($targets['project']);
            if (!$this->canListForProject($actor, $target, true)) {
                throw ApiProblemException::forbidden('Insufficient permissions for project images.');
            }
        } else {
            $target = $this->resolveTask($targets['task']);
            if (!$this->canListForTask($actor, $target, true)) {
                throw ApiProblemException::forbidden('Insufficient permissions for task images.');
            }
        }
    }

    private function resolveUser(string $userId): User
    {
        try {
            $uuid = Uuid::fromString($userId);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation(['user_id' => ['Invalid user id.']]);
        }

        $user = $this->userRepository->find($uuid);
        if (!$user instanceof User) {
            throw ApiProblemException::validation(['user_id' => ['User not found.']]);
        }

        return $user;
    }

    private function resolveProject(string $projectId): Project
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

    private function resolveTask(string $taskId): Task
    {
        try {
            $uuid = Uuid::fromString($taskId);
        } catch (\InvalidArgumentException) {
            throw ApiProblemException::validation(['task_id' => ['Invalid task id.']]);
        }

        $task = $this->taskRepository->find($uuid);
        if (!$task instanceof Task) {
            throw ApiProblemException::validation(['task_id' => ['Task not found.']]);
        }

        return $task;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        $resolved = $this->permissionRegistry->resolve($user);

        return $resolved[$permission] ?? false;
    }

    private function findImage(string $id): Image
    {
        $uuid = $this->toUuid($id, 'id');
        $image = $this->imageRepository->find($uuid);

        if (!$image instanceof Image) {
            throw ApiProblemException::notFound('Image not found.');
        }

        return $image;
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

    private function mapMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}

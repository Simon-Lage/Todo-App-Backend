<?php

declare(strict_types=1);

namespace App\Image\Service;

use App\Entity\Image;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiProblemException;
use App\Repository\ImageRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class ImageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ImageRepository $imageRepository,
        private readonly UserRepository $userRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ImageStorage $storage
    ) {
    }

    public function create(User $uploader, UploadedFile $file, string $type, ?string $userId, ?string $projectId, ?string $taskId): Image
    {
        $targets = $this->resolveRelation($userId, $projectId, $taskId);

        $image = new Image();
        $image->setType(trim($type));
        $image->setUploadedByUser($uploader);
        $image->setUploadedAt(new DateTimeImmutable());
        $size = $file->getSize();
        $image->setFileSize($size !== false ? (int) $size : 0);
        $image->setFileType($this->normalizeExtension($file));

        $this->applyRelation($image, $targets);

        $this->entityManager->persist($image);

        $sourcePath = $file->getRealPath() ?: $file->getPathname();
        $this->storage->store($image, $sourcePath);

        $this->entityManager->flush();

        return $image;
    }

    public function update(Image $image, ?string $type, ?string $userId, ?string $projectId, ?string $taskId): Image
    {
        if ($type !== null) {
            $image->setType(trim($type));
        }

        if ($userId !== null || $projectId !== null || $taskId !== null) {
            $targets = $this->resolveRelation($userId, $projectId, $taskId, true);
            if ($targets !== []) {
                $this->applyRelation($image, $targets);
            }
        }

        $this->entityManager->flush();

        return $image;
    }

    public function delete(Image $image): void
    {
        $this->storage->delete($image);
        $this->entityManager->remove($image);
        $this->entityManager->flush();
    }

    public function getFilePath(Image $image): string
    {
        return $this->storage->ensureExists($image);
    }

    /**
     * @return array{user?:User, project?:Project, task?:Task}
     */
    private function resolveRelation(?string $userId, ?string $projectId, ?string $taskId, bool $acceptEmpty = false): array
    {
        $nonNull = array_filter([
            'user' => $userId,
            'project' => $projectId,
            'task' => $taskId,
        ], static fn($value) => $value !== null && $value !== '');

        if (!$acceptEmpty && count($nonNull) !== 1) {
            throw ApiProblemException::validation(['relation' => ['Exactly one of user_id, project_id or task_id must be provided.']]);
        }

        if ($acceptEmpty && count($nonNull) > 1) {
            throw ApiProblemException::validation(['relation' => ['Only one of user_id, project_id or task_id can be provided.']]);
        }

        $targets = [];

        if (array_key_exists('user', $nonNull)) {
            $targets['user'] = $this->findUser($nonNull['user']);
        } elseif (array_key_exists('project', $nonNull)) {
            $targets['project'] = $this->findProject($nonNull['project']);
        } elseif (array_key_exists('task', $nonNull)) {
            $targets['task'] = $this->findTask($nonNull['task']);
        }

        return $targets;
    }

    private function applyRelation(Image $image, array $targets): void
    {
        $image->setUser(null);
        $image->setProject(null);
        $image->setTask(null);

        if (isset($targets['user'])) {
            $image->setUser($targets['user']);
        } elseif (isset($targets['project'])) {
            $image->setProject($targets['project']);
        } elseif (isset($targets['task'])) {
            $image->setTask($targets['task']);
        }
    }

    private function normalizeExtension(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        if ($extension === null || $extension === '') {
            return 'bin';
        }

        return substr(strtolower($extension), 0, 10);
    }

    private function findUser(string $userId): User
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

    private function findTask(string $taskId): Task
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
}

<?php

declare(strict_types=1);

namespace App\Image\Service;

use App\Entity\Image;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

final class ImageStorage
{
    public function __construct(private readonly string $uploadDir, private readonly Filesystem $filesystem)
    {
        $this->filesystem->mkdir($this->uploadDir);
    }

    public function store(Image $image, string $sourcePath): void
    {
        $targetPath = $this->path($image);
        $this->filesystem->copy($sourcePath, $targetPath, true);
    }

    public function path(Image $image): string
    {
        $id = $image->getId();
        if ($id === null) {
            throw new \RuntimeException('Image does not have an identifier yet.');
        }

        return rtrim($this->uploadDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$id->toRfc4122();
    }

    public function delete(Image $image): void
    {
        $path = $this->path($image);
        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }

    public function ensureExists(Image $image): string
    {
        $path = $this->path($image);
        if (!$this->filesystem->exists($path)) {
            throw new \RuntimeException('Stored file not found.');
        }

        return $path;
    }
}

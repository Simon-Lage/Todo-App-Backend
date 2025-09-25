<?php

declare(strict_types=1);

namespace App\Image\View;

use App\Entity\Image;

final class ImageViewFactory
{
    public function make(Image $image): array
    {
        return [
            'id' => $image->getId()?->toRfc4122(),
            'type' => $image->getType(),
            'file_type' => $image->getFileType(),
            'file_size' => $image->getFileSize(),
            'uploaded_at' => $image->getUploadedAt()?->format(DATE_ATOM),
            'uploaded_by_user_id' => $image->getUploadedByUser()?->getId()?->toRfc4122(),
            'project_id' => $image->getProject()?->getId()?->toRfc4122(),
            'task_id' => $image->getTask()?->getId()?->toRfc4122(),
            'user_id' => $image->getUser()?->getId()?->toRfc4122(),
        ];
    }
}

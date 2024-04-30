<?php

declare(strict_types=1);

namespace App\Application\Domain\Entity\Snap\FileStorage;

final readonly class FileMetadata
{
    public function __construct(
        private string $originalName,
        private string $path
    ) {
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}

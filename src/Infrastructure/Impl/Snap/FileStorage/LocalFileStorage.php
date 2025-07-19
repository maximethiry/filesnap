<?php

declare(strict_types=1);

namespace App\Infrastructure\Impl\Snap\FileStorage;

use App\Application\Domain\Snap\FileStorage\File;
use App\Application\Domain\Snap\FileStorage\FileMetadata;
use App\Application\Domain\Snap\FileStorage\FileStorageInterface;
use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\FormatConverterService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Uuid;

final readonly class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        #[Autowire(param: 'app.upload_directory')] private string $uploadDirectory,
        #[Autowire(param: 'app.upload.bytes_max_filesize')] private int $uploadBytesMaxFilesize,
        private FormatConverterService $formatConverterService,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function getFileMaximumAuthorizedBytesSize(): int
    {
        return $this->uploadBytesMaxFilesize;
    }

    public function store(Uuid $snapId, Uuid $snapUserId, FileMetadata $fileMetadata): void
    {
        $userPersonalUploadDirectory = sprintf('%s/%s', $this->uploadDirectory, $snapUserId->toBase58());

        if ($this->filesystem->exists($userPersonalUploadDirectory) === false) {
            $this->filesystem->mkdir($userPersonalUploadDirectory);
        }

        $this->filesystem->rename(
            $fileMetadata->getPath(),
            sprintf('%s/%s', $userPersonalUploadDirectory, $snapId->toBase58())
        );
    }

    public function delete(Snap $snap): void
    {
        $filePath = sprintf('%s/%s/%s', $this->uploadDirectory, $snap->getUserId()->toBase58(), $snap->getId()->toBase58());

        $this->filesystem->remove($filePath);
        $this->formatConverterService->deleteConvertedFiles($snap);
    }

    public function get(Uuid $snapId, Uuid $snapUserId): ?File
    {
        $filePath = sprintf('%s/%s/%s', $this->uploadDirectory, $snapUserId->toBase58(), $snapId->toBase58());

        if ($this->filesystem->exists($filePath) === false) {
            return null;
        }

        return new File($filePath);
    }
}

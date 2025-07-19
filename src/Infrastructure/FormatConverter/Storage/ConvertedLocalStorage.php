<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter\Storage;

use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Uid\Uuid;

final readonly class ConvertedLocalStorage implements StorageInterface
{
    public function __construct(
        private string $extension,
        #[Autowire(param: 'app.converted_upload_directory')] private string $convertedUploadDirectory,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function save(Snap $snap, File $file): void
    {
        $userDirectory = $this->getUserDirectory($snap->getUserId());

        if ($this->filesystem->exists($userDirectory) === false) {
            $this->filesystem->mkdir($userDirectory);
        }

        $this->filesystem->rename($file->getPathname(), $this->getFileAbsolutePath($snap));
    }

    public function get(Snap $snap): ?File
    {
        $fileAbsolutePath = $this->getFileAbsolutePath($snap);

        return $this->filesystem->exists($fileAbsolutePath) === true
            ? new File($fileAbsolutePath)
            : null;
    }

    public function delete(Snap $snap): void
    {
        $this->filesystem->remove($this->getFileAbsolutePath($snap));
    }

    private function getUserDirectory(Uuid $userId): string
    {
        return sprintf('%s/%s', $this->convertedUploadDirectory, $userId->toBase58());
    }

    private function getFileAbsolutePath(Snap $snap): string
    {
        return sprintf(
            '%s/%s.%s',
            $this->getUserDirectory($snap->getUserId()),
            $snap->getId()->toBase58(),
            $this->extension
        );
    }
}

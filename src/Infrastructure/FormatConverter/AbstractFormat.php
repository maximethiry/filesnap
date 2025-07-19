<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter;

use App\Application\Domain\Snap\Snap;
use Symfony\Component\HttpFoundation\File\File;

abstract readonly class AbstractFormat
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function convert(Snap $snap): void
    {
        $this->storage->save($snap, $this->convertFile($snap));
    }

    public function get(Snap $snap): ?File
    {
        return $this->storage->get($snap);
    }

    public function delete(Snap $snap): void
    {
        $this->storage->delete($snap);
    }

    abstract public static function getExtension(): string;

    abstract protected function convertFile(Snap $snap): File;
}

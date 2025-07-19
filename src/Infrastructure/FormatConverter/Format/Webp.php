<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter\Format;

use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\AbstractFormat;
use App\Infrastructure\FormatConverter\GdService;
use App\Infrastructure\FormatConverter\StorageInterface;
use Symfony\Component\HttpFoundation\File\File;

final readonly class Webp extends AbstractFormat
{
    public function __construct(
        StorageInterface $storage,
        private int $quality = 90,
    ) {
        if ($this->quality < 0 || $this->quality > 100) {
            throw new \InvalidArgumentException('Quality must be between 0 and 100');
        }

        parent::__construct($storage);
    }

    public static function getExtension(): string
    {
        return 'webp';
    }

    protected function convertFile(Snap $snap): File
    {
        $gdImage = GdService::getSnapGdImage($snap);
        $tempFilePath = sprintf('%s/%s.%s', sys_get_temp_dir(), $snap->getId()->toBase58(), self::getExtension());

        if (imagewebp($gdImage, $tempFilePath, $this->quality) === false) {
            throw new \RuntimeException('Error at avif image creation');
        }

        return new File($tempFilePath);
    }
}

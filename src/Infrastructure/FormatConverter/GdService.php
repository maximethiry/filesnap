<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter;

use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;

final readonly class GdService
{
    public static function getSnapGdImage(Snap $snap): \GdImage
    {
        $snapAbsolutePath = $snap->getFile()->getAbsolutePath();
        $snapMimeType = $snap->getMimeType();

        $gdImage = match ($snapMimeType) {
            MimeType::ImageJpeg => imagecreatefromjpeg($snapAbsolutePath),
            MimeType::ImagePng => imagecreatefrompng($snapAbsolutePath),
            MimeType::ImageGif => imagecreatefromgif($snapAbsolutePath),
            MimeType::ImageWebp => imagecreatefromwebp($snapAbsolutePath),
            default => throw new \RuntimeException(sprintf('You can\'t generate a webp from a %s file.', $snapMimeType->value)),
        };

        if ($gdImage === false) {
            throw new \RuntimeException('GdImage could not be created.');
        }

        return $gdImage;
    }
}

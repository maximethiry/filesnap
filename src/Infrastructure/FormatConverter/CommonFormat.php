<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter;

use App\Application\Domain\Snap\MimeType;

enum CommonFormat: string
{
    case Avif = 'avif';
    case Webm = 'webm';
    case Webp = 'webp';

    /** @var list<self> */
    private const array IMAGE_FORMATS = [
        self::Avif,
        self::Webp,
    ];

    /** @var list<self> */
    private const array VIDEO_FORMATS = [
        self::Webm,
    ];

    /** @return list<self> */
    public static function getFormats(MimeType $mimeType): array
    {
        if ($mimeType->isImage() === true) {
            return self::IMAGE_FORMATS;
        }

        if ($mimeType->isVideo() === true) {
            return self::VIDEO_FORMATS;
        }

        throw new \RuntimeException('MimeType is not an image nor a video ?');
    }
}

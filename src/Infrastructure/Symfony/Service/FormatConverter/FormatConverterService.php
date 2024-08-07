<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Service\FormatConverter;

use App\Application\Domain\Entity\Snap\Snap;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\FormatConverterInterface;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\WebmConverter;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\WebpConverter;

final readonly class FormatConverterService
{
    /**
     * @return list<FormatConverterInterface>
     */
    private static function getConverters(): array
    {
        return [
            new WebpConverter(),
            new WebmConverter(),
        ];
    }

    public static function deleteAllConvertedFiles(Snap $snap): void
    {
        foreach (self::getConverters() as $converter) {
            $converter->deleteConvertedFile($snap);
        }
    }
}

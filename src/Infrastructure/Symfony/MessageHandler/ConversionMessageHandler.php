<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\MessageHandler;

use App\Application\UseCase\Snap\FindOneById\FindOneSnapByIdRequest;
use App\Application\UseCase\Snap\FindOneById\FindOneSnapByIdUseCase;
use App\Infrastructure\Symfony\Message\ConversionMessage;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\Avif\AvifConverter;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\ConvertFormat;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\Thumbnail\ThumbnailConverter;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\Webm\WebmConverter;
use App\Infrastructure\Symfony\Service\FormatConverter\Converter\Webp\WebpConverter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ConversionMessageHandler
{
    public function __construct(
        private FindOneSnapByIdUseCase $findOneSnapByIdUseCase,
        private ThumbnailConverter $thumbnailConverter,
        private WebmConverter $webmConverter,
        private WebpConverter $webpConverter,
        private AvifConverter $avifConverter,
    ) {
    }

    public function __invoke(ConversionMessage $message): void
    {
        $response = ($this->findOneSnapByIdUseCase)(new FindOneSnapByIdRequest($message->getSnapId()));
        $snap = $response->getSnap();

        if ($snap === null) {
            throw new \RuntimeException(sprintf('Snap id %s not found.', $message->getSnapId()->toRfc4122()));
        }

        match ($message->getFormat()) {
            ConvertFormat::Thumbnail => $this->thumbnailConverter->convert($snap),
            ConvertFormat::Webm => $this->webmConverter->convert($snap),
            ConvertFormat::Webp => $this->webpConverter->convert($snap),
            ConvertFormat::Avif => $this->avifConverter->convert($snap),
        };
    }
}

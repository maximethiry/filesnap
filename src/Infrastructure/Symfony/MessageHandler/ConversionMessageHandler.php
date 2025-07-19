<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\MessageHandler;

use App\Application\UseCase\Snap\FindOneById\FindOneSnapByIdRequest;
use App\Application\UseCase\Snap\FindOneById\FindOneSnapByIdUseCase;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\FormatConverter\Format\Avif;
use App\Infrastructure\FormatConverter\Format\Webm;
use App\Infrastructure\FormatConverter\Format\Webp;
use App\Infrastructure\Symfony\Message\ConversionMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ConversionMessageHandler
{
    public function __construct(
        private FindOneSnapByIdUseCase $findOneSnapByIdUseCase,
        private Avif $avif,
        private Webm $webm,
        private Webp $webp,
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
            CommonFormat::Avif => $this->avif->convert($snap),
            CommonFormat::Webm => $this->webm->convert($snap),
            CommonFormat::Webp => $this->webp->convert($snap),
        };
    }
}

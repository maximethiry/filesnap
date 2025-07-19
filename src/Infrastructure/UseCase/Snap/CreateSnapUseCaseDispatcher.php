<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCase\Snap;

use App\Application\Domain\Snap\Exception\FileNotFoundException;
use App\Application\Domain\Snap\Exception\FileSizeTooBigException;
use App\Application\Domain\Snap\Exception\UnsupportedFileTypeException;
use App\Application\UseCase\Snap\Create\CreateSnapRequest;
use App\Application\UseCase\Snap\Create\CreateSnapResponse;
use App\Application\UseCase\Snap\Create\CreateSnapUseCase;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\Symfony\Message\ConversionMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateSnapUseCaseDispatcher
{
    public function __construct(
        private MessageBusInterface $bus,
        private CreateSnapUseCase $useCase,
    ) {
    }

    /**
     * @throws FileNotFoundException
     * @throws UnsupportedFileTypeException
     * @throws FileSizeTooBigException
     * @throws ExceptionInterface
     */
    public function __invoke(CreateSnapRequest $request): CreateSnapResponse
    {
        $response = ($this->useCase)($request);
        $snap = $response->getSnap();

        $formats = CommonFormat::getFormats($snap->getMimeType());

        foreach ($formats as $format) {
            $this->bus->dispatch(new ConversionMessage($snap->getId(), $format));
        }

        return $response;
    }
}

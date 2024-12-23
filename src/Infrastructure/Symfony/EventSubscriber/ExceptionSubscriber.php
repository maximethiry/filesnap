<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventSubscriber;

use App\Application\Domain\Exception\DomainException;
use App\Application\Domain\Exception\InvalidRequestParameterException;
use App\Application\Domain\Snap\Exception\FileSizeTooBigException;
use App\Application\Domain\Snap\Exception\SnapNotFoundException;
use App\Application\Domain\Snap\Exception\UnauthorizedDeletionException;
use App\Application\Domain\Snap\Exception\UnknownSnapsException;
use App\Application\Domain\Snap\Exception\UnsupportedFileTypeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['domainExceptionToHttpException', -127],
            ],
        ];
    }

    public function domainExceptionToHttpException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof DomainException === false) {
            return;
        }

        $statusCode = match ($throwable::class) {
            SnapNotFoundException::class => Response::HTTP_NOT_FOUND,

            UnsupportedFileTypeException::class,
            FileSizeTooBigException::class,
            InvalidRequestParameterException::class,
            UnknownSnapsException::class => Response::HTTP_BAD_REQUEST,

            UnauthorizedDeletionException::class => Response::HTTP_FORBIDDEN,

            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $event->setThrowable(new HttpException($statusCode, $throwable->getMessage(), $throwable));
    }
}

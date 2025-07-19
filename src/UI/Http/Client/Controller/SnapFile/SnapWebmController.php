<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\SnapFile;

use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\FormatConverter\Format\Webm;
use App\UI\Http\Client\Controller\AbstractSnapFileController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Very poor performance at the moment.
 */
#[Route(
    path: '/snap/{id}.webm',
    name: 'client_snap_file_webm',
    methods: Request::METHOD_GET,
    priority: 1,
)]
final class SnapWebmController extends AbstractSnapFileController
{
    public function __construct(
        private readonly Webm $webm,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function response(Snap $snap): Response
    {
        $webmFile = $this->webm->get($snap);

        if ($webmFile === null) {
            return $this->waitingForConversionResponse($snap, CommonFormat::Webm);
        }

        return $this->file(
            $webmFile,
            sprintf('%s.%s', $snap->getOriginalFilename(), Webm::getExtension()),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    protected function updateSnapLastSeenDate(): bool
    {
        return true;
    }

    protected function supportsMimeType(MimeType $mimeType): bool
    {
        return $mimeType->isVideo();
    }
}

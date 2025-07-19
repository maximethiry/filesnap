<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\SnapFile;

use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\FormatConverter\Format\Webp;
use App\UI\Http\Client\Controller\AbstractSnapFileController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/snap/{id}.webp',
    name: 'client_snap_file_webp',
    methods: Request::METHOD_GET,
    priority: 1,
)]
final class SnapWebpController extends AbstractSnapFileController
{
    public function __construct(
        private readonly Webp $webp,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function response(Snap $snap): Response
    {
        $webpFile = $this->webp->get($snap);

        if ($webpFile === null) {
            return $this->waitingForConversionResponse($snap, CommonFormat::Webp);
        }

        return $this->file(
            $webpFile,
            sprintf('%s.%s', $snap->getOriginalFilename(), Webp::getExtension()),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    protected function updateSnapLastSeenDate(): bool
    {
        return true;
    }

    protected function supportsMimeType(MimeType $mimeType): bool
    {
        return $mimeType->isImage();
    }
}

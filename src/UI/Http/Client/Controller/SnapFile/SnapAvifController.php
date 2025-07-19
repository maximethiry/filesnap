<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\SnapFile;

use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\FormatConverter\Format\Avif;
use App\UI\Http\Client\Controller\AbstractSnapFileController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/snap/{id}.avif',
    name: 'client_snap_file_avif',
    methods: Request::METHOD_GET,
    priority: 1,
)]
final class SnapAvifController extends AbstractSnapFileController
{
    public function __construct(
        private readonly Avif $avif,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function response(Snap $snap): Response
    {
        $avifFile = $this->avif->get($snap);

        if ($avifFile === null) {
            return $this->waitingForConversionResponse($snap, CommonFormat::Avif);
        }

        return $this->file(
            $avifFile,
            sprintf('%s.%s', $snap->getOriginalFilename(), Avif::getExtension()),
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

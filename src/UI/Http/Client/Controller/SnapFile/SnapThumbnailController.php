<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\SnapFile;

use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\Format\Thumbnail;
use App\UI\Http\Client\Controller\AbstractSnapFileController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This route is triggered only if the .thumbnail file for a Snap doesn't exist in the public/snap/ directory.
 * It generates the thumbnail and redirect to itself to let the server return the previously generated thumbnail file.
 */
#[Route(
    path: '/snap/{id}.thumbnail',
    name: 'client_snap_file_thumbnail',
    methods: Request::METHOD_GET,
    priority: 1,
)]
final class SnapThumbnailController extends AbstractSnapFileController
{
    public function __construct(
        private readonly Thumbnail $thumbnail,
    ) {
    }

    /**
     * @throws \Exception
     */
    protected function response(Snap $snap): RedirectResponse
    {
        $this->thumbnail->convert($snap);

        return $this->redirectToRoute('client_snap_file_thumbnail', ['id' => $snap->getId()->toBase58()]);
    }

    protected function updateSnapLastSeenDate(): bool
    {
        return false;
    }

    protected function supportsMimeType(MimeType $mimeType): bool
    {
        return true;
    }
}

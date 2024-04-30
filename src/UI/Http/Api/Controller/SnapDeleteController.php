<?php

declare(strict_types=1);

namespace App\UI\Http\Api\Controller;

use App\Application\Domain\Entity\Snap\Exception\SnapNotFoundException;
use App\Application\UseCase\Snap\DeleteById\DeleteSnapByIdRequest;
use App\Application\UseCase\Snap\DeleteById\DeleteSnapByIdUseCase;
use App\Infrastructure\Symfony\Attribute\MapUuidFromBase58;
use App\UI\Http\FilesnapAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route(
    path: '/api/snap/{id}',
    name: 'api_snap_delete',
    methods: Request::METHOD_DELETE,
    format: 'json'
)]
final class SnapDeleteController extends FilesnapAbstractController
{
    /**
     * @throws SnapNotFoundException
     */
    public function __invoke(
        DeleteSnapByIdUseCase $deleteSnapByIdUseCase,
        Request $request,
        #[MapUuidFromBase58] Uuid $id
    ): Response {
        $deleteSnapByIdUseCase(new DeleteSnapByIdRequest($id));

        return $this->emptyResponse();
    }
}

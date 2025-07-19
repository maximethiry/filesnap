<?php

declare(strict_types=1);

namespace App\UI\Http\Client\Controller\User;

use App\Infrastructure\Software\Software;
use App\Infrastructure\Software\SoftwareConfigurationService;
use App\UI\Http\FilesnapAbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

#[Route(
    path: '/u/configuration-file/{software}',
    name: 'client_user_configuration_file',
    requirements: ['software' => new EnumRequirement(Software::class)],
    methods: Request::METHOD_GET,
)]
final class ConfigurationFileController extends FilesnapAbstractController
{
    /**
     * @throws \JsonException
     */
    public function __invoke(
        SoftwareConfigurationService $softwareConfigurationService,
        Software $software,
    ): BinaryFileResponse {
        $file = $softwareConfigurationService->getConfigurationFile($software, $this->getAuthenticatedUser());
        $filename = sprintf('filesnap_%s_configuration.%s', $software->value, $file->getExtension());

        return $this->file($file, $filename);
    }
}

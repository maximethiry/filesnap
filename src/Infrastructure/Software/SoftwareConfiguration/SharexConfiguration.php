<?php

declare(strict_types=1);

namespace App\Infrastructure\Software\SoftwareConfiguration;

use App\Infrastructure\Symfony\Security\ApiKeyAuthenticator;
use App\Infrastructure\Symfony\Security\Entity\SecurityUser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SharexConfiguration implements SoftwareConfigurationInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getConfigurationFile(SecurityUser $user): File
    {
        $requestUrl = $this->router->generate(name: 'api_snap_post', referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
        $authorizationHeader = ApiKeyAuthenticator::createAuthorizationHeader($user->getAuthorizationKey());

        $configuration = [
            'Version' => '14.1.0',
            'Name' => 'Filesnap',
            'DestinationType' => 'ImageUploader',
            'RequestMethod' => 'POST',
            'RequestURL' => $requestUrl,
            'Headers' => ['Authorization' => $authorizationHeader],
            'Body' => 'MultipartFormData',
            'FileFormName' => 'file',
            'URL' => '{json:formats.preferred}',
            'ThumbnailURL' => '{json:formats.thumbnail}',
        ];

        $json = json_encode($configuration, JSON_THROW_ON_ERROR);
        $tmpFilename = $this->filesystem->tempnam(sys_get_temp_dir(), 'sharex_configuration_', '.sxcu');
        $this->filesystem->appendToFile($tmpFilename, $json);

        return new File($tmpFilename);
    }
}

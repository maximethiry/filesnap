<?php

declare(strict_types=1);

namespace App\Infrastructure\Software;

use App\Infrastructure\Software\SoftwareConfiguration\SharexConfiguration;
use App\Infrastructure\Symfony\Security\Entity\SecurityUser;
use Symfony\Component\HttpFoundation\File\File;

final readonly class SoftwareConfigurationService
{
    public function __construct(
        private SharexConfiguration $sharexConfiguration,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function getConfigurationFile(Software $software, SecurityUser $user): File
    {
        return match ($software) {
            Software::Sharex => $this->sharexConfiguration->getConfigurationFile($user),
        };
    }
}

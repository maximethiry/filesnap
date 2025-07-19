<?php

declare(strict_types=1);

namespace App\Infrastructure\Software\SoftwareConfiguration;

use App\Infrastructure\Symfony\Security\Entity\SecurityUser;
use Symfony\Component\HttpFoundation\File\File;

interface SoftwareConfigurationInterface
{
    public function getConfigurationFile(SecurityUser $user): File;
}

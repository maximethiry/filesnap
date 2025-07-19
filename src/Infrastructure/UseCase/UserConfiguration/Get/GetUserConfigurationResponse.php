<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCase\UserConfiguration\Get;

use App\Infrastructure\UserConfiguration\UserConfiguration;

final readonly class GetUserConfigurationResponse
{
    public function __construct(
        public UserConfiguration $userConfiguration
    ) {
    }
}

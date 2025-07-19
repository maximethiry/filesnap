<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCase\UserConfiguration\Get;

use Symfony\Component\Uid\Uuid;

final readonly class GetUserConfigurationRequest
{
    public function __construct(
        public Uuid $userId
    ) {
    }
}

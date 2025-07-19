<?php

declare(strict_types=1);

namespace App\Infrastructure\UserConfiguration;

use Symfony\Component\Uid\Uuid;

interface UserConfigurationRepositoryInterface
{
    public function find(Uuid $userId): ?UserConfiguration;
    public function create(Uuid $userId, UserConfiguration $userConfiguration): void;
    public function update(Uuid $userId, UserConfiguration $userConfiguration): void;
}

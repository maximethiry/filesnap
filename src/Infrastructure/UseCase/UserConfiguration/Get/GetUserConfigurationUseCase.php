<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCase\UserConfiguration\Get;

use App\Infrastructure\UserConfiguration\UserConfigurationRepositoryInterface;

final readonly class GetUserConfigurationUseCase
{
    public function __construct(
        private UserConfigurationRepositoryInterface $userConfigurationRepository
    ) {
    }

    public function __invoke(GetUserConfigurationRequest $request): GetUserConfigurationResponse
    {
        $userConfiguration = $this->userConfigurationRepository->find($request->userId);

        if ($userConfiguration === null) {
            throw new \RuntimeException('No configuration found');
        }

        return new GetUserConfigurationResponse($userConfiguration);
    }
}

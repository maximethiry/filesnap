<?php

declare(strict_types=1);

namespace App\Infrastructure\UseCase\User;

use App\Application\UseCase\User\Create\CreateUserRequest;
use App\Application\UseCase\User\Create\CreateUserResponse;
use App\Application\UseCase\User\Create\CreateUserUseCase;
use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\UserConfiguration\UserConfiguration;
use App\Infrastructure\UserConfiguration\UserConfigurationRepositoryInterface;

final readonly class CreateUserWithConfigurationUseCase
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase,
        private UserConfigurationRepositoryInterface $userConfigurationRepository
    ) {
    }

    public function __invoke(CreateUserRequest $request): CreateUserResponse
    {
        $useCaseResponse = ($this->createUserUseCase)($request);

        $this->userConfigurationRepository->create(
            $useCaseResponse->getUser()->getId(),
            new UserConfiguration(CommonFormat::cases(), null)
        );

        return $useCaseResponse;
    }
}

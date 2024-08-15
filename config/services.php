<?php

declare(strict_types=1);

use App\Application\Domain\Snap\FileStorage\FileStorageInterface;
use App\Application\Domain\Snap\SnapRepositoryInterface;
use App\Application\Domain\User\Service\PasswordHasherInterface;
use App\Application\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Domain\Impl\Snap\FileStorage\LocalFileStorage;
use App\Infrastructure\Domain\Impl\Snap\Repository\MariadbSnapRepository;
use App\Infrastructure\Domain\Impl\User\Repository\MariadbUserRepository;
use App\Infrastructure\Domain\Impl\User\Service\SymfonyPasswordHasher;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('app.environment', '%kernel.environment%');
    $parameters->set('app.project_directory', '%kernel.project_dir%');
    $parameters->set('app.public_directory', '%kernel.project_dir%/public');
    $parameters->set('app.upload.relative_directory', '/user_uploads');
    $parameters->set('app.upload.bytes_max_filesize', 50000000);
    $parameters->set('app.converted_upload_directory', '%kernel.project_dir%/user_converted_uploads');

    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('App\\', __DIR__ . '/../src/')
        ->exclude([
            __DIR__ . '/../src/Kernel.php',
        ]);

    $services->alias(UserRepositoryInterface::class, MariadbUserRepository::class);
    $services->alias(SnapRepositoryInterface::class, MariadbSnapRepository::class);
    $services->alias(FileStorageInterface::class, LocalFileStorage::class);
    $services->alias(PasswordHasherInterface::class, SymfonyPasswordHasher::class);
};

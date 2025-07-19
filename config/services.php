<?php

declare(strict_types=1);

use App\Infrastructure\FormatConverter\AbstractFormat;
use App\Infrastructure\FormatConverter\Format\Avif;
use App\Infrastructure\FormatConverter\Format\Thumbnail;
use App\Infrastructure\FormatConverter\Format\Webm;
use App\Infrastructure\FormatConverter\Format\Webp;
use App\Infrastructure\FormatConverter\Storage\ConvertedLocalStorage;
use App\Infrastructure\FormatConverter\Storage\ThumbnailLocalStorage;
use App\Infrastructure\FormatConverter\StorageInterface;
use App\Infrastructure\UseCase\UserConfiguration\Get\GetUserConfigurationUseCase;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('app.environment', '%kernel.environment%');
    $parameters->set('app.project_directory', '%kernel.project_dir%');
    $parameters->set('app.public_directory', '%kernel.project_dir%/public');
    $parameters->set('app.upload_directory', '%kernel.project_dir%/user_uploads');
    $parameters->set('app.converted_upload_directory', '%kernel.project_dir%/user_converted_uploads');
    $parameters->set('app.thumbnail_directory', '%kernel.project_dir%/public/snap');
    $parameters->set('app.upload.bytes_max_filesize', env('LOCAL_STORAGE_BYTES_MAX_FILESIZE')->int());

    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('App\\', sprintf('%s/../src/', __DIR__))
        ->exclude([
            sprintf('%s/../src/Kernel.php', __DIR__),
        ]);

    $services->instanceof(AbstractFormat::class)->tag('abstract-format');

    // Avif format configuration
    $services
        ->set('converted-local-storage.extension.avif', ConvertedLocalStorage::class)
        ->arg('$extension', Avif::getExtension());
    $services
        ->set(Avif::class)
        ->bind(StorageInterface::class, service('converted-local-storage.extension.avif'));

    // Thumbnail format configuration
    $services
        ->set(Thumbnail::class)
        ->bind(StorageInterface::class, service(ThumbnailLocalStorage::class));

    // Webm format configuration
    $services
        ->set('converted-local-storage.extension.webm', ConvertedLocalStorage::class)
        ->arg('$extension', Webm::getExtension());
    $services
        ->set(Webm::class)
        ->bind(StorageInterface::class, service('converted-local-storage.extension.webm'));

    // Webp format configuration
    $services
        ->set('converted-local-storage.extension.webp', ConvertedLocalStorage::class)
        ->arg('$extension', Webp::getExtension());
    $services
        ->set(Webp::class)
        ->bind(StorageInterface::class, service('converted-local-storage.extension.webp'));
};

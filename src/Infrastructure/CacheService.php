<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

final readonly class CacheService
{
    public static function getAdapter(): TagAwareAdapterInterface
    {
        if (ApcuAdapter::isSupported()) {
            return new TagAwareAdapter(new ApcuAdapter());
        }

        return new FilesystemTagAwareAdapter();
    }
}

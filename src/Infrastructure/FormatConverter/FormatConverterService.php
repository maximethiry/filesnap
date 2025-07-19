<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter;

use App\Application\Domain\Snap\Snap;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class FormatConverterService
{
    public function __construct(
        /** @var list<AbstractFormat> $abstractFormats */
        #[AutowireIterator(tag: 'abstract-format')] private iterable $abstractFormats,
    ) {
    }

    public function deleteConvertedFiles(Snap $snap): void
    {
        foreach ($this->abstractFormats as $formatStorageImplementation) {
            $formatStorageImplementation->delete($snap);
        }
    }
}

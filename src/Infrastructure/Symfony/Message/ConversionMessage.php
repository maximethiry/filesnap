<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Message;

use App\Infrastructure\FormatConverter\CommonFormat;
use Symfony\Component\Uid\Uuid;

final readonly class ConversionMessage
{
    public function __construct(
        private Uuid $snapId,
        private CommonFormat $format,
    ) {
    }

    public function getSnapId(): Uuid
    {
        return $this->snapId;
    }

    public function getFormat(): CommonFormat
    {
        return $this->format;
    }
}

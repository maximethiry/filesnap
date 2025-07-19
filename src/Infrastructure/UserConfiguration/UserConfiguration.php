<?php

declare(strict_types=1);

namespace App\Infrastructure\UserConfiguration;

use App\Infrastructure\FormatConverter\CommonFormat;

final readonly class UserConfiguration
{
    /**
     * @var list<CommonFormat>
     */
    public array $enabledConversionFormats;
    public ?\DateInterval $snapExpirationInterval;

    public function __construct(
        array $enabledConversionFormats,
        ?\DateInterval $snapExpirationInterval,
    ) {
        $this->enabledConversionFormats = $enabledConversionFormats;
        $this->snapExpirationInterval = $snapExpirationInterval;
    }

    public function hasConversionFormatEnabled(CommonFormat $format): bool
    {
        return in_array($format, $this->enabledConversionFormats, true);
    }
}

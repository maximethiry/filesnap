<?php

declare(strict_types=1);

namespace App\Infrastructure\Impl\UserConfiguration\Repository;

use App\Infrastructure\FormatConverter\CommonFormat;
use App\Infrastructure\UserConfiguration\UserConfiguration;
use App\Infrastructure\UserConfiguration\UserConfigurationRepositoryInterface;
use DateInterval;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type DbResult array{
 *      user_id:string,
 *      enabled_conversion_formats:string,
 *      snap_expiration_days_interval:int|null,
 *  }
 */
final readonly class MariadbUserConfigurationRepository implements UserConfigurationRepositoryInterface
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * @throws Exception
     */
    public function find(Uuid $userId): ?UserConfiguration
    {
        $query = '
            SELECT user_id, enabled_conversion_formats, snap_expiration_days_interval
            FROM user_configuration
            WHERE user_id = :userId
        ';

        /** @var DbResult|false $dbResult */
        $dbResult = $this->connection->fetchAssociative($query, ['userId' => $userId->toRfc4122()]);

        if ($dbResult === false) {
            return null;
        }

        return self::toUserConfiguration($dbResult);
    }

    /**
     * @throws Exception
     */
    public function create(Uuid $userId, UserConfiguration $userConfiguration): void
    {
        $query = '
            INSERT INTO user_configuration (user_id, enabled_conversion_formats, snap_expiration_days_interval)
            VALUES (:user_id, :enabled_conversion_formats, :snap_expiration_days_interval)
        ';

        $snapExpirationDaysInterval = null;

        if ($userConfiguration->snapExpirationInterval !== null) {
            $snapExpirationDaysInterval = (int) $userConfiguration->snapExpirationInterval->format('%d');
        }

        $this->connection->executeQuery($query, [
            'user_id' => $userId->toRfc4122(),
            'enabled_conversion_formats' => json_encode(array_map(
                static fn (CommonFormat $format): string => $format->value,
                $userConfiguration->enabledConversionFormats
            )),
            'snap_expiration_days_interval' => $snapExpirationDaysInterval,
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(Uuid $userId, UserConfiguration $userConfiguration): void
    {
        $query = '
            UPDATE user_configuration
            SET enabled_conversion_formats = :enabled_conversion_formats,
                snap_expiration_days_interval = :snap_expiration_days_interval,
            WHERE user_id = :user_id
        ';

        $snapExpirationDaysInterval = null;

        if ($userConfiguration->snapExpirationInterval !== null) {
            $snapExpirationDaysInterval = (int) $userConfiguration->snapExpirationInterval->format('%d');
        }

        $this->connection->executeQuery($query, [
            'user_id' => $userId->toRfc4122(),
            'enabled_conversion_formats' => json_encode(array_map(
                static fn (CommonFormat $format): string => $format->value,
                $userConfiguration->enabledConversionFormats
            )),
            'snap_expiration_days_interval' => $snapExpirationDaysInterval,
        ]);
    }

    /**
     * @param DbResult $dbResult
     */
    private static function toUserConfiguration(array $dbResult): UserConfiguration
    {
        /** @var list<string> $formatsRawValues */
        $formatsRawValues = json_decode($dbResult['enabled_conversion_formats'], true, 512, JSON_THROW_ON_ERROR);

        $enabledConversionFormats = array_map(
            static fn (string $format) => CommonFormat::from($format),
            $formatsRawValues
        );

        $dateInterval = null;

        if ($dbResult['snap_expiration_days_interval'] !== null) {
            $dateInterval = new DateInterval(sprintf('P%dD', $dbResult['snap_expiration_days_interval']));
        }

        return new UserConfiguration(
            enabledConversionFormats: $enabledConversionFormats,
            snapExpirationInterval: $dateInterval,
        );
    }
}

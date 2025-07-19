<?php

declare(strict_types=1);

namespace App\Infrastructure\Impl\Snap\Repository;

use App\Application\Domain\Snap\Exception\FileNotFoundException;
use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Application\Domain\Snap\SnapFactory;
use App\Application\Domain\Snap\SnapRepositoryInterface;
use App\Infrastructure\Impl\MariadbTools;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type DbResult array{
 *      id:string,
 *      user_id:string,
 *      original_filename:string,
 *      mime_type:string,
 *      creation_date:string,
 *      last_seen_date:string|null
 *  }
 */
final readonly class MariadbSnapRepository implements SnapRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private SnapFactory $snapFactory,
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(Snap $snap): void
    {
        $query = '
            INSERT INTO snap (id, user_id, original_filename, mime_type, creation_date, last_seen_date)
            VALUES (:id, :user_id, :original_filename, :mime_type, :creation_date, :last_seen_date)
        ';

        $this->connection->executeQuery($query, [
            'id' => $snap->getId()->toRfc4122(),
            'user_id' => $snap->getUserId()->toRfc4122(),
            'original_filename' => $snap->getOriginalFilename(),
            'mime_type' => $snap->getMimeType()->value,
            'creation_date' => $snap->getCreationDate()->format(MariadbTools::DATETIME_FORMAT),
            'last_seen_date' => $snap->getLastSeenDate()?->format(MariadbTools::DATETIME_FORMAT),
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateLastSeenDate(Uuid $id, \DateTimeInterface $lastSeenDate): void
    {
        $query = 'UPDATE snap SET last_seen_date = :last_seen_date WHERE id = :id';

        $this->connection->executeQuery($query, [
            'id' => $id->toRfc4122(),
            'last_seen_date' => $lastSeenDate->format(MariadbTools::DATETIME_FORMAT),
        ]);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findOneById(Uuid $id): ?Snap
    {
        $query = '
            SELECT id, user_id, original_filename, mime_type, creation_date, last_seen_date
            FROM snap
            WHERE id = :id
        ';

        /** @var DbResult|false $dbResult */
        $dbResult = $this->connection->fetchAssociative($query, ['id' => $id->toRfc4122()]);

        if ($dbResult === false) {
            return null;
        }

        return $this->toSnap($dbResult);
    }

    /**
     * @return list<Snap>
     *
     * @throws Exception
     * @throws \Exception
     */
    public function findByIds(array $ids): array
    {
        $query = '
            SELECT id, user_id, original_filename, mime_type, creation_date, last_seen_date
            FROM snap
            WHERE id IN (:ids)
        ';

        /** @var list<DbResult> $dbResults */
        $dbResults = $this->connection->fetchAllAssociative(
            $query,
            ['ids' => array_map(
                static fn (Uuid $id) => $id->toRfc4122(),
                $ids
            )],
            ['ids' => ArrayParameterType::STRING]
        );

        return array_map(
            fn (array $dbResult) => $this->toSnap($dbResult),
            $dbResults
        );
    }

    /**
     * @return list<Snap>
     *
     * @throws Exception
     * @throws \Exception
     */
    public function findByUser(Uuid $userId, int $offset, int $limit, ?\DateTimeInterface $expirationCheckDate = null): array
    {
        $parameters = [
            'user_id' => $userId->toRfc4122(),
            'limit' => $limit,
            'offset' => $offset,
        ];

        $types = [
            'user_id' => ParameterType::STRING,
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ];

        $query = '
            SELECT id, user_id, original_filename, mime_type, creation_date, last_seen_date
            FROM snap
            WHERE user_id = :user_id
        ';

        if ($expirationCheckDate !== null) {
            $query .= '
                AND (
                    last_seen_date > :date
                    OR (
                        last_seen_date IS NULL
                        AND creation_date > :date
                    )
                )
            ';

            $parameters['date'] = $expirationCheckDate->format(MariadbTools::DATETIME_FORMAT);
            $types['date'] = ParameterType::STRING;
        }

        $query .= '
            ORDER BY creation_date DESC
            LIMIT :limit
            OFFSET :offset
        ';

        /** @var list<DbResult> $dbResults */
        $dbResults = $this->connection->fetchAllAssociative($query, $parameters, $types);

        return array_map(
            fn (array $dbResult) => $this->toSnap($dbResult),
            $dbResults
        );
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function countByUser(Uuid $userId, ?\DateTimeInterface $expirationCheckDate = null): int
    {
        $parameters = ['user_id' => $userId->toRfc4122()];

        $query = 'SELECT COUNT(id) FROM snap WHERE user_id = :user_id';

        if ($expirationCheckDate !== null) {
            $query .= '
                AND (
                    last_seen_date > :date
                    OR (
                        last_seen_date IS NULL
                        AND creation_date > :date
                    )
                )
            ';

            $parameters['date'] = $expirationCheckDate->format(MariadbTools::DATETIME_FORMAT);
        }

        $count = $this->connection->fetchOne($query, $parameters);

        if (is_int($count) === false) {
            throw new \RuntimeException(sprintf('%s doctrine returns is not a correct count value.', __FUNCTION__));
        }

        return $count;
    }

    /**
     * @throws Exception
     */
    public function deleteOneById(Uuid $id): void
    {
        $query = 'DELETE FROM snap WHERE id = :id';

        $this->connection->executeQuery($query, ['id' => $id->toRfc4122()]);
    }

    /**
     * @throws Exception
     */
    public function deleteByIds(array $ids): void
    {
        $query = 'DELETE FROM snap WHERE id IN (:ids)';

        $this->connection->executeQuery(
            $query,
            [
                'ids' => array_map(
                    static fn (Uuid $id) => $id->toRfc4122(),
                    $ids
                ),
            ],
            [
                'user_id' => ParameterType::STRING,
                'ids' => ArrayParameterType::STRING,
            ]
        );
    }

    /**
     * @return list<Snap>
     * @throws Exception
     * @throws FileNotFoundException
     */
    public function findExpiredSnaps(\DateTimeInterface $date): array
    {
        $query = '
            SELECT id, user_id, original_filename, mime_type, creation_date, last_seen_date
            FROM snap
            WHERE
                last_seen_date < :date
                OR (
                    last_seen_date IS NULL
                    AND creation_date < :date
                )
            ORDER BY creation_date ASC
        ';

        /** @var list<DbResult> $dbResults */
        $dbResults = $this->connection->fetchAllAssociative($query, [
            'date' => $date->format(MariadbTools::DATETIME_FORMAT)
        ]);

        return array_map(
            fn (array $dbResult) => $this->toSnap($dbResult),
            $dbResults
        );
    }

    /**
     * @param DbResult $dbResult
     *
     * @throws FileNotFoundException
     * @throws \Exception
     */
    private function toSnap(array $dbResult): Snap
    {
        return $this->snapFactory->create(
            id: Uuid::fromString($dbResult['id']),
            userId: Uuid::fromString($dbResult['user_id']),
            originalFilename: $dbResult['original_filename'],
            mimeType: MimeType::from($dbResult['mime_type']),
            creationDate: new \DateTimeImmutable($dbResult['creation_date']),
            lastSeenDate: $dbResult['last_seen_date'] !== null ? new \DateTimeImmutable($dbResult['last_seen_date']) : null
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Infrastructure\Entity\Snap\Repository;

use App\Application\Domain\Entity\Snap\Factory\SnapFactory;
use App\Application\Domain\Entity\Snap\Snap;
use App\Application\Domain\Entity\Snap\MimeType;
use App\Application\Domain\Entity\Snap\Repository\SnapRepositoryInterface;
use App\Infrastructure\Entity\MariadbTools;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Uid\Uuid;

final readonly class MariadbSnapRepository implements SnapRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private SnapFactory $snapFactory
    )
    {
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
            'last_seen_date' => $snap->getLastSeenDate()?->format(MariadbTools::DATETIME_FORMAT)
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateLastSeenDate(Uuid $id, DateTimeInterface $lastSeenDate): void
    {
        $query = 'UPDATE snap SET last_seen_date = :last_seen_date WHERE id = :id';

        $this->connection->executeQuery($query, [
            'id' => $id->toRfc4122(),
            'last_seen_date' => $lastSeenDate->format(MariadbTools::DATETIME_FORMAT)
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

        $dbResult = $this->connection->fetchAssociative($query, ['id' => $id->toRfc4122()]);

        if (false === $dbResult) {
            return null;
        }

        return $this->createSnapEntity($dbResult);
    }

    /**
     * @return Snap[]
     * @throws Exception
     * @throws \Exception
     */
    public function findByUser(Uuid $userId, int $offset, int $limit): array
    {
        $query = '
            SELECT id, user_id, original_filename, mime_type, creation_date, last_seen_date
            FROM snap
            WHERE user_id = :user_id
            ORDER BY creation_date DESC
            LIMIT :limit
            OFFSET :offset
        ';

        $statement = $this->connection->prepare($query);

        $statement->bindValue('user_id', $userId->toRfc4122(), ParameterType::BINARY);
        $statement->bindValue('offset', $offset, ParameterType::INTEGER);
        $statement->bindValue('limit', $limit, ParameterType::INTEGER);

        $dbResults = $statement->executeQuery()->fetchAllAssociative();

        return array_map(
            fn(array $dbResult) => $this->createSnapEntity($dbResult),
            $dbResults
        );
    }

    /**
     * @throws Exception
     */
    public function countByUser(Uuid $userId): int
    {
        $query = 'SELECT COUNT(id) FROM snap WHERE user_id = :user_id';

        return $this->connection->fetchOne($query, ['user_id' => $userId->toRfc4122()]);
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
     * @throws \Exception
     */
    private function createSnapEntity(array $dbResult): Snap
    {
        return $this->snapFactory->create(
            id: Uuid::fromString($dbResult['id']),
            userId: Uuid::fromString($dbResult['user_id']),
            originalFilename: $dbResult['original_filename'],
            mimeType: MimeType::fromString($dbResult['mime_type']),
            creationDate: new DateTime($dbResult['creation_date']),
            lastSeenDate: $dbResult['last_seen_date'] ? new DateTime($dbResult['last_seen_date']) : null
        );
    }
}
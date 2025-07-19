<?php

declare(strict_types=1);

namespace App\Infrastructure\Impl\User\Repository;

use App\Application\Domain\User\User;
use App\Application\Domain\User\UserRepositoryInterface;
use App\Application\Domain\User\UserRole;
use App\Infrastructure\Symfony\Security\Entity\SecurityUserRole;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type DbResult array{
 *      id:non-empty-string,
 *      email:non-empty-string,
 *      password:non-empty-string,
 *      roles:non-empty-string,
 *      authorization_key:non-empty-string,
 *  }
 */
final readonly class MariadbUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception|\JsonException
     */
    public function create(User $user): void
    {
        $query = '
            INSERT INTO user (id, email, password, roles, authorization_key)
            VALUES (:id, :email, :password, :roles, :authorization_key)
        ';

        $this->connection->executeQuery($query, [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'roles' => self::jsonEncodeRolesForInsert($user->getRoles()),
            'authorization_key' => $user->getAuthorizationKey()->toRfc4122(),
        ]);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function findOneByEmail(string $email): ?User
    {
        $query = 'SELECT id, email, password, roles, authorization_key FROM user WHERE email = :email';

        /** @var DbResult|false $dbResult */
        $dbResult = $this->connection->fetchAssociative($query, ['email' => $email]);

        if ($dbResult === false) {
            return null;
        }

        return self::toUser($dbResult);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function findOneByAuthorizationKey(Uuid $authorizationKey): ?User
    {
        $query = '
            SELECT id, email, password, roles, authorization_key
            FROM user
            WHERE authorization_key = :authorization_key
        ';

        /** @var DbResult|false $dbResult */
        $dbResult = $this->connection->fetchAssociative(
            $query,
            ['authorization_key' => $authorizationKey->toRfc4122()]
        );

        if ($dbResult === false) {
            return null;
        }

        return self::toUser($dbResult);
    }

    /**
     * @throws Exception
     */
    public function updatePassword(Uuid $id, string $hashedPassword): void
    {
        $query = 'UPDATE user SET password = :password WHERE id = :id';

        $this->connection->executeQuery($query, [
            'password' => $hashedPassword,
            'id' => $id->toRfc4122(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateAuthorizationKey(Uuid $id, Uuid $authorizationKey): void
    {
        $query = 'UPDATE user SET authorization_key = :authorization_key WHERE id = :id';

        $this->connection->executeQuery($query, [
            'authorization_key' => $authorizationKey->toRfc4122(),
            'id' => $id->toRfc4122(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateEmail(Uuid $id, string $email): void
    {
        $query = 'UPDATE user SET email = :email WHERE id = :id';

        $this->connection->executeQuery($query, [
            'email' => $email,
            'id' => $id->toRfc4122(),
        ]);
    }

    /**
     * @param DbResult $dbResult
     *
     * @throws \JsonException
     * @throws \Exception
     */
    private static function toUser(array $dbResult): User
    {
        /** @var list<string> $rolesJson */
        $rolesJson = json_decode($dbResult['roles'], true, 512, JSON_THROW_ON_ERROR);

        $roles = array_map(
            static fn (string $role): UserRole => SecurityUserRole::valueToUserRole($role),
            $rolesJson
        );

        return new User(
            id: Uuid::fromString($dbResult['id']),
            email: $dbResult['email'],
            password: $dbResult['password'],
            roles: $roles,
            authorizationKey: Uuid::fromString($dbResult['authorization_key']),
        );
    }

    /**
     * @param list<UserRole> $roles
     *
     * @throws \JsonException
     */
    private static function jsonEncodeRolesForInsert(array $roles): string
    {
        return json_encode(
            array_map(
                static fn (UserRole $role): string => SecurityUserRole::userRoleToValue($role),
                $roles
            ),
            JSON_THROW_ON_ERROR
        );
    }
}

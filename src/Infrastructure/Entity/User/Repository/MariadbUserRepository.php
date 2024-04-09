<?php
declare(strict_types=1);

namespace App\Infrastructure\Entity\User\Repository;

use App\Application\Domain\Entity\User\Repository\UserRepositoryInterface;
use App\Application\Domain\Entity\User\User;
use App\Application\Domain\Entity\User\UserRole;
use App\Infrastructure\Symfony\Security\Entity\RoleTools;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Symfony\Component\Uid\Uuid;

final readonly class MariadbUserRepository implements UserRepositoryInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function save(User $user): void
    {
        $query = 'SELECT COUNT(id) FROM user WHERE id = :id';
        $exists = (bool)$this->connection->fetchOne($query, ['id' => $user->getId()->toRfc4122()]);

        if (true === $exists) {
            $this->update($user);
        } else {
            $this->insert($user);
        }
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function findOneById(Uuid $id): ?User
    {
        $query = 'SELECT id, email, password, roles, authorization_key FROM user WHERE id = :id';
        $dbResult = $this->connection->fetchAssociative($query, ['id' => $id->toRfc4122()]);

        if (false === $dbResult) {
            return null;
        }

        return $this->createUserEntity($dbResult);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function findOneByEmail(string $email): ?User
    {
        $query = 'SELECT id, email, password, roles, authorization_key FROM user WHERE email = :email';
        $dbResult = $this->connection->fetchAssociative($query, ['email' => $email]);

        if (false === $dbResult) {
            return null;
        }

        return $this->createUserEntity($dbResult);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function findOneByAuthorizationKey(Uuid $authorizationKey): ?User
    {
        $query = '
            SELECT id, email, password, roles, authorization_key
            FROM user
            WHERE authorization_key = :authorization_key
        ';

        $dbResult = $this->connection->fetchAssociative(
            $query,
            ['authorization_key' => $authorizationKey->toRfc4122()]
        );

        if (false === $dbResult) {
            return null;
        }

        return $this->createUserEntity($dbResult);
    }

    /**
     * @throws Exception
     */
    public function updatePassword(Uuid $id, string $hashedPassword): void
    {
        $query = 'UPDATE user SET password = :password WHERE id = :id';

        $this->connection->executeQuery($query, [
            'password' => $hashedPassword,
            'id' => $id->toRfc4122()
        ]);

        test
    }

    /**
     * @throws Exception
     */
    public function updateAuthorizationKey(Uuid $id, Uuid $authorizationKey): void
    {
        $query = 'UPDATE user SET authorization_key = :authorization_key WHERE id = :id';

        $this->connection->executeQuery($query, [
            'authorization_key' => $authorizationKey->toRfc4122(),
            'id' => $id->toRfc4122()
        ]);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function insert(User $user): void
    {
        $query = '
            INSERT INTO user (id, email, password, roles, authorization_key)
            VALUES (:id, :email, :password, :roles, :authorization_key)
        ';

        $this->connection->executeQuery($query, [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'roles' => $this->jsonEncodeRolesForInsert($user->getRoles()),
            'authorization_key' => $user->getAuthorizationKey()->toRfc4122()
        ]);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function update(User $user): void
    {
        $query = '
            UPDATE user
            SET (email, password, roles, authorization_key) = (:email, :password, :roles, :authorization_key)
            WHERE id = :id
        ';

        $this->connection->executeQuery($query, [
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'roles' => $this->jsonEncodeRolesForInsert($user->getRoles()),
            'authorization_key' => $user->getAuthorizationKey()->toRfc4122()
        ]);
    }

    /**
     * @throws JsonException
     */
    private function createUserEntity(array $dbResult): User
    {
        $roles = array_map(
            static fn(string $role): UserRole => RoleTools::fromStringToUserRole($role),
            json_decode($dbResult['roles'], true, 512, JSON_THROW_ON_ERROR)
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
     * @param UserRole[] $roles
     * @throws JsonException
     */
    private function jsonEncodeRolesForInsert(array $roles): string
    {
        return json_encode(
            array_map(
                static fn(UserRole $role): string => RoleTools::fromUserRoleToString($role),
                $roles
            ),
            JSON_THROW_ON_ERROR
        );
    }
}
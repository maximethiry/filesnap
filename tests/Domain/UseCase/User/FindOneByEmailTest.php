<?php

declare(strict_types=1);

namespace App\Tests\Domain\UseCase\User;

use App\Application\Domain\User\User;
use App\Application\Domain\User\UserRepositoryInterface;
use App\Application\Domain\User\UserRole;
use App\Application\UseCase\User\FindOneByEmail\FindOneUserByEmailRequest;
use App\Application\UseCase\User\FindOneByEmail\FindOneUserByEmailUseCase;
use App\Tests\FilesnapTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Uid\Uuid;

final class FindOneByEmailTest extends FilesnapTestCase
{
    /**
     * @return list<array{0:User}>
     */
    public static function provider(): array
    {
        $email = 'user@example.com';
        $password = 'this-is-a-hashed-password';

        return [
            [
                new User(
                    id: Uuid::v4(),
                    email: $email,
                    password: $password,
                    roles: [UserRole::User],
                    authorizationKey: Uuid::v4()
                ),
            ],
            [
                new User(
                    id: Uuid::v4(),
                    email: $email,
                    password: $password,
                    roles: [UserRole::User, UserRole::Admin],
                    authorizationKey: Uuid::v4()
                ),
            ],
        ];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('provider')]
    public function test(User $expectedUser): void
    {
        $request = new FindOneUserByEmailRequest($expectedUser->getEmail());

        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);

        $userRepositoryMock
            ->expects($this->once())
            ->method('findOneByEmail')
            ->with($request->getEmail())
            ->willReturn($expectedUser);

        $useCase = new FindOneUserByEmailUseCase($userRepositoryMock);
        $response = $useCase($request);
        $user = $response->getUser();

        self::assertNotNull($user);
        self::assertSame($expectedUser->getId(), $user->getId());
        self::assertSame($expectedUser->getEmail(), $user->getEmail());
        self::assertSame($expectedUser->getPassword(), $user->getPassword());
        self::assertSameSize($expectedUser->getRoles(), $user->getRoles());
        self::assertContainsOnlyInstancesOf(UserRole::class, $user->getRoles());

        foreach ($expectedUser->getRoles() as $role) {
            self::assertContainsEquals($role, $user->getRoles());
        }

        self::assertSame($expectedUser->getAuthorizationKey(), $user->getAuthorizationKey());
    }
}

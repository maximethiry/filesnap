<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Security;

use App\Application\UseCase\User\FindOneByEmail\FindOneUserByEmailRequest;
use App\Application\UseCase\User\FindOneByEmail\FindOneUserByEmailUseCase;
use App\Application\UseCase\User\UpdatePasswordById\UpdateUserPasswordByIdRequest;
use App\Application\UseCase\User\UpdatePasswordById\UpdateUserPasswordByIdUseCase;
use App\Infrastructure\Symfony\Security\Entity\SecurityUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 * @implements PasswordUpgraderInterface<SecurityUser>
 */
final readonly class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private FindOneUserByEmailUseCase $findOneUserByEmailUseCase,
        private UpdateUserPasswordByIdUseCase $updateUserPasswordByIdUseCase,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if ($this->supportsClass($user::class) === false) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class || is_subclass_of($class, SecurityUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $useCaseResponse = ($this->findOneUserByEmailUseCase)(new FindOneUserByEmailRequest($identifier));
        $user = $useCaseResponse->getUser();

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return SecurityUser::create($user);
    }

    /**
     * This method should not block the login, that's why it does not throw anything
     * cf PasswordUpgraderInterface::upgradePassword phpdoc.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        try {
            ($this->updateUserPasswordByIdUseCase)(
                new UpdateUserPasswordByIdRequest($user->getId(), $newHashedPassword, true)
            );
        } catch (\Exception) {
        }
    }
}

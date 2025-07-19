<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Security\Entity;

use App\Application\Domain\User\User;
use App\Application\Domain\User\UserRole;
use App\Infrastructure\UserConfiguration\UserConfiguration;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param list<SecurityUserRole> $securityRoles
     * @param non-empty-string $email
     * @param non-empty-string $password
     */
    public function __construct(
        private Uuid $id,
        private string $email,
        private string $password,
        private array $securityRoles,
        private Uuid $authorizationKey,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_map(
            static fn (SecurityUserRole $role): string => $role->value,
            $this->securityRoles
        );
    }

    public function getAuthorizationKey(): Uuid
    {
        return $this->authorizationKey;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public static function create(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            password: $user->getPassword(),
            securityRoles: array_map(
                static fn (UserRole $role): SecurityUserRole => SecurityUserRole::fromUserRole($role),
                $user->getRoles()
            ),
            authorizationKey: $user->getAuthorizationKey()
        );
    }
}

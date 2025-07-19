<?php

declare(strict_types=1);

namespace App\Infrastructure\Impl\User\Service;

use App\Application\Domain\User\Service\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    private static ?PasswordHasherFactoryInterface $factory = null;

    /**
     * @param string $plainPassword
     * @return non-empty-string
     */
    public function hash(string $plainPassword): string
    {
        /** @var non-empty-string $hashedPassword */
        $hashedPassword = $this
            ->getFactory()
            ->getPasswordHasher('default')
            ->hash($plainPassword);

        return $hashedPassword;
    }

    private function getFactory(): PasswordHasherFactoryInterface
    {
        if (self::$factory !== null) {
            return self::$factory;
        }

        return self::$factory = new PasswordHasherFactory([
            'default' => ['algorithm' => 'auto'],
        ]);
    }
}

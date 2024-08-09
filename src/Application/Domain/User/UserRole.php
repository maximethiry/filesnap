<?php

declare(strict_types=1);

namespace App\Application\Domain\User;

enum UserRole
{
    case User;
    case Admin;
}

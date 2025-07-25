<?php

declare(strict_types=1);

namespace App\Enums;

enum RolesEnum: string
{
    case ADMIN = 'super administrator';
    case SALESMAN = 'salesman';
}

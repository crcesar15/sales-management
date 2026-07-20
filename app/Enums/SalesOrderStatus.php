<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case FULFILLED = 'fulfilled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

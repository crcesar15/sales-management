<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case HELD = 'held';
    case CANCELLED = 'cancelled';
}

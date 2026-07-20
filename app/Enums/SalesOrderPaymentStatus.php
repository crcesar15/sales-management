<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesOrderPaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
}

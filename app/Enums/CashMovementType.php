<?php

declare(strict_types=1);

namespace App\Enums;

enum CashMovementType: string
{
    case CASH_IN = 'cash_in';
    case CASH_OUT = 'cash_out';
}

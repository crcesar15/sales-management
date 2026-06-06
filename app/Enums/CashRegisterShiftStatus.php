<?php

declare(strict_types=1);

namespace App\Enums;

enum CashRegisterShiftStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case FORCED_CLOSE = 'forced_close';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum MarginType: string
{
    case PERCENT = 'percent';
    case AMOUNT = 'amount';
}

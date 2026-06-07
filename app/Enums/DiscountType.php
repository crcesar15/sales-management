<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case FLAT = 'flat';
    case PERCENTAGE = 'percentage';
}

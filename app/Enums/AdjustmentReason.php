<?php

declare(strict_types=1);

namespace App\Enums;

enum AdjustmentReason: string
{
    case INITIAL_STOCK = 'initial_stock';
    case PHYSICAL_AUDIT = 'physical_audit';
    case ROBBERY = 'robbery';
    case EXPIRY = 'expiry';
    case DAMAGE = 'damage';
    case CORRECTION = 'correction';
    case OTHER = 'other';
}

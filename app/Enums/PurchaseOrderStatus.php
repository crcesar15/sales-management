<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';
}

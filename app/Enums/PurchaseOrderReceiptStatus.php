<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseOrderReceiptStatus: string
{
    case NOT_RECEIVED = 'not_received';
    case PARTIALLY_RECEIVED = 'partially_received';
    case FULLY_RECEIVED = 'fully_received';
}

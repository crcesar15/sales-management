<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case CHECK = 'check';
    case CREDIT_CARD = 'credit_card';
}

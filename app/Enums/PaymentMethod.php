<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case QR = 'qr';
    case TRANSFER = 'transfer';
}

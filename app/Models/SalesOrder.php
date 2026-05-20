<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;

    /** @return BelongsTo<Customer, $this>*/
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

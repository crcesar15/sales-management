<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class CustomerReceivableEntry extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerReceivableEntryFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'customer_id',
        'sales_order_id',
        'sales_order_payment_id',
        'user_id',
        'type',
        'amount',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /** @return BelongsTo<SalesOrderPayment, $this> */
    public function salesOrderPayment(): BelongsTo
    {
        return $this->belongsTo(SalesOrderPayment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('customer_receivable_entry')
            ->dontSubmitEmptyLogs();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

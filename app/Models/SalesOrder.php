<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'customer_id',
        'user_id',
        'store_id',
        'cash_register_shift_id',
        'fulfilled_by',
        'status',
        'payment_status',
        'discount_type',
        'discount_value',
        'sub_total',
        'discount',
        'tax_amount',
        'total',
        'token',
        'notes',
        'validated_at',
        'fulfilled_at',
        'completed_at',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<CashRegisterShift, $this> */
    public function cashRegisterShift(): BelongsTo
    {
        return $this->belongsTo(CashRegisterShift::class);
    }

    /** @return BelongsTo<User, $this> */
    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /** @return HasMany<SalesOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /** @return HasMany<SalesOrderPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SalesOrderPayment::class);
    }

    /** @return HasMany<CustomerReceivableEntry, $this> */
    public function receivableEntries(): HasMany
    {
        return $this->hasMany(CustomerReceivableEntry::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('sales_order')
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        self::creating(function (self $order): void {
            $order->token = (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'payment_status' => SalesOrderPaymentStatus::class,
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'sub_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
            'validated_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'completed_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}

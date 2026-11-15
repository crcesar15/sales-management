<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class PurchaseOrder extends Model implements HasMedia
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'status',
        'is_paid',
        'paid_at',
        'order_date',
        'expected_arrival_date',
        'sub_total',
        'discount',
        'discount_type',
        'discount_value',
        'total',
        'notes',
        'completion_notes',
        'proof_of_payment_type',
        'proof_of_payment_number',
    ];

    protected $appends = ['receipt_status', 'gross_sub_total', 'line_discount_total'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return HasMany<PurchaseOrderProduct, $this> */
    public function lineItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }

    /** @return HasMany<ReceptionOrder, $this> */
    public function receptionOrders(): HasMany
    {
        return $this->hasMany(ReceptionOrder::class);
    }

    /** Whether all line items have been fully received. */
    public function getIsFullyReceivedAttribute(): bool
    {
        $this->loadMissing('lineItems.receptionOrderItems.receptionOrder');

        if ($this->lineItems->isEmpty()) {
            return false;
        }

        return $this->lineItems->every(function (PurchaseOrderProduct $item): bool {
            $receivedQuantity = (float) $item->received_quantity;

            return $receivedQuantity >= (float) $item->quantity;
        });
    }

    /** Whether at least one line item has been partially received but not all are complete. */
    public function getIsPartiallyReceivedAttribute(): bool
    {
        if ($this->is_fully_received) {
            return false;
        }

        $this->loadMissing('lineItems.receptionOrderItems.receptionOrder');

        return $this->lineItems->some(function (PurchaseOrderProduct $item): bool {
            return (float) $item->received_quantity > 0;
        });
    }

    public function getReceiptStatusAttribute(): PurchaseOrderReceiptStatus
    {
        if ($this->is_fully_received) {
            return PurchaseOrderReceiptStatus::FULLY_RECEIVED;
        }

        if ($this->is_partially_received) {
            return PurchaseOrderReceiptStatus::PARTIALLY_RECEIVED;
        }

        return PurchaseOrderReceiptStatus::NOT_RECEIVED;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof-of-payment')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('purchase_order')
            ->dontSubmitEmptyLogs();
    }

    /** @return Attribute<float, never> */
    protected function grossSubTotal(): Attribute
    {
        return Attribute::make(get: fn (): float => round((float) $this->lineItems->sum('gross_total'), 2));
    }

    /** @return Attribute<float, never> */
    protected function lineDiscountTotal(): Attribute
    {
        return Attribute::make(get: fn (): float => round((float) $this->lineItems->sum('discount_amount'), 2));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_arrival_date' => 'date',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'proof_of_payment_type' => PaymentMethod::class,
            'sub_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

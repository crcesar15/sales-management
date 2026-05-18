<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PurchaseOrderFactory;
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
        'order_date',
        'expected_arrival_date',
        'sub_total',
        'discount',
        'total',
        'notes',
        'proof_of_payment_type',
        'proof_of_payment_number',
    ];

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_arrival_date' => 'date',
            'proof_of_payment_type' => PaymentMethod::class,
            'sub_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

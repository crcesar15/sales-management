<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

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
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

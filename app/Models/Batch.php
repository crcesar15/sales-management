<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'product_variant_id',
        'reception_order_id',
        'store_id',
        'expiry_date',
        'batch_identifier',
        'initial_quantity',
        'remaining_quantity',
        'missing_quantity',
        'sold_quantity',
        'transferred_quantity',
        'status',
    ];

    protected $appends = ['expiry_status'];

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<ReceptionOrder, $this> */
    public function receptionOrder(): BelongsTo
    {
        return $this->belongsTo(ReceptionOrder::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<StockAdjustment, $this> */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** @param  Builder<self>  $query */
    public function scopeFulfillableAt(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId)
            ->active()
            ->where('remaining_quantity', '>', 0)
            ->where(fn (Builder $query) => $query
                ->whereNull('expiry_date')
                ->orWhereDate('expiry_date', '>=', today()));
    }

    /** @param  Builder<self>  $query */
    public function scopeFefo(Builder $query): void
    {
        $query->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->orderBy('created_at');
    }

    /**
     * Batches available for FEFO consumption.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query, int $variantId, int $storeId): Builder
    {
        return $query->where('product_variant_id', $variantId)
            ->fulfillableAt($storeId)
            ->fefo();
    }

    /**
     * Batches expiring within the given number of days.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeExpiringSoon(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days)->endOfDay()->toDateTimeString())
            ->active();
    }

    // ─── Activity Log ────────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('batch')
            ->dontSubmitEmptyLogs();
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    /**
     * Returns 'ok', 'expiring_soon', 'expired', or null (no expiry date).
     *
     * @return Attribute<string|null, void>
     */
    protected function expiryStatus(): Attribute
    {
        return Attribute::get(function (): ?string {
            $date = $this->getAttribute('expiry_date');

            if ($date === null) {
                return null;
            }

            $threshold = (int) Setting::get('expiry_alert_days', 30);

            if ($date->isPast()) {
                return 'expired';
            }

            if ($date->lessThanOrEqualTo(now()->addDays($threshold))) {
                return 'expiring_soon';
            }

            return 'ok';
        });
    }

    // ─── Casts ───────────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

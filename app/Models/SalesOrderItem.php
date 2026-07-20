<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class SalesOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\SalesOrderItemFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'sales_order_id',
        'product_variant_id',
        'sale_unit_id',
        'quantity',
        'unit_price',
        'conversion_factor',
        'line_total',
    ];

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<ProductVariantUnit, $this> */
    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(ProductVariantUnit::class, 'sale_unit_id');
    }

    /** @return HasMany<SalesOrderStockAllocation, $this> */
    public function stockAllocations(): HasMany
    {
        return $this->hasMany(SalesOrderStockAllocation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('sales_order_item')
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'conversion_factor' => 'integer',
            'line_total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

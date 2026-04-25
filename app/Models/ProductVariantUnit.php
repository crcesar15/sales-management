<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ProductVariantUnit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_variant_id',
        'type',
        'name',
        'conversion_factor',
        'price',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'conversion_factor' => 'integer',
        'price' => 'decimal:2',
    ];

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('product_variant_unit')
            ->dontSubmitEmptyLogs();
    }
}

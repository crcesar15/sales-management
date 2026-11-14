<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VariantStoreLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class VariantStoreLocation extends Model
{
    /** @use HasFactory<VariantStoreLocationFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = ['product_variant_id', 'store_id', 'location_code'];

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('variant_store_location')->dontSubmitEmptyLogs();
    }
}

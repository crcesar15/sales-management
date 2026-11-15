<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ReplacementCostEvent extends Model
{
    use LogsActivity;

    protected $fillable = ['product_variant_id', 'vendor_id', 'catalog_id', 'purchase_order_id', 'purchase_order_item_id', 'user_id', 'source', 'supplier_name', 'unit_name', 'unit_price', 'conversion_factor', 'cost', 'previous_cost', 'invalidated_at', 'reviewed_at', 'reviewed_by'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('replacement_cost')->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return ['cost' => 'decimal:12', 'previous_cost' => 'decimal:12', 'unit_price' => 'decimal:8', 'conversion_factor' => 'integer', 'invalidated_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}

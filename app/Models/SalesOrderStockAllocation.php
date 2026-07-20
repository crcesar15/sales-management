<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class SalesOrderStockAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\SalesOrderStockAllocationFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = ['sales_order_item_id', 'batch_id', 'quantity'];

    /** @return BelongsTo<SalesOrderItem, $this> */
    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('sales_order_stock_allocation')
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class ReceptionOrderBatchAllocation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'reception_order_product_id',
        'purchase_unit_quantity',
        'loose_unit_quantity',
        'base_quantity',
        'expiry_date',
        'batch_identifier',
    ];

    /** @return BelongsTo<ReceptionOrderProduct, $this> */
    public function receptionOrderProduct(): BelongsTo
    {
        return $this->belongsTo(ReceptionOrderProduct::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('reception_order_batch_allocation')
            ->dontSubmitEmptyLogs();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

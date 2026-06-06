<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class CashRegister extends Model
{
    /** @use HasFactory<\Database\Factories\CashRegisterFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'status',
        'is_default',
    ];

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<CashRegisterShift, $this> */
    public function shifts(): HasMany
    {
        return $this->hasMany(CashRegisterShift::class);
    }

    /** @return HasOne<CashRegisterShift, $this> */
    public function currentShift(): HasOne
    {
        return $this->hasOne(CashRegisterShift::class)->where('status', CashRegisterShiftStatus::OPEN->value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('cash_register')
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CashRegisterStatus::class,
            'is_default' => 'boolean',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

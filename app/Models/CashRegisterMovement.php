<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class CashRegisterMovement extends Model
{
    /** @use HasFactory<\Database\Factories\CashRegisterMovementFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'cash_register_shift_id',
        'user_id',
        'type',
        'amount',
        'reason',
    ];

    /** @return BelongsTo<CashRegisterShift, $this> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashRegisterShift::class, 'cash_register_shift_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('cash_register_movement')
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

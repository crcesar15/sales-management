<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashRegisterShiftStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class CashRegisterShift extends Model
{
    /** @use HasFactory<\Database\Factories\CashRegisterShiftFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'status',
        'opening_balance',
        'closing_balance',
        'expected_closing',
        'difference',
        'opened_at',
        'closed_at',
        'notes',
    ];

    /** @return BelongsTo<CashRegister, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<CashRegisterMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashRegisterMovement::class, 'cash_register_shift_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('cash_register_shift')
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CashRegisterShiftStatus::class,
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'expected_closing' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('store')
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        Route::bind('store', fn ($value) => Store::withTrashed()->findOrFail($value));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }

    /** @return Attribute<string, void> */
    protected function code(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => mb_strtoupper($value));
    }
}

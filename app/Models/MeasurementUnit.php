<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MeasurementUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class MeasurementUnit extends Model
{
    /** @use HasFactory<MeasurementUnitFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['name', 'abbreviation'];

    /** @return HasMany<Product, $this>*/
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('measurement_unit')
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();
        Route::bind('measurementUnit', fn ($value) => MeasurementUnit::withTrashed()->findOrFail($value));
    }
}

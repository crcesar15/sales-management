<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'pivot',
    ];

    /** @return BelongsToMany<Product, $this, Pivot>*/
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function hasActiveProducts(): bool
    {
        return $this->products()->whereNull('products.deleted_at')->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('category')
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();
        Route::bind('category', fn ($value) => Category::withTrashed()->findOrFail($value));
    }
}

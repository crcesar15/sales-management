<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MeasurementUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MeasurementUnit extends Model
{
    /** @use HasFactory<MeasurementUnitFactory> */
    use HasFactory;

    protected $fillable = ['name', 'abbreviation'];

    /** @return HasMany<Product, $this>*/
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

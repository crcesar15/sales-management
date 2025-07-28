<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MeasurementUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\MeasurementUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementUnit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

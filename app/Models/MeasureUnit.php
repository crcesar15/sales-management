<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MeasureUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\MeasureUnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasureUnit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class MeasureUnit extends Model
{
    /** @use HasFactory<MeasureUnitFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /** @return HasMany<Product, $this>*/
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

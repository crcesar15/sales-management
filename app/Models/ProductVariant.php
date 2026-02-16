<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ProductVariant extends Model implements HasMedia
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'product_id',
        'identifier',
        'price',
        'stock',
        'status',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * @return BelongsTo<Product, $this>*/
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsToMany<Vendor, $this, Pivot>*/
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'catalog', 'product_variant_id', 'vendor_id')
            ->withPivot('price', 'payment_terms', 'details', 'status');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10);
    }

    /** @return BelongsToMany<ProductOptionValue, $this>*/
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        );
    }

    /** @return Attribute<string, never> */
    protected function name(): Attribute
    {
        $values = $this->values;

        $formatted = [];

        foreach ($values as $value) {
            $formatted[] = $value->option?->name . ': ' . $value->value;
        }

        return Attribute::make(
            get: fn () => implode(' / ', $formatted)
        );
    }
}

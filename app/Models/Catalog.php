<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $vendor_id
 * @property int $product_variant_id
 * @property float $price
 * @property string|null $payment_terms
 * @property string|null $details
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\CatalogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Catalog whereVendorId($value)
 * @mixin \Eloquent
 */
final class Catalog extends Model
{
    /** @use HasFactory<CatalogFactory> */
    use HasFactory;

    protected $table = 'catalog';

    protected $fillable = [
        'vendor_id',
        'product_variant_id',
        'price',
        'payment_terms',
        'details',
    ];
}

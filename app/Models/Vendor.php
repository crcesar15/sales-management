<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'address',
        'details',
        'status',
        'additional_contacts',
        'meta',
    ];

    /** @return BelongsToMany<ProductVariant, $this, Pivot>*/
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'catalog', 'vendor_id', 'product_variant_id')
            ->withTimestamps()
            ->withPivot('price', 'payment_terms', 'details');
    }

    /** @return HasMany<PurchaseOrder, $this>*/
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @return array{additional_contacts: string, created_at: string, meta: string, updated_at: string}
     */
    protected function casts(): array
    {
        return [
            'additional_contacts' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

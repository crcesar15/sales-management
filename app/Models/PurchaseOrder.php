<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'user_id',
        'vendor_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'discount',
        'total',
        'notes',
        'proof_of_payment_type',
        'proof_of_payment_number',
    ];

    /** @return BelongsTo<User, $this>*/
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Vendor, $this>*/
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsToMany<Product, $this, Pivot>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'purchase_order_product', 'purchase_order_id', 'product_variant_id')
            ->withPivot('quantity', 'price', 'total');
    }
}

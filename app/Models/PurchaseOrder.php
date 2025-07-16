<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PurchaseOrder extends Model
{
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_order_product', 'purchase_order_id', 'product_variant_id')
            ->withPivot('quantity', 'price', 'total');
    }
}

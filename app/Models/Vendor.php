<?php

namespace App\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'address',
        'details',
        'status',
        'additional_contacts',
    ];

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'catalog', 'vendor_id', 'product_variant_id')
            ->withTimestamps()
            ->withPivot('price', 'payment_terms', 'details');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    protected function casts(): array
    {
        return [
            'additional_contacts' => 'array',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

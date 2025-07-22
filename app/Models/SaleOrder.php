<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaleOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property string $status
 * @property string $payment_method
 * @property string|null $notes
 * @property float $sub_total
 * @property float $discount
 * @property float $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\SaleOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleOrder whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class SaleOrder extends Model
{
    /** @use HasFactory<SaleOrderFactory> */
    use HasFactory;
}

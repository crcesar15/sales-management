<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SalesOrderFactory;
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
 * @method static \Database\Factories\SalesOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;
}

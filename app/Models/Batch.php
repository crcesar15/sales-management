<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $reception_order_id
 * @property string|null $expiry_date
 * @property int $initial_quantity
 * @property int $remaining_quantity
 * @property int $missing_quantity
 * @property int $sold_quantity
 * @property int $transferred_quantity
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\BatchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereInitialQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereMissingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereProductVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereReceptionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereRemainingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereSoldQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereTransferredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Batch whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Batch extends Model
{
    /** @use HasFactory<BatchFactory>*/
    use HasFactory;
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReceptionOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $user_id
 * @property int $vendor_id
 * @property string|null $reception_date
 * @property string|null $notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ReceptionOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereReceptionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceptionOrder whereVendorId($value)
 * @mixin \Eloquent
 */
final class ReceptionOrder extends Model
{
    /** @use HasFactory<ReceptionOrderFactory> */
    use HasFactory;
}

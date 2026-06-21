<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property float $price
 * @property string|null $payment_terms
 * @property string|null $details
 * @property string $status
 * @property int|null $unit_id
 * @property int|null $minimum_order_quantity
 * @property int|null $lead_time_days
 */
final class CatalogPivot extends Pivot {}

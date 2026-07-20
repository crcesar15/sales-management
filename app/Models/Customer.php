<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'tax_id',
        'tax_id_name',
        'status',
    ];

    /** @return HasMany<SalesOrder, $this>*/
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /** @return HasMany<CustomerReceivableEntry, $this> */
    public function receivableEntries(): HasMany
    {
        return $this->hasMany(CustomerReceivableEntry::class);
    }

    public function hasSalesOrders(): bool
    {
        return $this->salesOrders()->exists();
    }

    /**
     * Search scope for POS lookup: matches across first_name, last_name, email, phone, tax_id.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function ($q) use ($term): void {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('tax_id', 'like', "%{$term}%");
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('customer')
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array{created_at: string, updated_at: string}
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}

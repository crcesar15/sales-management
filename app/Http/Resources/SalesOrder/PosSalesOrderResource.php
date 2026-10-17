<?php

declare(strict_types=1);

namespace App\Http\Resources\SalesOrder;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SalesOrder */
final class PosSalesOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'cash_register_shift_id' => $this->cash_register_shift_id,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'total' => (float) $this->total,
            'items_count' => (int) $this->items_count,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'display_name' => $this->customer ? mb_trim($this->customer->first_name . ' ' . $this->customer->last_name) : null,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'full_name' => $this->user?->full_name,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
                'code' => $this->store?->code,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

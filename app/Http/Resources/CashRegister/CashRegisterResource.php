<?php

declare(strict_types=1);

namespace App\Http\Resources\CashRegister;

use App\Http\Resources\CashRegisterShift\CashRegisterShiftResource;
use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashRegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'store_id' => $this->store_id ?? null,
            'name' => $this->name ?? null,
            'code' => $this->code ?? null,
            'status' => $this->status ?? null,
            'is_default' => $this->is_default ?? null,
            'store' => $this->whenLoaded('store', fn ($store) => (new StoreResource($store))->resolve()),
            'current_shift' => $this->whenLoaded('currentShift', fn ($shift) => (new CashRegisterShiftResource($shift))->resolve()),
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
            'updated_at' => ! empty($this->updated_at) ? $this->updated_at->toISOString() : null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\CashRegisterShift;

use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Http\Resources\CashRegisterMovement\CashRegisterMovementResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashRegisterShiftResource extends JsonResource
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
            'cash_register_id' => $this->cash_register_id ?? null,
            'user_id' => $this->user_id ?? null,
            'status' => $this->status ?? null,
            'opening_balance' => $this->opening_balance ?? null,
            'closing_balance' => $this->closing_balance ?? null,
            'expected_closing' => $this->expected_closing ?? null,
            'difference' => $this->difference ?? null,
            'opened_at' => ! empty($this->opened_at) ? $this->opened_at->toISOString() : null,
            'closed_at' => ! empty($this->closed_at) ? $this->closed_at->toISOString() : null,
            'notes' => $this->notes ?? null,
            'cash_register' => $this->whenLoaded('register', fn ($register) => (new CashRegisterResource($register))->resolve()),
            'user' => $this->whenLoaded('cashier', fn ($cashier) => (new UserResource($cashier))->resolve()),
            'movements' => $this->relationLoaded('movements')
                ? CashRegisterMovementResource::collection($this->movements)->resolve()
                : [],
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
            'updated_at' => ! empty($this->updated_at) ? $this->updated_at->toISOString() : null,
        ];
    }
}

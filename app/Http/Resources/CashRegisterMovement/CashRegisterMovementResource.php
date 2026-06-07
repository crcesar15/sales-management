<?php

declare(strict_types=1);

namespace App\Http\Resources\CashRegisterMovement;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CashRegisterMovementResource extends JsonResource
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
            'cash_register_shift_id' => $this->cash_register_shift_id ?? null,
            'user_id' => $this->user_id ?? null,
            'type' => $this->type ?? null,
            'amount' => $this->amount ?? null,
            'reason' => $this->reason ?? null,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
            'updated_at' => ! empty($this->updated_at) ? $this->updated_at->toISOString() : null,
        ];
    }
}

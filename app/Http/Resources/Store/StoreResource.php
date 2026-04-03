<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoreResource extends JsonResource
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
            'name' => $this->name ?? null,
            'code' => $this->code ?? null,
            'address' => $this->address ?? null,
            'city' => $this->city ?? null,
            'state' => $this->state ?? null,
            'zip_code' => $this->zip_code ?? null,
            'phone' => $this->phone ?? null,
            'email' => $this->email ?? null,
            'status' => $this->status ?? null,
            'users_count' => $this->whenCounted('users'),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'deleted_at' => ! empty($this->deleted_at) ? $this->deleted_at->toISOString() : null,
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
            'updated_at' => ! empty($this->updated_at) ? $this->updated_at->toISOString() : null,
        ];
    }
}

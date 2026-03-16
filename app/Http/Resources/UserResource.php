<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'status' => $this->status,
            'roles' => $this->getRoleNames(),
            'stores' => StoreResource::collection($this->whenLoaded('stores')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

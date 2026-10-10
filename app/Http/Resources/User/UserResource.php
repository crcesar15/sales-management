<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Http\Resources\Store\StoreResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $this->id ?? null,
            'first_name' => $this->first_name ?? null,
            'last_name' => $this->last_name ?? null,
            'full_name' => $this->full_name ?? null,
            'email' => $this->email ?? null,
            'username' => $this->username ?? null,
            'phone' => $this->phone ?? null,
            'status' => $this->status ?? null,
            'locale' => $user->locale?->value,
            'theme' => $user->theme?->value,
            'roles' => $this->getRoleNames() ?? null,
            'stores' => StoreResource::collection($this->whenLoaded('stores')),
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function getRoleNames(): ?array
    {
        if (empty($this->roles)) {
            return null;
        }

        return $this->roles->pluck('name')->toArray();
    }
}

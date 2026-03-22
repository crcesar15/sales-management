<?php

declare(strict_types=1);

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RoleResource extends JsonResource
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
            'permissions' => $this->whenLoaded('permissions', function () {
                return ! empty($this->permissions) ? $this->permissions->pluck('name') : [];
            }),
            'created_at' => ! empty($this->created_at) ? $this->created_at->toISOString() : null,
            'updated_at' => ! empty($this->updated_at) ? $this->updated_at->toISOString() : null,
        ];
    }
}

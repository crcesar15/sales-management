<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($user) use ($request): array {
                $collect = [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];

                if (in_array('roles', explode(',', (string) $request->input('include', '')))) {
                    $collect['roles'] = $user->roles->map(fn ($role): array => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ]);
                }

                return $collect;
            }),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (isset($this->subject_type)) {
            $subjectType = class_basename($this->subject_type);
        } else {
            $subjectType = null;
        }

        if (isset($this->causer)) {
            $causerId = $this->causer->id;
            $causerFullName = $this->causer->full_name;
        } else {
            $causerId = null;
            $causerFullName = null;
        }

        if (isset($this->created_at)) {
            $createdAt = $this->created_at->format('Y-m-d H:i');
        } else {
            $createdAt = null;
        }

        return [
            'id' => $this->id ?? null,
            'log_name' => $this->log_name ?? null,
            'description' => $this->description ?? null,
            'event' => $this->event ?? null,
            'subject_type' => $subjectType,
            'subject_id' => $this->subject_id ?? null,
            'properties' => [
                'old' => $this->properties['old'] ?? null,
                'attributes' => $this->properties['attributes'] ?? null,
            ],
            'causer' => $this->whenLoaded('causer', fn () => [
                'id' => $causerId,
                'full_name' => $causerFullName,
            ]),
            'created_at' => $createdAt,
        ];
    }
}

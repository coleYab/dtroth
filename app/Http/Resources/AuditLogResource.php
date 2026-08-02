<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
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
            'action' => $this->action,
            'description' => $this->description,
            'app_id' => $this->app_id,
            'app_name' => $this->whenLoaded('app', fn () => $this->app?->name),
            'actor' => $this->whenLoaded('user', fn () => $this->user->name),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

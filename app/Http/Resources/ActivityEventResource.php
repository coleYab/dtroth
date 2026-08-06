<?php

namespace App\Http\Resources;

use App\Models\ActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityEvent
 */
class ActivityEventResource extends JsonResource
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
            'type' => $this->type->value,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'app' => $this->whenLoaded('app', fn () => [
                'id' => $this->app->id,
                'name' => $this->app->name,
            ]),
        ];
    }
}

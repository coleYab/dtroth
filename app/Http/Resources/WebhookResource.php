<?php

namespace App\Http\Resources;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Webhook
 */
class WebhookResource extends JsonResource
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
            'url' => $this->url,
            'description' => $this->description,
            'secret_preview' => $this->secret_preview,
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'name' => $event->name,
                'label' => $event->label,
            ])),
            'last_triggered_at' => $this->last_triggered_at?->toISOString(),
            'is_failing' => ($this->recent_failure_count ?? 0) > 0,
            'recent_failure_count' => $this->recent_failure_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

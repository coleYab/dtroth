<?php

namespace App\Http\Resources;

use App\Models\Consent;
use App\Models\ConsentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Consent
 */
class ConsentResource extends JsonResource
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
            'subject_identifier' => $this->subject_identifier,
            'query_type' => $this->query_type?->value,
            'query_value' => $this->query_value,
            'age_query' => $this->ageQuery(),
            'result' => $this->result,
            'status' => $this->isRevoked()
                ? 'revoked'
                : ($this->isExpired() ? 'expired' : 'active'),
            'expires_at' => $this->expires_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'events' => $this->whenLoaded('events', fn () => collect($this->events)->map(
                fn (ConsentEvent $event) => (new ConsentEventResource($event))->resolve(),
            )->values()->all()),
        ];
    }
}

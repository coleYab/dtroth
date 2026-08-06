<?php

namespace App\Http\Resources;

use App\Models\ConsentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConsentEvent
 */
class ConsentEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

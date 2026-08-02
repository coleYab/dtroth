<?php

namespace App\Http\Resources;

use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin App
 */
class AppResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'status' => $this->status->value,
            'redirect_uris' => $this->redirect_uris ?? [],
            'allowed_origins' => $this->allowed_origins ?? [],
            'environments' => $this->whenLoaded('environments', fn () => $this->environments
                ->map(fn ($environment) => [
                    'id' => $environment->id,
                    'name' => $environment->name->value,
                    'is_enabled' => $environment->is_enabled,
                    'is_live' => $environment->is_live,
                    'redirect_uris' => $environment->redirect_uris ?? $this->redirect_uris ?? [],
                    'allowed_origins' => $environment->allowed_origins ?? $this->allowed_origins ?? [],
                ])
                ->values()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

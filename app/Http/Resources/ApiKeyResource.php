<?php

namespace App\Http\Resources;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiKey
 */
class ApiKeyResource extends JsonResource
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
            'public_key' => $this->public_key,
            'secret_preview' => $this->secret_preview,
            'environment' => $this->environment?->name->value ?? 'shared',
            'expires_at' => $this->expires_at?->toISOString(),
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'is_revoked' => $this->isRevoked(),
            'revoked_at' => $this->revoked_at?->toISOString(),
        ];
    }
}

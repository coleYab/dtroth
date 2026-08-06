<?php

namespace App\Http\Resources;

use App\Models\ApiCall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiCall
 */
class ApiCallResource extends JsonResource
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
            'method' => $this->method,
            'path' => $this->path,
            'query_type' => $this->query_type?->value,
            'query_value' => $this->query_value,
            'age_query' => $this->ageQuery(),
            'result' => $this->result,
            'status_code' => $this->status_code,
            'response_time_ms' => $this->response_time_ms,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

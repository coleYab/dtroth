<?php

namespace App\Models;

use App\Enums\AgeQueryType;
use Database\Factories\ApiCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string $method
 * @property string $path
 * @property AgeQueryType|null $query_type
 * @property int|null $query_value
 * @property bool|null $result
 * @property int $status_code
 * @property int|null $response_time_ms
 * @property array<string, mixed>|null $request_payload
 * @property array<string, mixed>|null $response_payload
 * @property Carbon $created_at
 * @property-read App $app
 */
#[Fillable(['app_id', 'method', 'path', 'query_type', 'query_value', 'result', 'status_code', 'response_time_ms', 'request_payload', 'response_payload', 'created_at'])]
class ApiCall extends Model
{
    /** @use HasFactory<ApiCallFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query_type' => AgeQueryType::class,
            'query_value' => 'integer',
            'result' => 'boolean',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The readable age query, e.g. "18+".
     */
    public function ageQuery(): ?string
    {
        if ($this->query_type === null || $this->query_value === null) {
            return null;
        }

        return $this->query_type->describe($this->query_value);
    }

    /**
     * Whether the call succeeded (2xx status code).
     */
    public function succeeded(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

<?php

namespace App\Models;

use App\Enums\AgeQueryType;
use Database\Factories\VerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property int|null $app_environment_id
 * @property string|null $token
 * @property string|null $query_id
 * @property Carbon|null $expires_at
 * @property Carbon|null $completed_at
 * @property string|null $subject_identifier
 * @property string|null $callback_url
 * @property string $status
 * @property AgeQueryType|null $query_type
 * @property int|null $query_value
 * @property bool|null $result
 * @property Carbon $created_at
 * @property-read App $app
 * @property-read AppEnvironment|null $environment
 */
#[Fillable([
    'app_id',
    'app_environment_id',
    'token',
    'query_id',
    'expires_at',
    'completed_at',
    'subject_identifier',
    'callback_url',
    'status',
    'query_type',
    'query_value',
    'result',
    'created_at',
])]
class Verification extends Model
{
    /** @use HasFactory<VerificationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

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
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /** @phpstan-return BelongsTo<App, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /** @phpstan-return BelongsTo<AppEnvironment, $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(AppEnvironment::class, 'app_environment_id');
    }
}

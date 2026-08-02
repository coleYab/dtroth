<?php

namespace App\Models;

use App\Enums\AgeQueryType;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property string $subject_identifier
 * @property AgeQueryType|null $query_type
 * @property int|null $query_value
 * @property bool|null $result
 * @property string $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 * @property-read ConsentEvent[] $events
 */
#[Fillable(['app_id', 'subject_identifier', 'query_type', 'query_value', 'result', 'status', 'expires_at', 'revoked_at'])]
class Consent extends Model
{
    /** @use HasFactory<ConsentFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

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
            'revoked_at' => 'datetime',
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
     * Whether the consent is currently revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Whether the consent has naturally expired.
     */
    public function isExpired(): bool
    {
        return ! $this->isRevoked() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Scope the query to active consents.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ConsentEvent::class)->orderByDesc('created_at');
    }
}

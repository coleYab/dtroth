<?php

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $app_id
 * @property int|null $app_environment_id
 * @property string $name
 * @property string $public_key
 * @property string|null $secret_hash
 * @property string|null $secret_preview
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read App $app
 * @property-read AppEnvironment|null $environment
 */
#[Fillable(['app_id', 'app_environment_id', 'name', 'public_key', 'secret_hash', 'secret_preview', 'expires_at', 'last_used_at', 'revoked_at'])]
#[Hidden(['secret_hash'])]
class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Determine whether the key has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
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

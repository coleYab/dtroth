<?php

namespace App\Models;

use App\Enums\AppStatus;
use Database\Factories\AppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $image
 * @property AppStatus $status
 * @property array<int, string>|null $redirect_uris
 * @property array<int, string>|null $allowed_origins
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read AppEnvironment[] $environments
 * @property-read ApiKey[] $keys
 * @property-read ApiCall[] $calls
 * @property-read Verification[] $verifications
 * @property-read Consent[] $consents
 * @property-read ActivityEvent[] $events
 * @property-read AgeQueryPreset[] $ageQueryPresets
 * @property-read VerificationSetting|null $verificationSetting
 * @property-read VerificationFlow|null $verificationFlow
 * @property-read Webhook[] $webhooks
 * @property-read Consent[] $consents
 */
#[Fillable(['user_id', 'name', 'image', 'status', 'redirect_uris', 'allowed_origins'])]
class App extends Model
{
    /** @use HasFactory<AppFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppStatus::class,
            'redirect_uris' => 'array',
            'allowed_origins' => 'array',
        ];
    }

    /**
     * Scope the query to apps owned by the given user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(AppEnvironment::class);
    }

    public function keys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(ApiCall::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ActivityEvent::class);
    }

    public function ageQueryPresets(): BelongsToMany
    {
        return $this->belongsToMany(AgeQueryPreset::class, 'age_query_preset_app', 'app_id', 'preset_id')
            ->withTimestamps();
    }

    public function verificationSetting(): HasOne
    {
        return $this->hasOne(VerificationSetting::class);
    }

    public function verificationFlow(): HasOne
    {
        return $this->hasOne(VerificationFlow::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}

<?php

namespace App\Models;

use App\Enums\AppStatus;
use Database\Factories\AppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property int|null $team_id
 * @property string $name
 * @property string|null $image
 * @property AppStatus $status
 * @property array<int, string>|null $redirect_uris
 * @property array<int, string>|null $allowed_origins
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Team|null $team
 * @property-read Collection<int, AppEnvironment> $environments
 * @property-read Collection<int, ApiKey> $keys
 * @property-read Collection<int, ApiCall> $calls
 * @property-read Collection<int, Verification> $verifications
 * @property-read Collection<int, Consent> $consents
 * @property-read Collection<int, ActivityEvent> $events
 * @property-read Collection<int, AgeQueryPreset> $ageQueryPresets
 * @property-read VerificationSetting|null $verificationSetting
 * @property-read VerificationFlow|null $verificationFlow
 * @property-read Collection<int, Webhook> $webhooks
 */
#[Fillable(['user_id', 'team_id', 'name', 'image', 'status', 'redirect_uris', 'allowed_origins'])]
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
     * Scope the query to apps accessible by the given user through a team
     * membership (the owner is a member of their own team).
     *
     * @param  Builder<App>  $query
     * @return Builder<App>
     */
    public function scopeForMember(Builder $query, User $user): Builder
    {
        return $query->whereExists(function ($subquery) use ($user) {
            $subquery->selectRaw('1')
                ->from('team_members')
                ->whereColumn('team_members.team_id', 'apps.team_id')
                ->where('team_members.user_id', $user->id)
                ->whereNotNull('team_members.accepted_at');
        });
    }

    /**
     * Scope the query to apps owned by the given user.
     *
     * @param  Builder<App>  $query
     * @return Builder<App>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /** @phpstan-return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @phpstan-return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @phpstan-return HasMany<AppEnvironment, $this> */
    public function environments(): HasMany
    {
        return $this->hasMany(AppEnvironment::class);
    }

    /** @phpstan-return HasMany<ApiKey, $this> */
    public function keys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /** @phpstan-return HasMany<ApiCall, $this> */
    public function calls(): HasMany
    {
        return $this->hasMany(ApiCall::class);
    }

    /** @phpstan-return HasMany<Verification, $this> */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /** @phpstan-return HasMany<Consent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /** @phpstan-return HasMany<ActivityEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ActivityEvent::class);
    }

    /** @phpstan-return BelongsToMany<AgeQueryPreset, $this> */
    public function ageQueryPresets(): BelongsToMany
    {
        return $this->belongsToMany(AgeQueryPreset::class, 'age_query_preset_app', 'app_id', 'preset_id')
            ->withTimestamps();
    }

    /** @phpstan-return HasOne<VerificationSetting, $this> */
    public function verificationSetting(): HasOne
    {
        return $this->hasOne(VerificationSetting::class);
    }

    /** @phpstan-return HasOne<VerificationFlow, $this> */
    public function verificationFlow(): HasOne
    {
        return $this->hasOne(VerificationFlow::class);
    }

    /** @phpstan-return HasMany<Webhook, $this> */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}

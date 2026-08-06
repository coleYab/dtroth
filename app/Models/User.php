<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property array<string, bool>|null $notification_preferences
 * @property-read Team|null $team
 * @property-read Team|null $subscriptionTeam
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'notification_preferences'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get the developer applications owned by the user.
     *
     * @phpstan-return HasMany<App, $this>
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /**
     * Get the team owned by the user.
     *
     * @phpstan-return HasOne<Team, $this>
     */
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'owner_user_id');
    }

    /**
     * Get the teams the user is a member of.
     *
     * @phpstan-return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot(['role', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * Get the team the user belongs to for portal purposes: the team they own,
     * falling back to their first accepted membership.
     */
    public function currentTeam(): ?Team
    {
        if ($this->team !== null) {
            return $this->team;
        }

        /** @var Team|null $team */
        $team = $this->teams()
            ->wherePivotNotNull('accepted_at')
            ->first();

        return $team;
    }

    /**
     * Whether the user has an email notification preference enabled.
     */
    public function notificationPreference(string $key): bool
    {
        return $this->notification_preferences[$key] ?? true;
    }

    /**
     * Get the user's billing subscription.
     *
     * @phpstan-return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the invoices issued to the user.
     *
     * @phpstan-return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

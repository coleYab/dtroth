<?php

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_user_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Collection<int, TeamMember> $memberships
 * @property-read Collection<int, App> $apps
 * @property-read Collection<int, Invitation> $invitations
 */
#[Fillable(['owner_user_id', 'name', 'slug'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /** @phpstan-return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * The accepted members of the team, including the owner.
     *
     * @phpstan-return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot(['role', 'invited_by', 'accepted_at'])
            ->withTimestamps();
    }

    /**
     * The raw membership rows (accepted members plus pending invites).
     *
     * @phpstan-return HasMany<TeamMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** @phpstan-return HasMany<App, $this> */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /** @phpstan-return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * The count of accepted members, owner included.
     */
    public function memberCount(): int
    {
        return $this->memberships()->whereNotNull('accepted_at')->count();
    }

    /**
     * Whether the given user is an accepted member of the team.
     */
    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->whereNotNull('accepted_at')->exists();
    }

    /**
     * The role of the given user within the team, or null when not a member.
     */
    public function roleFor(User $user): ?TeamRole
    {
        if ($this->owner_user_id === $user->id) {
            return TeamRole::Owner;
        }

        /** @var TeamMember|null $membership */
        $membership = $this->memberships()->where('user_id', $user->id)->first();

        return $membership?->role;
    }
}

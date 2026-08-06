<?php

namespace App\Services;

use App\Enums\TeamRole;
use App\Models\App;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;

class TeamService
{
    /**
     * Ensure the user has a personal team and return it, creating it (with
     * the owner membership row) on first use.
     */
    public static function ensurePersonalTeam(User $user): Team
    {
        $team = Team::query()->where('owner_user_id', $user->id)->first();

        if ($team === null) {
            $team = Team::create([
                'owner_user_id' => $user->id,
                'name' => $user->name."'s Team",
                'slug' => self::uniqueSlug($user->name),
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
                'accepted_at' => now(),
            ]);
        }

        return $team;
    }

    /**
     * The team the user belongs to for portal purposes.
     */
    public static function teamForUser(User $user): ?Team
    {
        return $user->currentTeam();
    }

    /**
     * Whether the user is an accepted member of the app's team.
     */
    public static function isMember(User $user, App $app): bool
    {
        return $app->team !== null
            && $app->team->roleFor($user) !== null;
    }

    /**
     * The role of the user within the app's team, or null when not a member.
     */
    public static function roleFor(User $user, App $app): ?TeamRole
    {
        if ($app->team === null) {
            return null;
        }

        return $app->team->roleFor($user);
    }

    /**
     * Abort with 403 unless the user's team role permits the given action.
     */
    public static function authorize(User $user, App $app, string $permission): void
    {
        $role = self::roleFor($user, $app);

        if ($role === null || ! self::roleAllows($role, $permission)) {
            abort(403, 'You do not have permission to do this.');
        }
    }

    /**
     * Whether the user may create new apps (owner or developer of their team).
     */
    public static function canCreateApps(User $user): bool
    {
        $team = self::teamForUser($user);

        if ($team === null) {
            return true;
        }

        $role = $team->roleFor($user);

        return $role === TeamRole::Owner || $role === TeamRole::Developer;
    }

    /**
     * Resolve an app the user can access, aborting with 404 otherwise.
     */
    public static function appForMember(User $user, int $appId): App
    {
        /** @var App $app */
        $app = App::query()
            ->forMember($user)
            ->with('environments')
            ->findOrFail($appId);

        return $app;
    }

    /**
     * The list of permissions the user holds for the app's team, used by the
     * frontend to show or hide controls.
     *
     * @return array<int, string>
     */
    public static function permissionsFor(User $user, App $app): array
    {
        $role = self::roleFor($user, $app) ?? TeamRole::Viewer;

        return match ($role) {
            TeamRole::Owner => [
                'view',
                'manage_app',
                'manage_keys',
                'manage_secrets',
                'manage_webhooks',
                'delete_app',
            ],
            TeamRole::Developer => [
                'view',
                'manage_app',
                'manage_keys',
                'manage_webhooks',
            ],
            TeamRole::Viewer => ['view'],
        };
    }

    /**
     * The accepted team members with at least the given role, used for
     * notification recipients.
     *
     * @return EloquentCollection<int, User>
     */
    public static function recipientsForApp(App $app, TeamRole $minimumRole = TeamRole::Developer): EloquentCollection
    {
        if ($app->team === null) {
            return new EloquentCollection;
        }

        $roles = collect(TeamRole::cases())
            ->filter(fn (TeamRole $role) => $role->atLeast($minimumRole))
            ->map(fn (TeamRole $role) => $role->value);

        return User::query()
            ->whereIn('id', TeamMember::query()
                ->where('team_id', $app->team->id)
                ->whereIn('role', $roles)
                ->whereNotNull('accepted_at')
                ->select('user_id'))
            ->get();
    }

    /**
     * Whether a role may perform the given permission.
     */
    private static function roleAllows(TeamRole $role, string $permission): bool
    {
        return match ($permission) {
            'view' => in_array($role, [TeamRole::Owner, TeamRole::Developer, TeamRole::Viewer], true),
            'manage_app', 'manage_keys', 'manage_webhooks' => in_array($role, [TeamRole::Owner, TeamRole::Developer], true),
            'manage_secrets', 'delete_app', 'manage_members' => $role === TeamRole::Owner,
            default => false,
        };
    }

    /**
     * Build a unique team slug from a display name.
     */
    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name).'-'.Str::lower(Str::random(6));

        while (Team::query()->where('slug', $base)->exists()) {
            $base = Str::slug($name).'-'.Str::lower(Str::random(6));
        }

        return $base;
    }
}

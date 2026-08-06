<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\TeamMember;
use App\Services\AuditLogger;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Show the current user's team page with members and invitations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $team = $user->currentTeam() ?? TeamService::ensurePersonalTeam($user);

        /** @var Collection<int, TeamMember> $memberships */
        $memberships = $team->memberships()->with('user')->orderBy('accepted_at')->get();

        $members = $memberships
            ->filter(fn (TeamMember $member) => $member->accepted_at !== null)
            ->map(fn (TeamMember $member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'role' => $member->role->value,
                'is_owner' => $member->user_id === $team->owner_user_id,
                'joined_at' => $member->accepted_at?->toISOString(),
            ])
            ->values();

        /** @var Collection<int, Invitation> $invitationRows */
        $invitationRows = $team->invitations()
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();

        $invitations = $invitationRows
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at->toISOString(),
                'is_expired' => $invitation->isExpired(),
            ]);

        $role = $team->roleFor($user);

        return Inertia::render('teams/index', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner_name' => $team->owner->name,
                'member_count' => $team->memberCount(),
            ],
            'role' => ($role ?? TeamRole::Viewer)->value,
            'is_owner' => $role === TeamRole::Owner,
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Change a member's role.
     */
    public function updateRole(Request $request, TeamMember $member): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam();

        abort_unless($team !== null, 403);
        abort_unless($team->roleFor($user) === TeamRole::Owner, 403);

        /** @var TeamMember $member */
        $member = $team->memberships()->findOrFail($member->id);

        abort_if($member->user_id === $team->owner_user_id, 422, 'The team owner cannot be demoted.');

        $validated = $request->validate([
            'role' => ['required', Rule::enum(TeamRole::class)],
        ]);

        $newRole = TeamRole::from($validated['role']);

        abort_if($newRole === TeamRole::Owner, 422, 'Members cannot be promoted to owner.');

        $member->update(['role' => $newRole]);

        AuditLogger::log($user, AuditLog::ACTION_TEAM_ROLE_CHANGED, "Changed {$member->user->name}'s role to {$newRole->label()} on {$team->name}.");

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role updated.'),
        ]);

        return back();
    }

    /**
     * Remove a member from the team.
     */
    public function remove(Request $request, TeamMember $member): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam();

        abort_unless($team !== null, 403);
        abort_unless($team->roleFor($user) === TeamRole::Owner, 403);

        /** @var TeamMember $member */
        $member = $team->memberships()->findOrFail($member->id);

        abort_if($member->user_id === $team->owner_user_id, 422, 'The team owner cannot be removed.');

        $name = $member->user->name;

        $member->delete();

        AuditLogger::log($user, AuditLog::ACTION_TEAM_MEMBER_REMOVED, "Removed {$name} from {$team->name}.");

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member removed.'),
        ]);

        return back();
    }
}

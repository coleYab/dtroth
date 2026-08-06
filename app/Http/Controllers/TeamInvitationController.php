<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Mail\TeamInvitation;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    /**
     * Invite someone to the current user's team by email.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam() ?? TeamService::ensurePersonalTeam($user);

        abort_unless($team->roleFor($user) === TeamRole::Owner, 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(TeamRole::class)],
        ]);

        $role = TeamRole::from($validated['role']);

        abort_if($role === TeamRole::Owner, 422, 'Invitations cannot grant the owner role.');

        abort_if(
            strtolower($validated['email']) === strtolower($user->email),
            422,
            'You are already a member of this team.',
        );

        abort_if(
            $team->memberships()->whereHas('user', fn ($query) => $query->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])]))->exists(),
            422,
            'That user is already a member of this team.',
        );

        $invitation = Invitation::updateOrCreate(
            ['team_id' => $team->id, 'email' => strtolower($validated['email'])],
            [
                'role' => $role,
                'token' => Str::random(40),
                'expires_at' => now()->addDays(Invitation::VALID_DAYS),
                'accepted_at' => null,
            ],
        );

        Mail::to($invitation->email)->queue(new TeamInvitation($invitation));

        AuditLogger::log($user, AuditLog::ACTION_TEAM_INVITED, "Invited {$invitation->email} to {$team->name} as {$role->label()}.");

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation sent to :email.', ['email' => $invitation->email]),
        ]);

        return back();
    }

    /**
     * Accept an invitation. Guests are routed to registration, where the
     * invitation is applied automatically after account creation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if ($invitation === null || $invitation->isExpired()) {
            abort(404, 'This invitation is invalid or has expired.');
        }

        if ($request->user() === null) {
            $request->session()->put('pending_invitation', $invitation->token);
            $request->session()->flash('status', 'Create an account to join the team.');

            return redirect()->route('register');
        }

        $this->applyInvitation($request->user(), $invitation);

        return redirect()->route('teams.index');
    }

    /**
     * Resend a pending invitation.
     */
    public function resend(Request $request, Invitation $invitation): RedirectResponse
    {
        $team = $this->ownedInvitationTeam($request, $invitation);

        abort_if($invitation->accepted_at !== null, 422, 'This invitation has already been accepted.');

        $invitation->update([
            'expires_at' => now()->addDays(Invitation::VALID_DAYS),
        ]);

        Mail::to($invitation->email)->queue(new TeamInvitation($invitation->refresh()));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation resent to :email.', ['email' => $invitation->email]),
        ]);

        return back();
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancel(Request $request, Invitation $invitation): RedirectResponse
    {
        $team = $this->ownedInvitationTeam($request, $invitation);

        $email = $invitation->email;

        $invitation->delete();

        AuditLogger::log($request->user(), AuditLog::ACTION_TEAM_INVITE_CANCELLED, "Cancelled the invitation for {$email} on {$team->name}.");

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation cancelled.'),
        ]);

        return back();
    }

    /**
     * Resolve the invitation's team, scoped to teams the user owns.
     */
    private function ownedInvitationTeam(Request $request, Invitation $invitation): Team
    {
        $team = $invitation->team;

        abort_unless($request->user()->team?->id === $invitation->team_id, 403);

        return $team;
    }

    /**
     * Apply an invitation to a user, creating the membership row.
     */
    private function applyInvitation(User $user, Invitation $invitation): void
    {
        $team = $invitation->team;

        TeamMember::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $user->id],
            [
                'role' => $invitation->role,
                'invited_by' => $team->owner_user_id,
                'accepted_at' => now(),
            ],
        );

        $invitation->update(['accepted_at' => now()]);
    }
}

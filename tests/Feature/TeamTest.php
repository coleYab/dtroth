<?php

use App\Enums\TeamRole;
use App\Mail\TeamInvitation;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->team = Team::factory()->withOwner()->create([
        'owner_user_id' => $this->owner->id,
    ]);
    $this->appModel = App::factory()->withEnvironments()->create([
        'user_id' => $this->owner->id,
    ]);
});

test('the teams page lists the team, its members and pending invitations', function () {
    $member = makeTeamMember($this->team, TeamRole::Developer);
    $invitation = Invitation::factory()->create(['team_id' => $this->team->id]);

    actingAs($this->owner);

    $this->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teams/index')
            ->where('team.name', $this->team->name)
            ->where('team.member_count', 2)
            ->where('role', TeamRole::Owner->value)
            ->where('is_owner', true)
            ->has('members', 2)
            ->where('members.1.name', $member->name)
            ->where('members.1.role', TeamRole::Developer->value)
            ->where('members.1.is_owner', false)
            ->has('invitations', 1)
            ->where('invitations.0.email', $invitation->email));
});

test('pending memberships are not listed on the teams page', function () {
    TeamMember::factory()->pending()->create([
        'team_id' => $this->team->id,
        'user_id' => User::factory(),
    ]);

    actingAs($this->owner);

    $this->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('members', 1));
});

test('developers can view and manage apps but cannot delete them or manage secrets', function () {
    $developer = makeTeamMember($this->team, TeamRole::Developer);
    $webhook = Webhook::factory()->create(['app_id' => $this->appModel->id]);

    actingAs($developer);

    $this->get(route('apps.show', $this->appModel))->assertOk();
    $this->patch(route('apps.update', $this->appModel), [
        'name' => 'Renamed app',
        'redirect_uris' => ['https://example.com/callback'],
    ])->assertStatus(302);
    $this->post(route('apps.keys.store', $this->appModel), [
        'environment' => 'sandbox',
        'name' => 'Deploy key',
    ])->assertOk();
    $this->get(route('apps.webhooks.create', $this->appModel))->assertOk();
    $this->post(route('apps.webhooks.regenerate-secret', [$this->appModel, $webhook]))->assertForbidden();
    $this->delete(route('apps.destroy', $this->appModel))->assertForbidden();
});

test('viewers can read app pages but every management action is forbidden', function () {
    $viewer = makeTeamMember($this->team, TeamRole::Viewer);

    actingAs($viewer);

    $this->get(route('apps.show', $this->appModel))->assertOk();
    $this->get(route('apps.webhooks.index', $this->appModel))->assertOk();
    $this->patch(route('apps.update', $this->appModel), [
        'name' => 'Renamed app',
        'redirect_uris' => ['https://example.com/callback'],
    ])->assertForbidden();
    $this->post(route('apps.keys.store', $this->appModel), [
        'environment' => 'sandbox',
    ])->assertForbidden();
    $this->get(route('apps.webhooks.create', $this->appModel))->assertForbidden();
    $this->delete(route('apps.destroy', $this->appModel))->assertForbidden();
});

test('users outside the team cannot see the app', function () {
    $outsider = User::factory()->create();

    actingAs($outsider);

    $this->get(route('apps.show', $this->appModel))->assertNotFound();
    $this->patch(route('apps.update', $this->appModel), [
        'name' => 'Hijack',
        'redirect_uris' => ['https://example.com/callback'],
    ])->assertNotFound();
});

test('the owner can change a member role but not their own', function () {
    $member = makeTeamMember($this->team, TeamRole::Developer);
    $membership = $this->team->memberships()->where('user_id', $member->id)->first();
    $ownerMembership = $this->team->memberships()->where('user_id', $this->owner->id)->first();

    actingAs($this->owner);

    $this->patch(route('teams.members.update', $membership), [
        'role' => TeamRole::Viewer->value,
    ])->assertStatus(302);

    expect($membership->fresh()->role)->toBe(TeamRole::Viewer)
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_TEAM_ROLE_CHANGED)->count())->toBe(1);

    $this->patch(route('teams.members.update', $ownerMembership), [
        'role' => TeamRole::Viewer->value,
    ])->assertStatus(422);

    $this->patch(route('teams.members.update', $membership), [
        'role' => TeamRole::Owner->value,
    ])->assertStatus(422);
});

test('only the owner can manage members', function () {
    $developer = makeTeamMember($this->team, TeamRole::Developer);
    $viewer = makeTeamMember($this->team, TeamRole::Viewer);
    $membership = $this->team->memberships()->where('user_id', $viewer->id)->first();

    actingAs($developer);

    $this->patch(route('teams.members.update', $membership), [
        'role' => TeamRole::Developer->value,
    ])->assertForbidden();
    $this->delete(route('teams.members.destroy', $membership))->assertForbidden();
    $this->post(route('teams.invitations.store'), [
        'email' => 'nobody@example.com',
        'role' => TeamRole::Developer->value,
    ])->assertForbidden();

    expect($developer->fresh())->not->toBeNull();
});

test('members from another team cannot be targeted through the members routes', function () {
    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->withOwner()->create(['owner_user_id' => $otherOwner->id]);
    $otherMember = makeTeamMember($otherTeam, TeamRole::Developer);
    $foreignMembership = $otherTeam->memberships()->where('user_id', $otherMember->id)->first();

    actingAs($this->owner);

    $this->patch(route('teams.members.update', $foreignMembership), [
        'role' => TeamRole::Viewer->value,
    ])->assertNotFound();
    $this->delete(route('teams.members.destroy', $foreignMembership))->assertNotFound();
});

test('the owner can remove a member but not themselves', function () {
    $member = makeTeamMember($this->team, TeamRole::Viewer);
    $membership = $this->team->memberships()->where('user_id', $member->id)->first();
    $ownerMembership = $this->team->memberships()->where('user_id', $this->owner->id)->first();

    actingAs($this->owner);

    $this->delete(route('teams.members.destroy', $ownerMembership))->assertStatus(422);

    $this->delete(route('teams.members.destroy', $membership))->assertStatus(302);

    expect($membership->fresh())->toBeNull()
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_TEAM_MEMBER_REMOVED)->count())->toBe(1);
});

test('removing a member revokes their app access immediately', function () {
    $viewer = makeTeamMember($this->team, TeamRole::Viewer);
    $membership = $this->team->memberships()->where('user_id', $viewer->id)->first();

    actingAs($viewer);
    $this->get(route('apps.show', $this->appModel))->assertOk();

    actingAs($this->owner);
    $this->delete(route('teams.members.destroy', $membership))->assertStatus(302);

    actingAs($viewer);
    $this->get(route('apps.show', $this->appModel))->assertNotFound();
});

test('the owner can invite someone by email and the invitation is queued', function () {
    Mail::fake();

    actingAs($this->owner);

    $this->post(route('teams.invitations.store'), [
        'email' => 'invitee@example.com',
        'role' => TeamRole::Developer->value,
    ])->assertStatus(302);

    $invitation = Invitation::query()->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('invitee@example.com')
        ->and($invitation->role)->toBe(TeamRole::Developer)
        ->and($invitation->accepted_at)->toBeNull()
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_TEAM_INVITED)->count())->toBe(1);

    Mail::assertQueued(TeamInvitation::class);
});

test('invitations reject self, existing members and the owner role', function () {
    $member = makeTeamMember($this->team, TeamRole::Developer);

    actingAs($this->owner);

    $this->post(route('teams.invitations.store'), [
        'email' => $this->owner->email,
        'role' => TeamRole::Developer->value,
    ])->assertStatus(422);

    $this->post(route('teams.invitations.store'), [
        'email' => $member->email,
        'role' => TeamRole::Developer->value,
    ])->assertStatus(422);

    $this->post(route('teams.invitations.store'), [
        'email' => 'invitee@example.com',
        'role' => TeamRole::Owner->value,
    ])->assertStatus(422);
});

test('inviting the same email again refreshes the existing invitation', function () {
    Mail::fake();

    $invitation = Invitation::factory()->create([
        'team_id' => $this->team->id,
        'email' => 'again@example.com',
        'expires_at' => now()->subDay(),
    ]);

    actingAs($this->owner);

    $this->post(route('teams.invitations.store'), [
        'email' => 'again@example.com',
        'role' => TeamRole::Viewer->value,
    ])->assertStatus(302);

    expect(Invitation::count())->toBe(1)
        ->and($invitation->fresh()->role)->toBe(TeamRole::Viewer)
        ->and($invitation->fresh()->expires_at->isFuture())->toBeTrue();
});

test('guests are routed to registration with the invitation pending', function () {
    $invitation = Invitation::factory()->create(['team_id' => $this->team->id]);

    $this->get(route('teams.invitations.accept', $invitation->token))
        ->assertRedirect(route('register'))
        ->assertSessionHas('pending_invitation', $invitation->token);
});

test('an existing user accepts an invitation and becomes a member', function () {
    $invitee = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'team_id' => $this->team->id,
        'email' => $invitee->email,
    ]);

    actingAs($invitee);

    $this->get(route('teams.invitations.accept', $invitation->token))
        ->assertRedirect(route('teams.index'));

    $membership = $this->team->memberships()->where('user_id', $invitee->id)->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe($invitation->role)
        ->and($membership->invited_by)->toBe($this->owner->id)
        ->and($membership->accepted_at)->not->toBeNull()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();

    actingAs($invitee);
    $this->get(route('apps.show', $this->appModel))->assertOk();
});

test('registering with a pending invitation email joins the team automatically', function () {
    $invitation = Invitation::factory()->create([
        'team_id' => $this->team->id,
        'email' => 'new@example.com',
    ]);

    $this->post(route('register.store'), [
        'name' => 'New Person',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'new@example.com')->first();

    expect($user->currentTeam()->id)->toBe($this->team->id)
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('expired or already-used invitations are rejected', function () {
    $expired = Invitation::factory()->expired()->create(['team_id' => $this->team->id]);
    $used = Invitation::factory()->accepted()->create(['team_id' => $this->team->id]);

    $this->get(route('teams.invitations.accept', $expired->token))->assertNotFound();
    $this->get(route('teams.invitations.accept', $used->token))->assertNotFound();
    $this->get(route('teams.invitations.accept', 'bogus-token'))->assertNotFound();
});

test('only the owner can resend or cancel invitations', function () {
    Mail::fake();

    $developer = makeTeamMember($this->team, TeamRole::Developer);
    $invitation = Invitation::factory()->create([
        'team_id' => $this->team->id,
        'expires_at' => now()->subDay(),
    ]);

    actingAs($developer);

    $this->post(route('teams.invitations.resend', $invitation))->assertForbidden();
    $this->delete(route('teams.invitations.destroy', $invitation))->assertForbidden();

    actingAs($this->owner);

    $this->post(route('teams.invitations.resend', $invitation))->assertStatus(302);

    expect($invitation->fresh()->expires_at->isFuture())->toBeTrue();

    Mail::assertQueued(TeamInvitation::class);

    $this->delete(route('teams.invitations.destroy', $invitation))->assertStatus(302);

    expect($invitation->fresh())->toBeNull()
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_TEAM_INVITE_CANCELLED)->count())->toBe(1);
});

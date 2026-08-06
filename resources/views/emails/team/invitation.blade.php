<x-mail::message>
# You've been invited to {{ $team->name }}

Hi,

**{{ $team->owner->name }}** has invited you to join the **{{ $team->name }}** team on {{ config('app.name') }} as a **{{ $invitation->role->label() }}**.

<x-mail::panel>
**Team:** {{ $team->name }}

**Your role:** {{ $invitation->role->label() }}

**Invited by:** {{ $team->owner->name }}
</x-mail::panel>

@if ($invitation->role->value === 'viewer')
As a viewer you can browse applications, usage, and logs, but you cannot change settings or manage API keys.
@else
As a developer you can manage applications, API keys, and webhooks within the team.
@endif

<x-mail::button :url="$acceptUrl" color="primary">
Accept invitation
</x-mail::button>

If you don't have an account yet, you'll be asked to create one — the invitation is applied automatically.

This invitation expires {{ $invitation->expires_at->toFormattedDayDateString() }}. If you weren't expecting this, you can safely ignore it.

Thanks,

The {{ config('app.name') }} Team
</x-mail::message>

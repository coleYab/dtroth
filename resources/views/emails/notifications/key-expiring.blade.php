<x-mail::message>
# API key expiring for {{ $app->name }}

Hi,

The API key **"{{ $key->name }}"** for **{{ $app->name }}** expires on **{{ $key->expires_at->toFormattedDayDateString() }}** ({{ $key->expires_at->diffForHumans() }}).

<x-mail::panel>
**Key name:** {{ $key->name }}

**Environment:** {{ $key->environment?->name->value ?? 'Default' }}

**Expires:** {{ $key->expires_at->toFormattedDayDateString() }}
</x-mail::panel>

Once a key expires, requests signed with it stop working. Generate a replacement key (or rotate) before the expiry date and update your servers.

<x-mail::button :url="$keysUrl" color="primary">
Manage API keys
</x-mail::button>

Thanks,

The Troth Team
</x-mail::message>

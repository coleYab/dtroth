<x-mail::message>
# Webhook failing: {{ $app->name }}

Hi,

We've detected failed deliveries on the webhook **{{ $webhook->url }}** over the last 24 hours.

<x-mail::panel>
**Application:** {{ $app->name }}

**Webhook endpoint:** {{ $webhook->url }}

**Last triggered:** {{ $webhook->last_triggered_at?->toDayDateTimeString() ?? 'Never' }}
</x-mail::panel>

If the endpoint is temporarily down, it may recover on its own. Otherwise check the endpoint and retry the failed deliveries once it's back.

<x-mail::button :url="$logsUrl" color="primary">
View delivery logs
</x-mail::button>

Thanks,

The Troth Team
</x-mail::message>

<x-mail::message>
# Usage at {{ $threshold }}% for {{ $app->name }}

Hi,

**{{ $app->name }}** has used **{{ number_format($usedCalls) }}** of its monthly quota of **{{ number_format($quotaCalls) }}** calls.

<x-mail::panel>
**Usage:** {{ number_format($usedCalls) }} calls

**Plan quota:** {{ number_format($quotaCalls) }} calls

**Usage level:** {{ $threshold }}%
</x-mail::panel>

@if ($threshold >= 100)
Your plan limit has been reached and further calls will incur overage charges.
@else
You're approaching your plan's monthly limit. Consider upgrading your plan or reviewing your integration to avoid overage charges.
@endif

<x-mail::button :url="$usageUrl" color="primary">
View usage
</x-mail::button>

Thanks,

The Troth Team
</x-mail::message>

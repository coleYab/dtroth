<x-mail::message>
# Payment failed for invoice {{ $invoice->number }}

Hi,

We were unable to collect payment for invoice **{{ $invoice->number }}** ({{ $invoice->formattedAmount() }}).

<x-mail::panel>
**Invoice:** {{ $invoice->number }}

**Amount:** {{ $invoice->formattedAmount() }}

**Date issued:** {{ $invoice->created_at?->toFormattedDayDateString() ?? 'N/A' }}
</x-mail::panel>

Your subscription remains active for now, but repeated failures may pause access. Update your payment method or retry the payment from the billing page.

<x-mail::button :url="$billingUrl" color="primary">
Open billing
</x-mail::button>

Thanks,

The Troth Team
</x-mail::message>

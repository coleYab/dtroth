<x-mail::message>
# Support ticket {{ $ticket->reference }} received

Hi {{ $ticket->user->name }},

We've received your support request **"{{ $ticket->subject }}"** and a member of our team will get back to you within one business day.

**Your reference number is: {{ $ticket->reference }}** — please keep it handy if you need to follow up.

<x-mail::panel>
**Category:** {{ $ticket->category->label() }}

**Description:**

{{ $ticket->description }}
</x-mail::panel>

If you have additional information to share, simply reply to this email or open a new ticket from the support page.

Thanks,

The Troth Support Team
</x-mail::message>

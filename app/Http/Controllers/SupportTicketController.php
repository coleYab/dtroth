<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketCategory;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Jobs\NotifySupportChannel;
use App\Mail\SupportTicketReceived;
use App\Models\App;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    /**
     * Show the support form and any ticket just submitted.
     */
    public function index(): Response
    {
        return Inertia::render('support', [
            'categories' => collect(SupportTicketCategory::cases())->map(fn ($category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])->values()->all(),
            'apps' => App::query()
                ->forUser(auth()->user())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a new support ticket.
     */
    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support/attachments');
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'app_id' => $validated['app_id'] ?? null,
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'status' => 'new',
            'reference' => $this->reference(),
        ]);

        Mail::to($ticket->user)->queue(new SupportTicketReceived($ticket));

        NotifySupportChannel::dispatch($ticket);

        session()->flash('ticket', [
            'reference' => $ticket->reference,
            'subject' => $ticket->subject,
            'createdAt' => $ticket->created_at->toIso8601String(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Ticket '.$ticket->reference.' submitted. We will get back to you within one business day.',
        ]);

        return redirect()->route('support.index');
    }

    /**
     * Generate a human-friendly unique reference, e.g. TKT-3F8K2M9A.
     */
    private function reference(): string
    {
        do {
            $reference = 'TKT-'.strtoupper(Str::random(8));
        } while (SupportTicket::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

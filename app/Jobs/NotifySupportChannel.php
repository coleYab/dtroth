<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifySupportChannel implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public SupportTicket $ticket) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.support.webhook_url');

        if ($webhookUrl === null || $webhookUrl === '') {
            return;
        }

        $response = Http::post($webhookUrl, [
            'text' => sprintf(
                'New support ticket %s — %s (%s) — %s',
                $this->ticket->reference,
                $this->ticket->subject,
                $this->ticket->category->label(),
                $this->ticket->user->email,
            ),
        ]);

        if ($response->failed()) {
            Log::warning('Failed to notify support channel about ticket '.$this->ticket->reference);
        }
    }
}

<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWebhookDelivery implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds to wait before retrying.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 480];

    /**
     * The maximum number of attempts before the delivery is marked failed.
     */
    public int $tries = 4;

    public function __construct(public WebhookDelivery $delivery) {}

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(24);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $delivery = $this->delivery->fresh();
        $webhook = $delivery->webhook;

        $payloadJson = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payloadJson, $webhook->secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Troth-Signature' => 'sha256='.$signature,
                'X-Troth-Event' => $delivery->event,
            ])
            ->post($webhook->url, $delivery->payload);

        $attempts = $delivery->attempts + 1;

        $delivery->update([
            'response_status' => $response->status(),
            'response_body' => mb_substr($response->body(), 0, 2000),
            'attempts' => $attempts,
        ]);

        if ($response->successful()) {
            $delivery->update([
                'status' => WebhookDeliveryStatus::Success,
                'delivered_at' => now(),
            ]);

            $webhook->update(['last_triggered_at' => now()]);

            return;
        }

        $delivery->update(['status' => WebhookDeliveryStatus::Retrying]);

        $this->release($this->backoff[$attempts] ?? 60);
    }

    /**
     * Handle a job failure, marking the delivery as failed.
     */
    public function failed(?Throwable $e): void
    {
        $this->delivery->update([
            'status' => WebhookDeliveryStatus::Failed,
        ]);
    }
}

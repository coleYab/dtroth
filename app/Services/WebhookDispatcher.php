<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\SendWebhookDelivery;
use App\Models\App;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Triggers webhook deliveries for lifecycle events. Delivery itself (signing,
 * retries, records) lives in SendWebhookDelivery — this service only decides
 * which webhooks fire and deduplicates repeat triggers.
 */
class WebhookDispatcher
{
    /**
     * Dispatch an event to every webhook of the app subscribed to it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, App $app, array $payload, ?string $idempotencyKey = null): void
    {
        $webhooks = $app->webhooks()
            ->whereHas('events', fn (Builder $query) => $query->where('name', $event))
            ->get();

        /** @var Webhook $webhook */
        foreach ($webhooks as $webhook) {
            try {
                if ($idempotencyKey !== null && $this->alreadyDelivered($webhook->id, $event, $idempotencyKey)) {
                    continue;
                }

                $delivery = WebhookDelivery::create([
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'idempotency_key' => $idempotencyKey,
                    'payload' => $payload,
                    'status' => WebhookDeliveryStatus::Pending,
                    'attempts' => 0,
                    'created_at' => now(),
                ]);

                $webhook->forceFill(['last_triggered_at' => now()])->saveQuietly();

                SendWebhookDelivery::dispatch($delivery);

                Log::info(sprintf('[webhooks] %s → %s (webhook #%d, delivery #%d)', $event, $webhook->url, $webhook->id, $delivery->id));
            } catch (\Throwable $e) {
                Log::warning(sprintf('[webhooks] dispatch failed for %s → %s: %s', $event, $webhook->url, $e->getMessage()));
            }
        }
    }

    /**
     * Whether any webhook of the app subscribes to the event.
     */
    public function hasSubscribers(string $event, App $app): bool
    {
        return $app->webhooks()
            ->whereHas('events', fn (Builder $query) => $query->where('name', $event))
            ->exists();
    }

    /**
     * Whether a delivery already exists for the same webhook, event, and key.
     */
    private function alreadyDelivered(int $webhookId, string $event, string $idempotencyKey): bool
    {
        return WebhookDelivery::query()
            ->where('webhook_id', $webhookId)
            ->where('event', $event)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();
    }
}

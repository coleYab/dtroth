<?php

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\SendWebhookDelivery;
use App\Models\App;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    foreach ([
        ['name' => WebhookEvent::VERIFICATION_COMPLETED, 'label' => 'Verification completed'],
        ['name' => WebhookEvent::VERIFICATION_EXPIRED, 'label' => 'Verification expired'],
        ['name' => WebhookEvent::CONSENT_REVOKED, 'label' => 'Consent revoked'],
    ] as $event) {
        WebhookEvent::create($event);
    }
});

test('dispatch with no subscribers creates nothing and does not throw', function () {
    $app = App::factory()->create();

    Queue::fake();

    app(WebhookDispatcher::class)->dispatch(
        WebhookEvent::VERIFICATION_COMPLETED,
        $app,
        ['event' => WebhookEvent::VERIFICATION_COMPLETED, 'result' => true],
        'query-1',
    );

    expect(WebhookDelivery::count())->toBe(0);

    Queue::assertNothingPushed();
});

test('dispatch creates a pending delivery per subscribed webhook and queues the job', function () {
    $app = App::factory()->create();

    $subscribed = Webhook::factory()->create(['app_id' => $app->id, 'url' => 'https://hooks.example.com/a']);
    $subscribed->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);

    $otherEvent = Webhook::factory()->create(['app_id' => $app->id, 'url' => 'https://hooks.example.com/b']);
    $otherEvent->events()->attach(WebhookEvent::where('name', WebhookEvent::CONSENT_REVOKED)->first()->id);

    Queue::fake();

    app(WebhookDispatcher::class)->dispatch(
        WebhookEvent::VERIFICATION_COMPLETED,
        $app,
        ['event' => WebhookEvent::VERIFICATION_COMPLETED, 'query_id' => 'query-1', 'result' => true],
        'query-1',
    );

    expect(WebhookDelivery::count())->toBe(1);

    $delivery = WebhookDelivery::first();

    expect($delivery->webhook_id)->toBe($subscribed->id)
        ->and($delivery->event)->toBe(WebhookEvent::VERIFICATION_COMPLETED)
        ->and($delivery->idempotency_key)->toBe('query-1')
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->attempts)->toBe(0)
        ->and($delivery->payload['query_id'])->toBe('query-1')
        ->and($delivery->payload['result'])->toBeTrue();

    Queue::assertPushed(SendWebhookDelivery::class, fn ($job) => $job->delivery->id === $delivery->id);

    expect($subscribed->fresh()->last_triggered_at)->not->toBeNull()
        ->and($otherEvent->fresh()->last_triggered_at)->toBeNull();
});

test('a second dispatch with the same idempotency key does not duplicate', function () {
    $app = App::factory()->create();

    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $webhook->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);

    Queue::fake();

    $dispatcher = app(WebhookDispatcher::class);

    $dispatcher->dispatch(WebhookEvent::VERIFICATION_COMPLETED, $app, ['event' => WebhookEvent::VERIFICATION_COMPLETED], 'query-1');
    $dispatcher->dispatch(WebhookEvent::VERIFICATION_COMPLETED, $app, ['event' => WebhookEvent::VERIFICATION_COMPLETED], 'query-1');

    expect(WebhookDelivery::count())->toBe(1);

    Queue::assertPushed(SendWebhookDelivery::class, 1);
});

test('the same key across different events or webhooks is not deduplicated', function () {
    $app = App::factory()->create();

    $first = Webhook::factory()->create(['app_id' => $app->id]);
    $first->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);

    $second = Webhook::factory()->create(['app_id' => $app->id]);
    $second->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);

    Queue::fake();

    app(WebhookDispatcher::class)->dispatch(WebhookEvent::VERIFICATION_COMPLETED, $app, ['event' => WebhookEvent::VERIFICATION_COMPLETED], 'query-1');

    expect(WebhookDelivery::count())->toBe(2);
});

test('hasSubscribers reports whether any webhook listens for the event', function () {
    $app = App::factory()->create();

    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $webhook->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);

    $dispatcher = app(WebhookDispatcher::class);

    expect($dispatcher->hasSubscribers(WebhookEvent::VERIFICATION_COMPLETED, $app))->toBeTrue()
        ->and($dispatcher->hasSubscribers(WebhookEvent::CONSENT_REVOKED, $app))->toBeFalse();
});

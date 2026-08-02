<?php

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\SendWebhookDelivery;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach ([
        ['name' => WebhookEvent::VERIFICATION_COMPLETED, 'label' => 'Verification completed'],
        ['name' => WebhookEvent::VERIFICATION_EXPIRED, 'label' => 'Verification expired'],
        ['name' => WebhookEvent::CONSENT_REVOKED, 'label' => 'Consent revoked'],
    ] as $event) {
        WebhookEvent::create($event);
    }
});

test('the webhooks page lists webhooks with their events', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $webhook->events()->attach(WebhookEvent::first()->id);
    actingAs($user);

    $this->get(route('apps.webhooks.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/webhooks/index')
            ->has('webhooks.data', 1)
            ->where('webhooks.data.0.events.0.name', WebhookEvent::VERIFICATION_COMPLETED));
});

test('users can create a webhook and the secret is revealed once', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->post(route('apps.webhooks.store', $app), [
        'url' => 'https://hooks.example.com/verify',
        'events' => [WebhookEvent::VERIFICATION_COMPLETED],
        'description' => 'Results hook',
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/webhooks/index')
            ->has('revealedSecret.secret'));

    $webhook = $app->webhooks()->first();

    expect($webhook)->not->toBeNull()
        ->and($webhook->secret_preview)->toContain('••••')
        ->and($webhook->events()->pluck('webhook_events.name'))->toContain(WebhookEvent::VERIFICATION_COMPLETED)
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_WEBHOOK_CHANGED)->count())->toBe(1);

    $this->get(route('apps.webhooks.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('webhooks.data.0.secret'));
});

test('webhook URLs must use https', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->post(route('apps.webhooks.store', $app), [
        'url' => 'http://insecure.example.com/hook',
        'events' => [WebhookEvent::VERIFICATION_COMPLETED],
    ])->assertSessionHasErrors('url');
});

test('at least one event is required', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->post(route('apps.webhooks.store', $app), [
        'url' => 'https://hooks.example.com/verify',
        'events' => [],
    ])->assertSessionHasErrors('events');
});

test('users can update a webhook and its events', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $webhook->events()->attach(WebhookEvent::where('name', WebhookEvent::VERIFICATION_COMPLETED)->first()->id);
    actingAs($user);

    $this->patch(route('apps.webhooks.update', [$app, $webhook]), [
        'url' => 'https://hooks.example.com/v2',
        'events' => [WebhookEvent::CONSENT_REVOKED],
        'description' => 'Updated',
    ])->assertRedirect();

    expect($webhook->fresh()->url)->toBe('https://hooks.example.com/v2')
        ->and($webhook->fresh()->events()->pluck('webhook_events.name'))->toContain(WebhookEvent::CONSENT_REVOKED)
        ->and($webhook->fresh()->events()->pluck('webhook_events.name'))->not->toContain(WebhookEvent::VERIFICATION_COMPLETED);
});

test('users can delete a webhook', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->delete(route('apps.webhooks.destroy', [$app, $webhook]))
        ->assertRedirect(route('apps.webhooks.index', $app));

    expect(Webhook::query()->find($webhook->id))->toBeNull();
});

test('users can regenerate the signing secret which is revealed once', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $oldPreview = $webhook->secret_preview;
    actingAs($user);

    $this->post(route('apps.webhooks.regenerate-secret', [$app, $webhook]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/webhooks/logs')
            ->has('revealedSecret.secret'));

    expect($webhook->fresh()->secret_preview)->not->toBe($oldPreview);
});

test('the webhook logs page lists deliveries with filters', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    WebhookDelivery::factory()->count(3)->create(['webhook_id' => $webhook->id]);
    WebhookDelivery::factory()->failed()->create(['webhook_id' => $webhook->id]);
    actingAs($user);

    $this->get(route('apps.webhooks.logs', [$app, $webhook]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/webhooks/logs')
            ->has('deliveries.data', 4));

    $this->get(route('apps.webhooks.logs', [$app, $webhook, 'status' => 'failed']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('deliveries.data', 1));
});

test('users can retry a failed delivery', function () {
    Queue::fake();

    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $delivery = WebhookDelivery::factory()->failed()->create(['webhook_id' => $webhook->id]);
    actingAs($user);

    $this->post(route('apps.webhooks.retry-delivery', [$app, $webhook, $delivery]))
        ->assertRedirect();

    Queue::assertPushed(SendWebhookDelivery::class);

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->fresh()->attempts)->toBe(0);
});

test('successful deliveries cannot be retried', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $delivery = WebhookDelivery::factory()->create(['webhook_id' => $webhook->id]);
    actingAs($user);

    $this->post(route('apps.webhooks.retry-delivery', [$app, $webhook, $delivery]))
        ->assertStatus(422);
});

test('a webhook delivery signs the payload with hmac and records success', function () {
    Http::fake([
        'https://hooks.example.com/*' => Http::response('ok', 200),
    ]);

    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id, 'url' => 'https://hooks.example.com/deliver']);
    $delivery = WebhookDelivery::factory()->create([
        'webhook_id' => $webhook->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempts' => 0,
    ]);
    actingAs($user);

    (new SendWebhookDelivery($delivery))->handle();

    Http::assertSent(function ($request) use ($delivery, $webhook) {
        $payload = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $expected = 'sha256='.hash_hmac('sha256', $payload, $webhook->secret);

        return $request->hasHeader('X-Troth-Signature', $expected)
            && $request->hasHeader('X-Troth-Event', $delivery->event);
    });

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->fresh()->attempts)->toBe(1)
        ->and($webhook->fresh()->last_triggered_at)->not->toBeNull();
});

test('a failing delivery is marked failed after retries are exhausted', function () {
    Http::fake([
        'https://hooks.example.com/*' => Http::response('error', 500),
    ]);

    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id, 'url' => 'https://hooks.example.com/deliver']);
    $delivery = WebhookDelivery::factory()->create([
        'webhook_id' => $webhook->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempts' => 0,
    ]);
    actingAs($user);

    (new SendWebhookDelivery($delivery))->handle();

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Retrying);
});

test('another user cannot manage the app webhooks', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $owner->id]);
    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    actingAs($other);

    $this->get(route('apps.webhooks.index', $app))->assertNotFound();
    $this->post(route('apps.webhooks.store', $app), [
        'url' => 'https://hooks.example.com/x',
        'events' => [WebhookEvent::VERIFICATION_COMPLETED],
    ])->assertNotFound();
    $this->patch(route('apps.webhooks.update', [$app, $webhook]), [
        'url' => 'https://hooks.example.com/y',
        'events' => [WebhookEvent::VERIFICATION_COMPLETED],
    ])->assertNotFound();
    $this->get(route('apps.webhooks.logs', [$app, $webhook]))->assertNotFound();
});

<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Consent;
use App\Models\SandboxIdentity;
use App\Models\Verification;
use App\Models\VerificationFlow;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use App\Services\VerificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    foreach ([
        ['name' => WebhookEvent::VERIFICATION_COMPLETED, 'label' => 'Verification completed'],
        ['name' => WebhookEvent::VERIFICATION_EXPIRED, 'label' => 'Verification expired'],
        ['name' => WebhookEvent::CONSENT_REVOKED, 'label' => 'Consent revoked'],
    ] as $event) {
        WebhookEvent::create($event);
    }
});

function triggerKeyFor(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-trigger-'.$key->id)])->saveQuietly();

    return $key;
}

function triggerPreset(App $app, int $value = 18): void
{
    $preset = AgeQueryPreset::factory()->create(['type' => AgeQueryType::EqualAbove, 'value' => $value]);
    $app->ageQueryPresets()->attach($preset);
}

function subscribeTo(Webhook $webhook, string ...$eventNames): void
{
    $ids = WebhookEvent::query()->whereIn('name', $eventNames)->pluck('id');
    $webhook->events()->attach($ids);
}

test('an API verification creates a verification.completed delivery and sends it', function () {
    $app = App::factory()->create();
    $key = triggerKeyFor($app);
    triggerPreset($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $webhook = Webhook::factory()->create([
        'app_id' => $app->id,
        'url' => 'https://hooks.example.com/verify',
    ]);
    subscribeTo($webhook, WebhookEvent::VERIFICATION_COMPLETED);

    Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

    $this->postJson(route('api.v1.verify.age'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ], ['Authorization' => 'Bearer sk-trigger-'.$key->id])
        ->assertOk()
        ->assertJsonPath('result', true);

    $delivery = WebhookDelivery::query()->where('webhook_id', $webhook->id)->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->event)->toBe(WebhookEvent::VERIFICATION_COMPLETED)
        ->and($delivery->payload['event'])->toBe(WebhookEvent::VERIFICATION_COMPLETED)
        ->and($delivery->payload['query_id'])->toBeString()
        ->and($delivery->payload['subject'])->toBe('fayda-test-a')
        ->and($delivery->payload['age'])->toBe(18)
        ->and($delivery->payload['type'])->toBe('equal_above')
        ->and($delivery->payload['result'])->toBeTrue()
        ->and($delivery->payload['environment'])->toBe('sandbox')
        ->and($delivery->payload['occurred_at'])->toBeString()
        ->and($delivery->idempotency_key)->toBe($delivery->payload['query_id']);

    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.com/verify'
        && $request->hasHeader('X-Troth-Event', WebhookEvent::VERIFICATION_COMPLETED));
});

test('a hosted flow completion creates a verification.completed delivery', function () {
    $app = App::factory()->create();
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);
    $key->forceFill(['secret_hash' => hash('sha256', 'sk-trigger-'.$key->id)])->saveQuietly();
    triggerPreset($app);
    VerificationFlow::factory()->create([
        'app_id' => $app->id,
        'success_redirect_url' => 'http://localhost:4100/continue',
        'failure_redirect_url' => 'http://localhost:4100/failed',
        'callback_url' => null,
        'callback_secret' => null,
    ]);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $webhook = Webhook::factory()->create([
        'app_id' => $app->id,
        'url' => 'https://hooks.example.com/flow',
    ]);
    subscribeTo($webhook, WebhookEvent::VERIFICATION_COMPLETED);

    $token = (string) Str::uuid();

    Verification::create([
        'app_id' => $app->id,
        'app_environment_id' => $environment->id,
        'token' => $token,
        'query_id' => $token,
        'expires_at' => now()->addMinutes(15),
        'status' => Verification::STATUS_PENDING,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
        'subject_identifier' => 'user-123',
        'callback_url' => null,
        'created_at' => now(),
    ]);

    Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

    $this->from(route('verify.flow', $token))
        ->post(route('verify.flow.submit', $token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ])
        ->assertRedirect();

    $delivery = WebhookDelivery::query()->where('webhook_id', $webhook->id)->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->event)->toBe(WebhookEvent::VERIFICATION_COMPLETED)
        ->and($delivery->payload['query_id'])->toBe($token)
        ->and($delivery->payload['subject'])->toBe('user-123')
        ->and($delivery->payload['result'])->toBeTrue()
        ->and($delivery->payload['environment'])->toBe('sandbox');
});

test('revoking a consent creates a consent.revoked delivery exactly once', function () {
    $app = App::factory()->create();
    $key = triggerKeyFor($app);
    $consent = Consent::factory()->active()->create(['app_id' => $app->id]);

    $webhook = Webhook::factory()->create([
        'app_id' => $app->id,
        'url' => 'https://hooks.example.com/consent',
    ]);
    subscribeTo($webhook, WebhookEvent::CONSENT_REVOKED);

    Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], [
        'Authorization' => 'Bearer sk-trigger-'.$key->id,
    ])->assertOk();

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], [
        'Authorization' => 'Bearer sk-trigger-'.$key->id,
    ])->assertOk();

    $deliveries = WebhookDelivery::query()->where('webhook_id', $webhook->id)->get();

    expect($deliveries)->toHaveCount(1)
        ->and($deliveries->first()->event)->toBe(WebhookEvent::CONSENT_REVOKED)
        ->and($deliveries->first()->payload['consent_id'])->toBe($consent->id)
        ->and($deliveries->first()->payload['subject'])->toBe($consent->subject_identifier)
        ->and($deliveries->first()->payload['revoked_at'])->toBeString()
        ->and($deliveries->first()->idempotency_key)->toBe('consent:'.$consent->id);
});

test('consents:expire creates verification.expired deliveries for subscribed apps', function () {
    $app = App::factory()->create();

    $expiring = Consent::factory()->active()->create([
        'app_id' => $app->id,
        'subject_identifier' => 'user-123',
        'expires_at' => now()->subDay(),
    ]);

    $verification = Verification::factory()->completed()->create([
        'app_id' => $app->id,
        'subject_identifier' => 'user-123',
        'expires_at' => now()->addDays(VerificationService::RESULT_TTL_DAYS),
    ]);

    $webhook = Webhook::factory()->create([
        'app_id' => $app->id,
        'url' => 'https://hooks.example.com/expired',
    ]);
    subscribeTo($webhook, WebhookEvent::VERIFICATION_EXPIRED);

    Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

    $this->artisan('consents:expire')->assertExitCode(0);

    $delivery = WebhookDelivery::query()->where('webhook_id', $webhook->id)->first();

    expect($delivery)->not->toBeNull()
        ->and($delivery->event)->toBe(WebhookEvent::VERIFICATION_EXPIRED)
        ->and($delivery->payload['query_id'])->toBe($verification->query_id)
        ->and($delivery->payload['subject'])->toBe('user-123')
        ->and($delivery->payload['expired_at'])->toBe($expiring->expires_at->toIso8601String())
        ->and($delivery->payload['occurred_at'])->toBeString()
        ->and($delivery->idempotency_key)->toBe($verification->query_id);
});

test('an unsubscribed webhook receives nothing', function () {
    $app = App::factory()->create();
    $key = triggerKeyFor($app);
    triggerPreset($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $webhook = Webhook::factory()->create([
        'app_id' => $app->id,
        'url' => 'https://hooks.example.com/unsubscribed',
    ]);
    subscribeTo($webhook, WebhookEvent::CONSENT_REVOKED);

    Http::fake();

    $this->postJson(route('api.v1.verify.age'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ], ['Authorization' => 'Bearer sk-trigger-'.$key->id])
        ->assertOk();

    expect(WebhookDelivery::count())->toBe(0);

    Http::assertNothingSent();
});

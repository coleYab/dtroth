<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Models\ProviderIdentity;
use App\Models\SandboxIdentity;
use App\Models\Verification;
use App\Models\VerificationFlow;
use App\Models\VerificationSetting;
use App\Services\WebhookSignature;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function flowApp(): App
{
    return App::factory()->create();
}

function flowSandboxKey(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);
    $key->forceFill(['secret_hash' => hash('sha256', 'sk-flow-sandbox-'.$key->id)])->saveQuietly();

    return $key;
}

function flowProductionKey(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Production, 'is_live' => true]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);
    $key->forceFill(['secret_hash' => hash('sha256', 'sk-flow-prod-'.$key->id)])->saveQuietly();

    return $key;
}

function flowEnablePreset(App $app, AgeQueryType $type = AgeQueryType::EqualAbove, int $value = 18): void
{
    $preset = AgeQueryPreset::factory()->create(['type' => $type, 'value' => $value]);
    $app->ageQueryPresets()->attach($preset);
}

function flowConfigureBranding(App $app, array $overrides = []): VerificationFlow
{
    $flow = VerificationFlow::factory()->create(array_merge([
        'app_id' => $app->id,
        'brand_color' => '#7c3aed',
        'success_redirect_url' => 'http://localhost:4100/continue',
        'failure_redirect_url' => 'http://localhost:4100/failed',
        'callback_url' => 'http://localhost:4100/callback',
        'callback_secret' => 'whcb_test_'.Str::random(48),
    ], $overrides));

    return $flow;
}

function flowCreateSession(App $app, ApiKey $key, int $age = 18, string $subject = 'user-123'): Verification
{
    return Verification::create([
        'app_id' => $app->id,
        'app_environment_id' => $key->app_environment_id,
        'token' => (string) Str::uuid(),
        'expires_at' => now()->addMinutes(15),
        'status' => Verification::STATUS_PENDING,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => $age,
        'subject_identifier' => $subject,
        'callback_url' => $app->verificationFlow()->first()?->callback_url,
        'created_at' => now(),
    ]);
}

beforeEach(function () {
    Config::set('verify.providers.mock_fayda', array_merge(config('verify.providers.mock_fayda', []), [
        'latency_min_ms' => 0,
        'latency_max_ms' => 5,
        'timeout_ms' => 1000,
        'failure_rate' => 0.0,
    ]));
});

test('POST /v1/verify/session creates a pending session and returns the verification URL', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);

    $response = $this->postJson(route('api.v1.verify.session'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'user-123',
    ], ['Authorization' => 'Bearer sk-flow-sandbox-'.$key->id]);

    $response->assertOk()
        ->assertJsonPath('session_id', fn ($value) => is_string($value))
        ->assertJsonPath('verification_url', fn ($value) => str_contains($value, '/verify/'))
        ->assertJsonPath('expires_at', fn ($value) => is_string($value));

    $verification = Verification::query()->where('token', $response->json('session_id'))->first();

    expect($verification)->not->toBeNull()
        ->and($verification->status)->toBe(Verification::STATUS_PENDING)
        ->and($verification->query_value)->toBe(18)
        ->and($verification->subject_identifier)->toBe('user-123')
        ->and($verification->expires_at->isFuture())->toBeTrue()
        ->and($verification->app_environment_id)->toBe($key->app_environment_id);

    expect($verification->callback_url)->toBe('http://localhost:4100/callback');

    expect($app->verificationFlow()->first()->callback_secret)->not->toBeNull();

    $call = ApiCall::query()->where('path', '/v1/verify/session')->first();

    expect($call)->not->toBeNull()
        ->and($call->status_code)->toBe(200)
        ->and($call->query_value)->toBe(18);
});

test('POST /v1/verify/session rejects missing auth, bad presets, and malformed bodies', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowConfigureBranding($app);

    $this->postJson(route('api.v1.verify.session'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'user-123',
    ])->assertStatus(401)->assertJsonPath('error.code', 'invalid_key');

    $this->postJson(route('api.v1.verify.session'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'user-123',
    ], ['Authorization' => 'Bearer sk-flow-sandbox-'.$key->id])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'preset_not_allowed');

    flowEnablePreset($app);

    $this->postJson(route('api.v1.verify.session'), [
        'age' => 181,
        'type' => 'nope',
        'subject' => 'user-123',
    ], ['Authorization' => 'Bearer sk-flow-sandbox-'.$key->id])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');

    expect(Verification::count())->toBe(0);
});

test('GET /verify/{token} renders the branded flow page', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app, ['brand_color' => '#7c3aed', 'callback_url' => null]);
    VerificationSetting::factory()->create(['app_id' => $app->id, 'consent_text' => 'I agree to the check.']);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key);

    $this->get(route('verify.flow', $session->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('verify/flow')
            ->where('app.name', $app->name)
            ->where('state', 'valid')
            ->where('mode', 'sandbox')
            ->where('branding.brand_color', '#7c3aed')
            ->where('branding.consent_text', 'I agree to the check.')
            ->where('query.label', '18+'));
});

test('the flow page carries security headers that block embedding and referrer leaks', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app, ['callback_url' => null]);

    $session = flowCreateSession($app, $key);

    $headers = $this->get(route('verify.flow', $session->token))->baseResponse->headers;

    expect($headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'")
        ->and($headers->get('Referrer-Policy'))->toBe('no-referrer')
        ->and($headers->get('X-Frame-Options'))->toBe('DENY');
});

test('the flow completes a sandbox verification, records consent, and signs the callback', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    $flow = flowConfigureBranding($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key, subject: 'user-123');

    Http::fake();

    $response = $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ]);

    $location = $response->headers->get('Location');

    expect($location)->toContain('http://localhost:4100/continue?verification=success')
        ->toContain('query_id='.$session->id)
        ->toContain('subject=user-123')
        ->toContain('result=true');

    $session->refresh();

    expect($session->status)->toBe(Verification::STATUS_COMPLETED)
        ->and($session->result)->toBeTrue()
        ->and($session->completed_at)->not->toBeNull();

    $consent = Consent::query()->where('app_id', $app->id)->where('subject_identifier', 'user-123')->first();

    expect($consent)->not->toBeNull()
        ->and($consent->status)->toBe(Consent::STATUS_ACTIVE)
        ->and($consent->result)->toBeTrue();

    expect(ConsentEvent::query()->where('consent_id', $consent->id)->value('type'))->toBe('granted');

    Http::assertSent(function ($request) use ($flow) {
        if ($request->url() !== 'http://localhost:4100/callback') {
            return false;
        }

        $signature = new WebhookSignature;
        $payload = json_decode($request->body(), true);

        return $request->header('X-Troth-Event')[0] === 'verification.completed'
            && $request->header('X-Troth-Signature')[0] === $signature->sign($request->body(), $flow->fresh()->callback_secret)
            && $payload['result'] === true
            && $payload['subject'] === 'user-123';
    });

    expect(ApiCall::query()->where('path', '/verify/'.$session->token)->value('result'))->toBeTrue();
});

test('a repeat verification by the same subject renews their consent', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $first = flowCreateSession($app, $key, subject: 'user-123');
    $second = flowCreateSession($app, $key, subject: 'user-123');

    Http::fake();

    foreach ([$first, $second] as $session) {
        $this->from(route('verify.flow', $session->token))
            ->post(route('verify.flow.submit', $session->token), [
                'national_id' => 'fayda-test-a',
                'consent' => '1',
            ])
            ->assertRedirect();
    }

    $consent = Consent::query()->where('app_id', $app->id)->where('subject_identifier', 'user-123')->first();

    expect($consent)->not->toBeNull();

    $events = ConsentEvent::query()->where('consent_id', $consent->id)->orderBy('id')->pluck('type')->all();

    expect($events)->toBe(['granted', 'renewed']);
});

test('the flow declines a blocked identity, completes the session, and reports the error to the callback', function () {
    $app = flowApp();
    $key = flowProductionKey($app);
    flowEnablePreset($app);
    $flow = flowConfigureBranding($app);
    ProviderIdentity::factory()->blocked()->create(['national_id' => '185141583902674']);

    $session = flowCreateSession($app, $key);

    Http::fake();

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => '185141583902674',
            'consent' => '1',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('verify/flow')
            ->where('state', 'valid')
            ->where('error.code', 'identity_provider_declined'));

    $session->refresh();

    expect($session->status)->toBe(Verification::STATUS_COMPLETED)
        ->and($session->result)->toBeNull();

    expect(Consent::count())->toBe(0);

    Http::assertSent(function ($request) {
        $payload = json_decode($request->body(), true);

        return $request->url() === 'http://localhost:4100/callback'
            && $payload['result'] === null
            && $payload['error']['code'] === 'identity_provider_declined';
    });
});

test('an unknown subject gets an inline error without completing the session', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key);

    Http::fake();

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'does-not-exist',
            'consent' => '1',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('verify/flow')
            ->where('state', 'valid')
            ->where('error.code', 'unknown_subject'));

    $session->refresh();

    expect($session->status)->toBe(Verification::STATUS_PENDING)
        ->and(Consent::count())->toBe(0);

    Http::assertNothingSent();
});

test('a valid ID below the threshold redirects to the failure URL with an error', function () {
    $app = flowApp();
    $key = flowProductionKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    ProviderIdentity::factory()->aged(16)->create(['national_id' => '185063384725618']);

    $session = flowCreateSession($app, $key);

    Http::fake();

    $response = $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => '185063384725618',
            'consent' => '1',
        ]);

    $location = $response->headers->get('Location');

    expect($location)->toContain('http://localhost:4100/failed?verification=failed')
        ->toContain('result=false')
        ->toContain('error=identity_failed');

    $session->refresh();

    expect($session->status)->toBe(Verification::STATUS_COMPLETED)
        ->and($session->result)->toBeFalse()
        ->and(Consent::count())->toBe(0);

    Http::assertSent(fn ($request) => json_decode($request->body(), true)['result'] === false);
});

test('the flow requires consent and a valid national ID', function () {
    $app = flowApp();
    $key = flowProductionKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    ProviderIdentity::factory()->aged(23)->create(['national_id' => '185026948372154']);

    $session = flowCreateSession($app, $key);
    $url = route('verify.flow', $session->token);

    $this->from($url)
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => '185026948372154',
        ])
        ->assertRedirect($url)
        ->assertSessionHasErrors('consent');

    $this->from($url)
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => '12345',
            'consent' => '1',
        ])
        ->assertRedirect($url)
        ->assertSessionHasErrors('national_id');

    $session->refresh();

    expect($session->status)->toBe(Verification::STATUS_PENDING)
        ->and(Consent::count())->toBe(0)
        ->and(ApiCall::count())->toBe(0);
});

test('expired and used sessions render the unavailable state', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app, ['callback_url' => null]);

    $expired = flowCreateSession($app, $key);
    $expired->forceFill(['expires_at' => now()->subMinute()])->saveQuietly();

    $used = flowCreateSession($app, $key);
    $used->forceFill([
        'status' => Verification::STATUS_COMPLETED,
        'completed_at' => now(),
        'result' => true,
    ])->saveQuietly();

    $this->get(route('verify.flow', $expired->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('verify/flow')->where('state', 'expired'));

    $this->get(route('verify.flow', $used->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('verify/flow')->where('state', 'used'));

    $this->get(route('verify.flow', Str::uuid()->toString()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('verify/flow')->where('state', 'invalid'));
});

test('completed sessions cannot be reused', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key);

    Http::fake();

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ])
        ->assertRedirect();

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('verify/flow')->where('state', 'used'));

    Http::assertSentCount(1);
});

test('the flow falls back to an on-page outcome when no redirect URL is configured', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app, ['success_redirect_url' => null, 'failure_redirect_url' => null, 'callback_url' => null]);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key);

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('verify/flow')
            ->where('state', 'success')
            ->where('outcome.result', true));
});

test('the flow records the national ID in the call trail only via response payload', function () {
    $app = flowApp();
    $key = flowSandboxKey($app);
    flowEnablePreset($app);
    flowConfigureBranding($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $session = flowCreateSession($app, $key);

    Http::fake();

    $this->from(route('verify.flow', $session->token))
        ->post(route('verify.flow.submit', $session->token), [
            'national_id' => 'fayda-test-a',
            'consent' => '1',
        ])
        ->assertRedirect();

    $call = ApiCall::query()->where('path', '/verify/'.$session->token)->first();

    expect($call)->not->toBeNull()
        ->and($call->request_payload)->toBe(['session_id' => $session->token])
        ->and(str_contains(json_encode($call->request_payload), 'fayda-test-a'))->toBeFalse();
});

<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\AppEnvironment;
use App\Models\ProviderIdentity;
use App\Models\SandboxIdentity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

function makeSandboxKey(App $app, ?AppEnvironment $environment = null): ApiKey
{
    $env = $environment ?? $app->environments()->create(['name' => EnvironmentName::Sandbox]);

    return ApiKey::factory()->forEnvironment($env)->create(['app_id' => $app->id]);
}

function secretFor(ApiKey $key): string
{
    $secret = 'sk_sandbox_'.Str::random(48);
    $key->forceFill(['secret_hash' => hash('sha256', $secret)])->saveQuietly();

    return $secret;
}

function enablePreset(App $app, AgeQueryType $type, int $value): void
{
    $preset = AgeQueryPreset::factory()->create(['type' => $type, 'value' => $value]);
    $app->ageQueryPresets()->attach($preset);
}

function sandboxKeyFor(App $app): ApiKey
{
    $key = makeSandboxKey($app);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-test-'.$key->id)])->saveQuietly();

    return $key;
}

function productionKeyFor(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Production, 'is_live' => true]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-prod-'.$key->id)])->saveQuietly();

    return $key;
}

function verifyPayload(int $age, string $type, string $subject): array
{
    return ['age' => $age, 'type' => $type, 'subject' => $subject];
}

test('a valid request returns the verification result for a sandbox identity', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);
    SandboxIdentity::create(['key' => 'fayda-test-b', 'label' => 'Test User B', 'age' => 16]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertOk()
        ->assertJsonPath('result', true)
        ->assertJsonPath('query_id', fn ($value) => is_string($value) && strlen($value) === 36)
        ->assertJsonPath('verified_at', fn ($value) => is_string($value))
        ->assertJsonPath('expires_at', fn ($value) => is_string($value));

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-b'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertOk()
        ->assertJsonPath('result', false);
});

test('a valid equal_below request is resolved against the identity age', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualBelow, 16);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(16, 'equal_below', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertOk()
        ->assertJsonPath('result', false);
});

test('every successful call is recorded in the api_calls log', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])->assertOk();

    $call = ApiCall::query()->latest('id')->first();

    expect($call)->not->toBeNull()
        ->and($call->app_id)->toBe($app->id)
        ->and($call->query_type)->toBe(AgeQueryType::EqualAbove)
        ->and($call->query_value)->toBe(18)
        ->and($call->result)->toBeTrue()
        ->and($call->status_code)->toBe(200)
        ->and($call->request_payload['subject'])->toBe('fayda-test-a')
        ->and($call->response_payload['result'])->toBeTrue();
});

test('a missing or invalid bearer token is rejected with 401', function () {
    $app = App::factory()->create();

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_key');

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer nope',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_key');
});

test('a revoked key is rejected with 401', function () {
    $app = App::factory()->create();
    $env = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($env)->revoked()->create(['app_id' => $app->id]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_key');
});

test('a disabled preset is rejected with 403 and points to verification settings', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 16);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'preset_not_allowed');
});

test('an unknown subject is rejected with 404', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'does-not-exist'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertNotFound()
        ->assertJsonPath('error.code', 'unknown_subject');
});

test('validation errors return 422 with the validation_error code', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(0, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'maybe', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

    $this->postJson(route('api.v1.verify.age'), ['age' => 18], [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
});

test('a production key verifies a known national id through the mock provider', function () {
    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185026948372154'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])
        ->assertOk()
        ->assertJsonPath('result', true)
        ->assertJsonPath('provider_reference', fn ($value) => is_string($value) && str_starts_with($value, 'fayda-'));
});

test('a production key returns false for a national id below the threshold', function () {
    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185063384725618',
        'birth_date' => now()->subYears(16)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185063384725618'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])
        ->assertOk()
        ->assertJsonPath('result', false);
});

test('a production verify with an unknown national id returns 404 subject_not_found', function () {
    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '000000000000000'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])
        ->assertNotFound()
        ->assertJsonPath('error.code', 'subject_not_found');
});

test('a production verify with a blocked national id returns 403 identity_provider_declined', function () {
    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185141583902674',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'blocked',
    ]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185141583902674'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'identity_provider_declined');
});

test('the simulated provider latency is reflected in the recorded response time', function () {
    config()->set('verify.providers.mock_fayda.latency_min_ms', 150);
    config()->set('verify.providers.mock_fayda.latency_max_ms', 150);

    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185026948372154'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])->assertOk();

    $call = ApiCall::query()->latest('id')->first();

    expect($call->response_time_ms)->toBeGreaterThanOrEqual(150);
});

test('the provider reference is recorded in the api_calls log for production verifies', function () {
    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185026948372154'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])->assertOk();

    $call = ApiCall::query()->latest('id')->first();

    expect($call->response_payload['provider_reference'])->toStartWith('fayda-')
        ->and($call->response_payload)->not->toHaveKey('birth_date')
        ->and($call->response_payload)->not->toHaveKey('full_name');
});

test('a production provider failure is recorded as a failed call', function () {
    config()->set('verify.providers.mock_fayda.failure_rate', 1.0);
    config()->set('verify.providers.mock_fayda.latency_min_ms', 0);
    config()->set('verify.providers.mock_fayda.latency_max_ms', 0);

    $app = App::factory()->create();
    $key = productionKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $response = $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', '185026948372154'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ]);

    expect($response->getStatusCode())->toBeIn([502, 504]);

    $call = ApiCall::query()->latest('id')->first();

    expect($call->status_code)->toBe($response->getStatusCode())
        ->and($call->response_payload['error']['code'])->toBeIn(['identity_provider_unreachable', 'identity_provider_timeout']);
});

test('the endpoint is rate limited per key', function () {
    Cache::flush();

    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    enablePreset($app, AgeQueryType::EqualAbove, 18);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    for ($i = 0; $i < 30; $i++) {
        $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
            'Authorization' => 'Bearer sk-test-'.$key->id,
        ])->assertOk();
    }

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

test('failed calls are recorded in the api_calls log', function () {
    $app = App::factory()->create();
    $key = sandboxKeyFor($app);
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-test-'.$key->id,
    ])->assertForbidden();

    $call = ApiCall::query()->latest('id')->first();

    expect($call)->not->toBeNull()
        ->and($call->status_code)->toBe(403)
        ->and($call->response_payload['error']['code'])->toBe('preset_not_allowed');
});

test('failed authentication is never recorded as a call', function () {
    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer nope',
    ])->assertUnauthorized();

    expect(ApiCall::query()->count())->toBe(0);
});

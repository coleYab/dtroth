<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\AppEnvironment;
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

test('production keys are rejected until the identity provider is available', function () {
    $app = App::factory()->create();
    $production = $app->environments()->create(['name' => EnvironmentName::Production, 'is_live' => true]);
    $key = ApiKey::factory()->forEnvironment($production)->create(['app_id' => $app->id]);
    $key->forceFill(['secret_hash' => hash('sha256', 'sk-prod-'.$key->id)])->saveQuietly();

    $this->postJson(route('api.v1.verify.age'), verifyPayload(18, 'equal_above', 'fayda-test-a'), [
        'Authorization' => 'Bearer sk-prod-'.$key->id,
    ])
        ->assertStatus(501)
        ->assertJsonPath('error.code', 'identity_provider_unavailable');
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

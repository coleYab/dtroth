<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\SandboxIdentity;

function rateLimitApp(): App
{
    $app = App::factory()->create();
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-ratelimit-'.$key->id)])->saveQuietly();

    $preset = AgeQueryPreset::factory()->create(['type' => AgeQueryType::EqualAbove, 'value' => 18]);
    $app->ageQueryPresets()->attach($preset);

    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    return $app;
}

function rateLimitAuth(ApiKey $key): array
{
    return ['Authorization' => 'Bearer sk-ratelimit-'.$key->id];
}

test('successful API calls return the X-RateLimit headers', function () {
    $app = rateLimitApp();
    $key = $app->keys()->first();

    $response = $this->postJson(route('api.v1.verify.age'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ], rateLimitAuth($key));

    $response->assertOk()
        ->assertHeader('X-RateLimit-Limit', '60')
        ->assertHeader('X-RateLimit-Remaining', '59')
        ->assertHeader('X-RateLimit-Reset');

    expect((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
});

test('the session endpoint emits the rate limit headers', function () {
    $app = rateLimitApp();
    $key = $app->keys()->first();

    $this->postJson(route('api.v1.verify.session'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ], rateLimitAuth($key))
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', '60')
        ->assertHeader('X-RateLimit-Remaining', '59');
});

test('the lookup and revoke endpoints emit the rate limit headers', function () {
    $app = rateLimitApp();
    $key = $app->keys()->first();

    $this->getJson(route('api.v1.verify.show', ['query_id' => 'missing']), rateLimitAuth($key))
        ->assertNotFound()
        ->assertHeader('X-RateLimit-Limit', '60')
        ->assertHeader('X-RateLimit-Remaining', '59');

    $response = $this->postJson(route('api.v1.consents.revoke', ['consent' => 999]), [], rateLimitAuth($key));
    $response->assertNotFound()
        ->assertHeader('X-RateLimit-Limit', '60')
        ->assertHeader('X-RateLimit-Remaining', '58');
});

test('exceeding the limit returns 429 with Retry-After and the standard envelope', function () {
    config(['verify.api_requests_per_minute' => 3]);

    $app = rateLimitApp();
    $key = $app->keys()->first();

    foreach (range(1, 3) as $attempt) {
        $this->postJson(route('api.v1.verify.age'), [
            'age' => 18,
            'type' => 'equal_above',
            'subject' => 'fayda-test-a',
        ], rateLimitAuth($key))
            ->assertOk()
            ->assertHeader('X-RateLimit-Remaining', (string) (3 - $attempt));
    }

    $this->postJson(route('api.v1.verify.age'), [
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ], rateLimitAuth($key))
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error.code', 'too_many_requests');
});

test('rate limit state is tracked per IP', function () {
    config(['verify.api_requests_per_minute' => 1]);

    $app = rateLimitApp();
    $key = $app->keys()->first();

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->postJson(route('api.v1.verify.age'), [
            'age' => 18,
            'type' => 'equal_above',
            'subject' => 'fayda-test-a',
        ], rateLimitAuth($key))
        ->assertOk()
        ->assertHeader('X-RateLimit-Remaining', '0');

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->postJson(route('api.v1.verify.age'), [
            'age' => 18,
            'type' => 'equal_above',
            'subject' => 'fayda-test-a',
        ], rateLimitAuth($key))
        ->assertOk()
        ->assertHeader('X-RateLimit-Remaining', '0');
});

<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Verification;
use App\Services\IdentityProviderRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function lookupKeyFor(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-lookup-'.$key->id)])->saveQuietly();

    return $key;
}

function lookupAuth(ApiKey $key): array
{
    return ['Authorization' => 'Bearer sk-lookup-'.$key->id];
}

test('returns the stored result for a completed verification without contacting the provider', function () {
    $app = App::factory()->create();
    $key = lookupKeyFor($app);

    $verification = Verification::factory()->completed()->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
        'result' => true,
        'created_at' => now()->subHours(2),
    ]);

    $this->mock(IdentityProviderRegistry::class, fn ($mock) => $mock->shouldReceive('resolve')->never());

    Http::fake();

    $this->getJson(route('api.v1.verify.show', ['query_id' => $verification->query_id]), lookupAuth($key))
        ->assertOk()
        ->assertJsonPath('query_id', $verification->query_id)
        ->assertJsonPath('result', true)
        ->assertJsonPath('age', 18)
        ->assertJsonPath('type', 'equal_above')
        ->assertJsonPath('verified_at', $verification->created_at->toIso8601String())
        ->assertJsonPath('expires_at', $verification->expires_at->toIso8601String())
        ->assertJsonPath('expired', false);

    Http::assertNothingSent();
});

test('an unknown query ID returns a not_found error', function () {
    $app = App::factory()->create();
    $key = lookupKeyFor($app);

    $this->getJson(route('api.v1.verify.show', ['query_id' => (string) Str::uuid()]), lookupAuth($key))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found')
        ->assertJsonPath('error.message', fn ($value) => is_string($value));
});

test('a result belonging to another app is not exposed', function () {
    $owner = App::factory()->create();
    $intruder = App::factory()->create();

    $verification = Verification::factory()->completed()->create([
        'app_id' => $owner->id,
    ]);

    $intruderKey = lookupKeyFor($intruder);

    $this->getJson(route('api.v1.verify.show', ['query_id' => $verification->query_id]), lookupAuth($intruderKey))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

test('an expired result returns an expired error', function () {
    $app = App::factory()->create();
    $key = lookupKeyFor($app);

    $verification = Verification::factory()->completed()->create([
        'app_id' => $app->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->getJson(route('api.v1.verify.show', ['query_id' => $verification->query_id]), lookupAuth($key))
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'expired')
        ->assertJsonPath('error.message', fn ($value) => is_string($value));
});

test('a pending hosted-flow session is not returned until it completes', function () {
    $app = App::factory()->create();
    $key = lookupKeyFor($app);

    $verification = Verification::factory()->pending()->create([
        'app_id' => $app->id,
    ]);

    $this->getJson(route('api.v1.verify.show', ['query_id' => $verification->query_id]), lookupAuth($key))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

test('invalid credentials are rejected', function () {
    $app = App::factory()->create();
    lookupKeyFor($app);

    $this->getJson(route('api.v1.verify.show', ['query_id' => (string) Str::uuid()]), [
        'Authorization' => 'Bearer sk-invalid-'.$app->id,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_key');
});

<?php

use App\Enums\EnvironmentName;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Services\IdentityProviderRegistry;
use Illuminate\Support\Facades\Http;

function revokeKeyFor(App $app): ApiKey
{
    $environment = $app->environments()->create(['name' => EnvironmentName::Sandbox]);
    $key = ApiKey::factory()->forEnvironment($environment)->create(['app_id' => $app->id]);

    $key->forceFill(['secret_hash' => hash('sha256', 'sk-revoke-'.$key->id)])->saveQuietly();

    return $key;
}

function revokeAuth(ApiKey $key): array
{
    return ['Authorization' => 'Bearer sk-revoke-'.$key->id];
}

test('an active consent is revoked and a revoked event is recorded', function () {
    $app = App::factory()->create();
    $key = revokeKeyFor($app);
    $consent = Consent::factory()->active()->withEvents()->create(['app_id' => $app->id]);

    $this->mock(IdentityProviderRegistry::class, fn ($mock) => $mock->shouldReceive('resolve')->never());

    Http::fake();

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], revokeAuth($key))
        ->assertOk()
        ->assertJsonPath('consent_id', $consent->id)
        ->assertJsonPath('status', 'revoked')
        ->assertJsonPath('revoked_at', fn ($value) => is_string($value));

    $consent->refresh();

    expect($consent->isRevoked())->toBeTrue()
        ->and($consent->revoked_at)->not->toBeNull()
        ->and(ConsentEvent::query()->where('consent_id', $consent->id)->where('type', ConsentEvent::TYPE_REVOKED)->count())->toBe(1);

    Http::assertNothingSent();
});

test('revoking an unknown consent returns a not_found error', function () {
    $app = App::factory()->create();
    $key = revokeKeyFor($app);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => 999999]), [], revokeAuth($key))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

test('a consent belonging to another app is not exposed', function () {
    $owner = App::factory()->create();
    $intruder = App::factory()->create();

    $consent = Consent::factory()->active()->create(['app_id' => $owner->id]);

    $intruderKey = revokeKeyFor($intruder);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], revokeAuth($intruderKey))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');

    expect($consent->fresh()->isRevoked())->toBeFalse();
});

test('revoking an already-revoked consent is idempotent', function () {
    $app = App::factory()->create();
    $key = revokeKeyFor($app);
    $consent = Consent::factory()->revoked()->withEvents()->create(['app_id' => $app->id]);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], revokeAuth($key))
        ->assertOk()
        ->assertJsonPath('status', 'revoked');

    expect(ConsentEvent::query()->where('consent_id', $consent->id)->where('type', ConsentEvent::TYPE_REVOKED)->count())->toBe(0);
});

test('an expired consent can still be revoked', function () {
    $app = App::factory()->create();
    $key = revokeKeyFor($app);
    $consent = Consent::factory()->expired()->create(['app_id' => $app->id]);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], revokeAuth($key))
        ->assertOk()
        ->assertJsonPath('status', 'revoked');

    expect($consent->fresh()->isRevoked())->toBeTrue()
        ->and(ConsentEvent::query()->where('consent_id', $consent->id)->where('type', ConsentEvent::TYPE_REVOKED)->count())->toBe(1);
});

test('invalid credentials are rejected', function () {
    $app = App::factory()->create();
    $consent = Consent::factory()->active()->create(['app_id' => $app->id]);

    $this->postJson(route('api.v1.consents.revoke', ['consent' => $consent->id]), [], [
        'Authorization' => 'Bearer sk-invalid-'.$app->id,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'invalid_key');
});

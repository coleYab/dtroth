<?php

use App\Enums\EnvironmentName;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('users can generate a key pair for an environment', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->post(route('apps.keys.store', $app), [
        'environment' => EnvironmentName::Sandbox->value,
        'name' => 'Mobile app',
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/keys')
            ->where('revealedKey.name', 'Mobile app')
            ->where('revealedKey.environment', 'sandbox')
            ->has('revealedKey.secret_key')
            ->has('revealedKey.public_key'));

    $key = $app->keys()->first();

    expect($key)->not->toBeNull()
        ->and($key->name)->toBe('Mobile app')
        ->and($key->app_environment_id)->toBe($app->environments->firstWhere('name', EnvironmentName::Sandbox)->id)
        ->and($key->public_key)->toStartWith('pk_sandbox_')
        ->and($key->secret_preview)->toContain('••••')
        ->and($key->secret_hash)->not->toBeNull();
});

test('the secret is only present in the generation response', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->post(route('apps.keys.store', $app), ['environment' => EnvironmentName::Sandbox->value])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('revealedKey.secret_key'));

    $this->get(route('apps.keys.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('keys.data', 1)
            ->missing('keys.data.0.secret_key')
            ->where('keys.data.0.secret_preview', fn ($value) => str_contains($value, '••••')));
});

test('users can rotate a key and get a new secret once', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $key = ApiKey::factory()->create(['app_id' => $app->id, 'name' => 'Primary']);
    $oldHash = $key->secret_hash;
    actingAs($user);

    $this->post(route('apps.keys.rotate', [$app, $key]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/keys')
            ->where('revealedKey.id', $key->id)
            ->has('revealedKey.secret_key'));

    $key->refresh();

    expect($key->secret_hash)->not->toBe($oldHash)
        ->and($key->public_key)->toBe($key->public_key);
});

test('revoked keys cannot be rotated', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $key = ApiKey::factory()->revoked()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->post(route('apps.keys.rotate', [$app, $key]))->assertStatus(422);
});

test('users can revoke a key and it stays listed as revoked', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $key = ApiKey::factory()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->patch(route('apps.keys.revoke', [$app, $key]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/keys')
            ->where('keys.data.0.is_revoked', true));

    expect($key->refresh()->revoked_at)->not->toBeNull();
});

test('already revoked keys cannot be revoked again', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $key = ApiKey::factory()->revoked()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->patch(route('apps.keys.revoke', [$app, $key]))->assertStatus(422);
});

test('users cannot manage keys of another user application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $otherUser->id]);
    $key = ApiKey::factory()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->post(route('apps.keys.store', $app), ['environment' => 'sandbox'])->assertNotFound();
    $this->post(route('apps.keys.rotate', [$app, $key]))->assertNotFound();
    $this->patch(route('apps.keys.revoke', [$app, $key]))->assertNotFound();
});

test('the create page shows the generate dialog open', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('apps.keys.create', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/keys')
            ->where('generateOpen', true));
});

test('key list includes the environment label', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $environment = $app->environments->firstWhere('name', EnvironmentName::Production);
    ApiKey::factory()->create([
        'app_id' => $app->id,
        'app_environment_id' => $environment->id,
        'name' => 'Live key',
    ]);
    actingAs($user);

    $this->get(route('apps.keys.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('keys.data.0.environment', 'production')
            ->where('keys.data.0.name', 'Live key'));
});

test('a key can belong to a shared environment', function () {
    $user = User::factory()->create();
    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    $key = ApiKey::factory()->create(['app_id' => $app->id, 'app_environment_id' => null]);
    actingAs($user);

    $this->get(route('apps.keys.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('keys.data.0.environment', 'shared'));

    expect($key->environment)->toBeNull();
});

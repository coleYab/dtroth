<?php

use App\Enums\AppStatus;
use App\Models\ActivityEvent;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Consent;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from the apps list', function () {
    $this->get(route('apps.index'))->assertRedirect(route('login'));
});

test('users can list their own applications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    App::factory()->count(2)->create(['user_id' => $user->id]);
    App::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->get(route('apps.index'));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('apps/index')
        ->has('apps.data', 2));
});

test('apps list can be searched by name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    App::factory()->create(['user_id' => $user->id, 'name' => 'Payment Gateway']);
    App::factory()->create(['user_id' => $user->id, 'name' => 'Age Gate']);

    $response = $this->get(route('apps.index', ['q' => 'Age']));

    $response->assertInertia(fn ($page) => $page
        ->component('apps/index')
        ->has('apps.data', 1)
        ->where('apps.data.0.name', 'Age Gate')
        ->where('filters.q', 'Age'));
});

test('users can visit the create application page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('apps.create'))->assertOk();
});

test('users can create an application with environments', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('apps.store'), [
        'name' => 'My First App',
        'environment' => 'sandbox',
        'redirect_uris' => ['https://example.com/callback'],
        'allowed_origins' => ['https://example.com'],
    ]);

    $app = $user->apps()->firstOrFail();

    $response->assertRedirect(route('apps.show', $app));

    expect($app->name)->toBe('My First App')
        ->and($app->status)->toBe(AppStatus::Active)
        ->and($app->environments)->toHaveCount(2)
        ->and($app->environments->pluck('name')->map->value)->toContain('sandbox')
        ->and($app->environments->pluck('name')->map->value)->toContain('production');
});

test('users can create an application with a logo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('apps.store'), [
        'name' => 'Branded App',
        'environment' => 'sandbox',
        'redirect_uris' => ['https://example.com/callback'],
        'allowed_origins' => ['https://example.com'],
        'image' => UploadedFile::fake()->image('logo.png'),
    ]);

    $app = $user->apps()->firstOrFail();

    $response->assertRedirect(route('apps.show', $app));

    expect($app->image)->not->toBeNull();
    Storage::disk('public')->assertExists($app->image);
});

test('application creation validates the input', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('apps.store'), [
        'name' => '',
        'redirect_uris' => ['not-a-url'],
        'allowed_origins' => ['https://example.com/some/path'],
    ]);

    $response->assertSessionHasErrors(['name', 'redirect_uris.0', 'allowed_origins.0']);

    expect($user->apps()->count())->toBe(0);
});

test('users can view their own application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(5)->create(['app_id' => $app->id]);
    Verification::factory()->approved()->create(['app_id' => $app->id]);
    Consent::factory()->count(3)->create(['app_id' => $app->id]);

    $response = $this->get(route('apps.show', $app));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('apps/show')
        ->where('app.name', $app->name)
        ->where('stats.apiCalls', 5)
        ->where('stats.activeConsents', 3)
        ->has('app.environments', 2));
});

test('users cannot view another user application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $otherUser->id]);

    $this->get(route('apps.show', $app))->assertNotFound();
});

test('users can view the keys page for their app', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);
    ApiKey::factory()->count(2)->create(['app_id' => $app->id]);

    $response = $this->get(route('apps.keys.index', $app));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('apps/keys')
        ->where('app.name', $app->name)
        ->has('keys.data', 2));
});

test('users cannot view the keys page of another user application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $otherUser->id]);

    $this->get(route('apps.keys.index', $app))->assertNotFound();
});

test('users can visit the settings page for their app', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);

    $this->get(route('apps.edit', $app))->assertOk();
});

test('users can update their application', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    $response = $this->patch(route('apps.update', $app), [
        'name' => 'Renamed App',
        'redirect_uris' => ['https://renamed.example.com/callback'],
        'allowed_origins' => ['https://renamed.example.com'],
    ]);

    $response->assertRedirect(route('apps.edit', $app));

    expect($app->fresh())
        ->name->toBe('Renamed App')
        ->redirect_uris->toBe(['https://renamed.example.com/callback'])
        ->allowed_origins->toBe(['https://renamed.example.com']);
});

test('users can replace the logo of their application', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create([
        'user_id' => $user->id,
        'image' => 'app-images/old-logo.png',
    ]);

    Storage::disk('public')->put('app-images/old-logo.png', 'old');

    $this->patch(route('apps.update', $app), [
        'name' => $app->name,
        'redirect_uris' => ['https://example.com/callback'],
        'allowed_origins' => ['https://example.com'],
        'image' => UploadedFile::fake()->image('new-logo.png'),
    ])->assertRedirect(route('apps.edit', $app));

    expect($app->fresh()->image)
        ->toStartWith('app-images/')
        ->toEndWith('.png');

    Storage::disk('public')->assertMissing('app-images/old-logo.png');
    Storage::disk('public')->assertExists($app->fresh()->image);
});

test('application update validates the input', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    $response = $this->patch(route('apps.update', $app), [
        'name' => '',
        'redirect_uris' => [],
    ]);

    $response->assertSessionHasErrors(['name', 'redirect_uris']);
});

test('users can delete their application and related data cascades', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);

    ApiKey::factory()->create(['app_id' => $app->id]);
    ApiCall::factory()->count(4)->create(['app_id' => $app->id]);
    Verification::factory()->count(2)->create(['app_id' => $app->id]);
    Consent::factory()->create(['app_id' => $app->id]);
    ActivityEvent::factory()->create(['app_id' => $app->id]);

    $response = $this->delete(route('apps.destroy', $app));

    $response->assertRedirect(route('apps.index'));

    expect($user->apps()->count())->toBe(0)
        ->and(ApiKey::count())->toBe(0)
        ->and(ApiCall::count())->toBe(0)
        ->and(Verification::count())->toBe(0)
        ->and(Consent::count())->toBe(0)
        ->and(ActivityEvent::count())->toBe(0)
        ->and($app->environments()->count())->toBe(0);
});

test('users cannot delete another user application', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $otherUser->id]);

    $this->delete(route('apps.destroy', $app))->assertNotFound();

    expect($app->fresh())->not->toBeNull();
});

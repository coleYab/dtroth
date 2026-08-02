<?php

use App\Enums\EnvironmentName;
use App\Models\App;
use App\Models\User;

test('users can visit the environments page for their app', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);

    $response = $this->get(route('apps.environments.index', $app));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('apps/environments')
        ->has('app.environments', 2));
});

test('users can update an environment configuration', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);

    $sandbox = $app->environments->firstWhere('name', EnvironmentName::Sandbox);

    $response = $this->put(route('apps.environments.update', [$app, $sandbox]), [
        'is_enabled' => false,
        'redirect_uris' => ['https://sandbox.example.com/callback'],
        'allowed_origins' => ['https://sandbox.example.com'],
    ]);

    $response->assertRedirect(route('apps.environments.index', $app));

    expect($sandbox->fresh())
        ->is_enabled->toBeFalse()
        ->redirect_uris->toBe(['https://sandbox.example.com/callback']);
});

test('environment update validates the input', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $user->id]);

    $sandbox = $app->environments->firstWhere('name', EnvironmentName::Sandbox);

    $response = $this->put(route('apps.environments.update', [$app, $sandbox]), [
        'is_enabled' => 'not-a-boolean',
    ]);

    $response->assertSessionHasErrors('is_enabled');
});

test('users cannot update an environment of another user app', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->withEnvironments()->create(['user_id' => $otherUser->id]);

    $sandbox = $app->environments->firstWhere('name', EnvironmentName::Sandbox);

    $this->put(route('apps.environments.update', [$app, $sandbox]), [
        'is_enabled' => true,
    ])->assertNotFound();
});

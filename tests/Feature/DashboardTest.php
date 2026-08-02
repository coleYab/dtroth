<?php

use App\Enums\AppStatus;
use App\Models\ActivityEvent;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\User;
use App\Models\Verification;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard returns aggregated stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    App::factory()->count(2)->create(['user_id' => $user->id]);

    App::factory()->create([
        'user_id' => $user->id,
        'status' => AppStatus::Disabled,
    ]);

    $app = $user->apps()->first();

    ApiCall::factory()->count(10)->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(5),
    ]);

    ApiCall::factory()->count(3)->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(40),
    ]);

    Verification::factory()->approved()->count(4)->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(2),
    ]);

    ApiKey::factory()->expiringSoon()->create(['app_id' => $app->id]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('stats.activeApps', 2)
        ->where('stats.apiCalls30d', 10)
        ->where('stats.verifications30d', 4)
        ->where('stats.activeAlerts', 1));
});

test('dashboard shows the most recent activity events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    ActivityEvent::factory()->count(8)->create(['app_id' => $app->id]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('recentActivity.data', 5));
});

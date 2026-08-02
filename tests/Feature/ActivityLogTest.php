<?php

use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\App;
use App\Models\User;

test('guests are redirected from the activity log', function () {
    $this->get(route('dashboard.activity'))->assertRedirect(route('login'));
});

test('activity log is paginated server-side', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    ActivityEvent::factory()->count(20)->create(['app_id' => $app->id]);

    $response = $this->get(route('dashboard.activity'));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/activity')
        ->has('events.data', 15)
        ->where('events.meta.total', 20)
        ->where('events.meta.per_page', 15));
});

test('activity log can be filtered by event type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    ActivityEvent::factory()->count(3)->ofType(ActivityEventType::KeyRotation)->create(['app_id' => $app->id]);
    ActivityEvent::factory()->count(2)->ofType(ActivityEventType::FailedVerification)->create(['app_id' => $app->id]);

    $response = $this->get(route('dashboard.activity', ['type' => 'failed_verification']));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/activity')
        ->where('events.meta.total', 2)
        ->where('filters.type', 'failed_verification'));
});

test('activity log can be filtered by date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $app = App::factory()->create(['user_id' => $user->id]);

    ActivityEvent::factory()->count(2)->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(40),
    ]);

    ActivityEvent::factory()->count(3)->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->get(route('dashboard.activity', [
        'from' => now()->subDays(10)->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
    ]));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/activity')
        ->where('events.meta.total', 3));
});

test('activity log only shows events for the authenticated user apps', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $otherApp = App::factory()->create(['user_id' => $otherUser->id]);

    ActivityEvent::factory()->count(4)->create(['app_id' => $otherApp->id]);

    $response = $this->get(route('dashboard.activity'));

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/activity')
        ->where('events.meta.total', 0));
});

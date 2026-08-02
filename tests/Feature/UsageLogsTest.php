<?php

use App\Enums\AgeQueryType;
use App\Models\ApiCall;
use App\Models\App;
use App\Models\User;
use App\Models\Verification;

use function Pest\Laravel\actingAs;

test('the usage page shows aggregated metrics for the range', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(10)->success()->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(2),
    ]);
    ApiCall::factory()->count(2)->failed()->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(2),
    ]);
    Verification::factory()->count(4)->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
        'created_at' => now()->subDays(2),
    ]);
    actingAs($user);

    $this->get(route('apps.usage', [$app, 'range' => '7']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/usage')
            ->where('metrics.total_calls', 12)
            ->where('metrics.total_verifications', 4)
            ->where('metrics.success_rate', round(10 / 12 * 100, 1))
            ->where('metrics.most_used_query', '18+')
            ->has('metrics.daily', 1)
            ->has('metrics.by_query'));
});

test('the usage page aggregates by query type for the chart', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(3)->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
        'created_at' => now()->subDay(),
    ]);
    ApiCall::factory()->count(2)->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualBelow,
        'query_value' => 65,
        'created_at' => now()->subDay(),
    ]);
    actingAs($user);

    $this->get(route('apps.usage', [$app, 'range' => '30']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('metrics.by_query.0.query', '18+')
            ->where('metrics.by_query.0.calls', 3));
});

test('the raw logs page lists calls with filters and payloads', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->count(3)->success()->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
        'created_at' => now()->subDay(),
    ]);
    ApiCall::factory()->failed()->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualBelow,
        'query_value' => 65,
        'created_at' => now()->subDay(),
    ]);
    actingAs($user);

    $this->get(route('apps.logs', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/logs')
            ->has('calls.data', 4)
            ->where('calls.data.0.age_query', fn ($value) => is_string($value))
            ->where('calls.data.0.request_payload', fn ($value) => is_array($value) || is_object($value)));

    $this->get(route('apps.logs', [$app, 'status' => 'failed']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('calls.data', 1));

    $this->get(route('apps.logs', [$app, 'query_type' => 'equal_above']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('calls.data', 3));
});

test('logs are filtered by date range', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    ApiCall::factory()->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(5),
    ]);
    ApiCall::factory()->create([
        'app_id' => $app->id,
        'created_at' => now()->subDays(60),
    ]);
    actingAs($user);

    $this->get(route('apps.logs', [
        'app' => $app->id,
        'from' => now()->subDays(10)->toDateString(),
        'to' => now()->toDateString(),
    ]))->assertOk()->assertInertia(fn ($page) => $page->has('calls.data', 1));
});

test('another user cannot view usage or logs', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $owner->id]);
    actingAs($other);

    $this->get(route('apps.usage', $app))->assertNotFound();
    $this->get(route('apps.logs', $app))->assertNotFound();
});

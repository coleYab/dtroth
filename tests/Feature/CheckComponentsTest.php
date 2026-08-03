<?php

use App\Contracts\IdentityProvider;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentName;
use App\Models\StatusCheck;
use App\Services\IdentityProviderRegistry;
use Illuminate\Support\Facades\Http;

test('the status:check command records a down check when a component is unreachable', function () {
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $this->artisan('status:check')->assertSuccessful();

    $check = StatusCheck::query()->where('component', config('status.components.0.key'))->latest('checked_at')->first();

    expect($check->status)->toBe(ComponentStatus::Down)
        ->and($check->component)->toBe(config('status.components.0.key'))
        ->and($check->checked_at)->not->toBeNull();
});

test('the status:check command records an ok check for a healthy component', function () {
    Http::fake([
        '/api/v1/verify/age' => Http::response(null, 401),
        '*' => Http::response(null, 200),
    ]);

    $this->artisan('status:check')->assertSuccessful();

    expect(StatusCheck::query()->where('status', ComponentStatus::Ok)->count())->toBeGreaterThan(0);
});

test('the status:check command treats an unexpected status code as down', function () {
    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    $this->artisan('status:check')->assertSuccessful();

    expect(StatusCheck::query()->where('status', ComponentStatus::Down)->count())->toBe(count(config('status.components')));
});

test('the status:check command records response time for healthy components', function () {
    Http::fake([
        '/api/v1/verify/age' => Http::response(null, 401),
        '*' => Http::response(null, 200),
    ]);

    $this->artisan('status:check')->assertSuccessful();

    $componentCount = count(config('status.components'));

    expect(StatusCheck::query()->where('status', ComponentStatus::Ok)->count())
        ->toBe($componentCount + 1)
        ->and(StatusCheck::query()->whereNotNull('response_time_ms')->count())
        ->toBe($componentCount);
});

test('the status:check command records an ok check for the identity provider', function () {
    Http::fake([
        '*' => Http::response(null, 200),
    ]);

    $this->artisan('status:check')->assertSuccessful();

    $check = StatusCheck::query()->where('component', 'identity_provider')->latest('checked_at')->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe(ComponentStatus::Ok);
});

test('the status:check command records a down check when the identity provider is unavailable', function () {
    Http::fake([
        '*' => Http::response(null, 200),
    ]);

    $unavailable = Mockery::mock(IdentityProvider::class, function ($mock): void {
        $mock->shouldReceive('isAvailable')->andReturn(false);
        $mock->shouldReceive('name')->andReturn('mock_fayda');
    });

    $registry = Mockery::mock(IdentityProviderRegistry::class, function ($mock) use ($unavailable): void {
        $mock->shouldReceive('resolve')->with(EnvironmentName::Production)->andReturn($unavailable);
    });

    $this->app->instance(IdentityProviderRegistry::class, $registry);

    $this->artisan('status:check')->assertSuccessful();

    $check = StatusCheck::query()->where('component', 'identity_provider')->latest('checked_at')->first();

    expect($check)->not->toBeNull()
        ->and($check->status)->toBe(ComponentStatus::Down);
});

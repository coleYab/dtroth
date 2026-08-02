<?php

use App\Models\StatusCheck;
use App\Models\StatusIncident;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->create());
});

test('the status page reports all systems operational when every component is ok', function () {
    foreach (config('status.components') as $component) {
        StatusCheck::factory()->ok()->create(['component' => $component['key']]);
    }

    $this->get(route('status.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('status')
            ->where('overall.tone', 'ok')
            ->where('overall.label', 'All Systems Operational')
            ->has('components', count(config('status.components')))
            ->where('components.0.status', 'ok'));
});

test('the status page reports degraded performance when a component is degraded', function () {
    foreach (config('status.components') as $component) {
        StatusCheck::factory()->ok()->create(['component' => $component['key']]);
    }

    StatusCheck::factory()->degraded()->on(now())->create(['component' => 'verification-api']);

    $this->get(route('status.index'))
        ->assertInertia(fn ($page) => $page
            ->where('overall.tone', 'degraded')
            ->where('overall.label', 'Degraded Performance'));
});

test('the status page reports a major outage when a component is down', function () {
    StatusCheck::factory()->down()->on(now())->create(['component' => 'verification-api']);

    $this->get(route('status.index'))
        ->assertInertia(fn ($page) => $page
            ->where('overall.tone', 'down')
            ->where('overall.label', 'Major Outage'));
});

test('uptime percentages are computed from the last 30 days of checks', function () {
    for ($i = 0; $i < 90; $i++) {
        StatusCheck::factory()->ok()->on(now()->subDays($i))->create(['component' => 'verification-api']);
    }

    StatusCheck::factory()->degraded()->on(now()->subDays(10))->create(['component' => 'verification-api']);

    $this->get(route('status.index'))
        ->assertInertia(fn ($page) => $page
            ->where('components.0.uptime30', fn ($value) => is_float($value) && $value < 100)
            ->where('components.0.uptime90', fn ($value) => is_float($value) && $value > 98));
});

test('uptime is null when there are no recent checks', function () {
    $this->get(route('status.index'))
        ->assertInertia(fn ($page) => $page->where('components.0.uptime30', null));
});

test('resolved incidents render with their update timelines newest first', function () {
    $incident = StatusIncident::factory()->resolved()->create([
        'title' => 'Elevated latency',
        'component' => 'verification-api',
    ]);

    $this->get(route('status.index'))
        ->assertInertia(fn ($page) => $page
            ->has('incidents', 1)
            ->where('incidents.0.title', 'Elevated latency')
            ->where('incidents.0.resolvedAt', fn ($value) => is_string($value))
            ->has('incidents.0.updates', 4)
            ->where('incidents.0.updates.0.status', 'resolved')
            ->where('incidents.0.updates.3.status', 'investigating')
            ->where('incidents.0.updates.0.createdAt', fn ($value) => is_string($value)));
});

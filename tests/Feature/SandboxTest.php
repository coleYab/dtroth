<?php

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\SandboxIdentity;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);

    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);
    SandboxIdentity::create(['key' => 'fayda-test-b', 'label' => 'Test User B', 'age' => 16]);

    $this->developerApp = App::factory()->create(['user_id' => $this->user->id]);
    $this->sandboxEnvironment = $this->developerApp->environments()->create(['name' => EnvironmentName::Sandbox]);

    ApiKey::factory()->forEnvironment($this->sandboxEnvironment)->create([
        'app_id' => $this->developerApp->id,
        'name' => 'Sandbox key',
    ]);
});

test('the sandbox page lists the apps and test identities', function () {
    $this->get(route('sandbox.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sandbox')
            ->has('apps', 1)
            ->where('apps.0.name', $this->developerApp->name)
            ->has('identities', 2)
            ->where('identities.0.key', 'fayda-test-b')
            ->where('identities.1.key', 'fayda-test-a')
            ->where('rateLimit.limit', 30)
            ->where('outcome', null));
});

test('the sandbox page only lists apps owned by the developer', function () {
    App::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->get(route('sandbox.index'))
        ->assertInertia(fn ($page) => $page->has('apps', 1));
});

test('sending a sandbox request returns a live verification result', function () {
    $preset = AgeQueryPreset::factory()->create(['type' => AgeQueryType::EqualAbove, 'value' => 18]);
    $this->developerApp->ageQueryPresets()->attach($preset);

    $this->post(route('sandbox.verify'), [
        'app_id' => $this->developerApp->id,
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sandbox')
            ->where('outcome.statusCode', 200)
            ->where('outcome.payload.result', true)
            ->where('outcome.payload.query_id', fn ($value) => is_string($value)));

    expect(ApiCall::query()->latest('id')->first())->not->toBeNull();
});

test('a failed sandbox request explains the preset error', function () {
    $this->post(route('sandbox.verify'), [
        'app_id' => $this->developerApp->id,
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outcome.statusCode', 403)
            ->where('outcome.payload.error.code', 'preset_not_allowed'));
});

test('another developers app cannot be used in the sandbox', function () {
    $other = App::factory()->create();
    $other->environments()->create(['name' => EnvironmentName::Sandbox]);

    $this->post(route('sandbox.verify'), [
        'app_id' => $other->id,
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ])->assertSessionHasErrors('app_id');
});

test('sandbox requests are rate limited per developer', function () {
    config(['sandbox.requests_per_minute' => 2]);

    $preset = AgeQueryPreset::factory()->create(['type' => AgeQueryType::EqualAbove, 'value' => 18]);
    $this->developerApp->ageQueryPresets()->attach($preset);

    $payload = [
        'app_id' => $this->developerApp->id,
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'fayda-test-a',
    ];

    $this->post(route('sandbox.verify'), $payload)->assertOk();
    $this->post(route('sandbox.verify'), $payload)->assertOk();

    $this->post(route('sandbox.verify'), $payload)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outcome.statusCode', 429)
            ->where('outcome.payload.error.code', 'too_many_requests')
            ->where('rateLimit.retryAfter', fn ($value) => is_int($value)));
});

test('an unknown test identity is rejected with a friendly error', function () {
    $this->post(route('sandbox.verify'), [
        'app_id' => $this->developerApp->id,
        'age' => 18,
        'type' => 'equal_above',
        'subject' => 'nope',
    ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outcome.statusCode', 404)
            ->where('outcome.payload.error.code', 'unknown_subject'));
});

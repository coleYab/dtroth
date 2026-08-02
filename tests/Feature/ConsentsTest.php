<?php

use App\Enums\AgeQueryType;
use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('the consents page lists consents with counts', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    Consent::factory()->count(3)->withEvents()->create(['app_id' => $app->id]);
    Consent::factory()->revoked()->create(['app_id' => $app->id]);
    Consent::factory()->expired()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->get(route('apps.consents.index', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/consents/index')
            ->where('counts.active', 3)
            ->where('counts.revoked', 1)
            ->where('counts.expired', 1)
            ->has('consents.data', 5));
});

test('consents never expose raw date of birth values', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    Consent::factory()->withEvents()->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 18,
    ]);
    actingAs($user);

    $consent = $app->consents()->first();

    $this->get(route('apps.consents.index', $app))
        ->assertInertia(fn ($page) => $page
            ->where('consents.data.0.age_query', '18+')
            ->where('consents.data.0.result', fn ($value) => is_bool($value)));
});

test('consents can be filtered by status', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    Consent::factory()->count(2)->withEvents()->create(['app_id' => $app->id]);
    Consent::factory()->revoked()->create(['app_id' => $app->id]);
    actingAs($user);

    $this->get(route('apps.consents.index', [$app, 'status' => 'revoked']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('consents.data', 1)
            ->where('consents.data.0.status', 'revoked'));
});

test('the consent detail page shows the event timeline', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $consent = Consent::factory()->revoked()->withEvents()->create([
        'app_id' => $app->id,
        'query_type' => AgeQueryType::EqualAbove,
        'query_value' => 21,
    ]);
    ConsentEvent::factory()->revoked()->create([
        'consent_id' => $consent->id,
        'created_at' => now()->subDay(),
    ]);
    actingAs($user);

    $this->get(route('apps.consents.show', [$app, $consent]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/consents/show')
            ->where('consent.age_query', '21+')
            ->where('consent.status', 'revoked')
            ->has('consent.events', 2)
            ->where('consent.events.1.type', 'revoked'));
});

test('consent statuses derive from revocation and expiry', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $active = Consent::factory()->create(['app_id' => $app->id]);
    $revoked = Consent::factory()->revoked()->create(['app_id' => $app->id]);
    $expired = Consent::factory()->expired()->create(['app_id' => $app->id]);
    actingAs($user);

    expect($active->status)->toBe(Consent::STATUS_ACTIVE)
        ->and($active->isRevoked())->toBeFalse()
        ->and($revoked->isRevoked())->toBeTrue()
        ->and($expired->isExpired())->toBeTrue()
        ->and($expired->status)->toBe(Consent::STATUS_EXPIRED);
});

test('another user cannot view the app consents', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $owner->id]);
    $consent = Consent::factory()->create(['app_id' => $app->id]);
    actingAs($other);

    $this->get(route('apps.consents.index', $app))->assertNotFound();
    $this->get(route('apps.consents.show', [$app, $consent]))->assertNotFound();
});

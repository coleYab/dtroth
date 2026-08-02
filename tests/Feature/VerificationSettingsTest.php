<?php

use App\Models\AgeQueryPreset;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\VerificationSetting;

use function Pest\Laravel\actingAs;

test('the verification settings page shows defaults and presets', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $preset = AgeQueryPreset::factory()->create();
    $app->ageQueryPresets()->attach($preset);
    actingAs($user);

    $this->get(route('apps.verification-settings.show', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/verification-settings')
            ->where('settings.re_verify_months', 6)
            ->where('settings.consent_text', VerificationSetting::DEFAULT_CONSENT_TEXT)
            ->has('presets', 1)
            ->where('presets.0.is_enabled', true));
});

test('users can update consent text, interval, and preset toggles', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $preset = AgeQueryPreset::factory()->create();
    actingAs($user);

    $this->patch(route('apps.verification-settings.update', $app), [
        'consent_text' => 'Custom consent copy',
        're_verify_months' => 12,
        'presets' => [$preset->id],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($app->fresh()->verificationSetting->consent_text)->toBe('Custom consent copy')
        ->and($app->fresh()->verificationSetting->re_verify_months)->toBe(12)
        ->and($app->fresh()->ageQueryPresets()->pluck('age_query_presets.id'))->toContain($preset->id);
});

test('preset toggles can be cleared and audit logged', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    $preset = AgeQueryPreset::factory()->create();
    $app->ageQueryPresets()->attach($preset);
    actingAs($user);

    $this->patch(route('apps.verification-settings.update', $app), [
        'consent_text' => null,
        're_verify_months' => 3,
        'presets' => [],
    ])->assertRedirect();

    expect($app->fresh()->ageQueryPresets()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_VERIFICATION_CHANGED)->count())->toBe(1);
});

test('re-verification interval is restricted to 3, 6, or 12 months', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->patch(route('apps.verification-settings.update', $app), [
        'consent_text' => null,
        're_verify_months' => 7,
        'presets' => [],
    ])->assertSessionHasErrors('re_verify_months');
});

test('another user cannot view or edit the app verification settings', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $owner->id]);
    actingAs($other);

    $this->get(route('apps.verification-settings.show', $app))->assertNotFound();
    $this->patch(route('apps.verification-settings.update', $app), [
        'consent_text' => null,
        're_verify_months' => 6,
        'presets' => [],
    ])->assertNotFound();
});

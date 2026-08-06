<?php

use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;

test('consents past their expiry are marked expired with an event', function () {
    $app = App::factory()->create();

    $expiring = Consent::factory()->active()->create([
        'app_id' => $app->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('consents:expire')
        ->expectsOutputToContain('[consents:expire] Expired 1 consents')
        ->assertExitCode(0);

    $expiring->refresh();

    expect($expiring->status)->toBe(Consent::STATUS_EXPIRED)
        ->and($expiring->isExpired())->toBeTrue()
        ->and(ConsentEvent::query()->where('consent_id', $expiring->id)->where('type', ConsentEvent::TYPE_EXPIRED)->count())->toBe(1);
});

test('active consents within their window are left untouched', function () {
    $app = App::factory()->create();

    Consent::factory()->active()->create([
        'app_id' => $app->id,
        'expires_at' => now()->addMonth(),
    ]);

    $this->artisan('consents:expire')
        ->expectsOutputToContain('[consents:expire] Expired 0 consents')
        ->assertExitCode(0);

    expect(Consent::query()->where('status', Consent::STATUS_EXPIRED)->count())->toBe(0);
});

test('already expired consents are not processed again', function () {
    $app = App::factory()->create();

    $expired = Consent::factory()->expired()->create([
        'app_id' => $app->id,
        'expires_at' => now()->subDays(10),
    ]);

    $this->artisan('consents:expire')->assertExitCode(0);

    expect(ConsentEvent::query()->where('consent_id', $expired->id)->where('type', ConsentEvent::TYPE_EXPIRED)->count())->toBe(0);
});

test('revoked consents are never expired', function () {
    $app = App::factory()->create();

    $revoked = Consent::factory()->revoked()->create([
        'app_id' => $app->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('consents:expire')->assertExitCode(0);

    expect($revoked->fresh()->isRevoked())->toBeTrue()
        ->and(ConsentEvent::query()->where('consent_id', $revoked->id)->where('type', ConsentEvent::TYPE_EXPIRED)->count())->toBe(0);
});

test('the command is scheduled hourly without overlapping', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('consents:expire');
});

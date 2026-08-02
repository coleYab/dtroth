<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

test('the verification flows page shows branding defaults', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('apps.verification-flows.show', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('apps/verification-flows')
            ->where('flow.brand_color', '#0f172a')
            ->where('flow.logo_url', null));
});

test('users can save branding color and redirect URLs', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->patch(route('apps.verification-flows.update', $app), [
        'brand_color' => '#2563eb',
        'success_redirect_url' => 'https://app.example.com/success',
        'failure_redirect_url' => 'https://app.example.com/failure',
        'callback_url' => 'https://app.example.com/hooks/verification',
    ])->assertRedirect();

    $flow = $app->fresh()->verificationFlow;

    expect($flow->brand_color)->toBe('#2563EB')
        ->and($flow->success_redirect_url)->toBe('https://app.example.com/success')
        ->and($flow->callback_url)->toBe('https://app.example.com/hooks/verification');
});

test('brand color must be a valid hex value', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->patch(route('apps.verification-flows.update', $app), [
        'brand_color' => 'blue',
        'success_redirect_url' => null,
        'failure_redirect_url' => null,
        'callback_url' => null,
    ])->assertSessionHasErrors('brand_color');
});

test('redirect URLs must be http or https URLs', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->patch(route('apps.verification-flows.update', $app), [
        'brand_color' => '#0f172a',
        'success_redirect_url' => 'ftp://nope.example.com',
        'failure_redirect_url' => null,
        'callback_url' => null,
    ])->assertSessionHasErrors('success_redirect_url');
});

test('users can upload a logo for the verification flow', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->patch(route('apps.verification-flows.update', $app), [
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        'brand_color' => '#0f172a',
        'success_redirect_url' => null,
        'failure_redirect_url' => null,
        'callback_url' => null,
    ])->assertRedirect();

    $flow = $app->fresh()->verificationFlow;

    expect($flow->logo_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($flow->logo_path))->toBeTrue();
});

test('another user cannot access the verification flows page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = App::factory()->create(['user_id' => $owner->id]);
    actingAs($other);

    $this->get(route('apps.verification-flows.show', $app))->assertNotFound();
    $this->patch(route('apps.verification-flows.update', $app), [
        'brand_color' => '#0f172a',
        'success_redirect_url' => null,
        'failure_redirect_url' => null,
        'callback_url' => null,
    ])->assertNotFound();
});

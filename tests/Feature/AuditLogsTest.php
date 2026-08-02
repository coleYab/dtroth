<?php

use App\Models\ApiKey;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Webhook;

use function Pest\Laravel\actingAs;

test('the audit log page lists account-wide entries newest first', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    AuditLog::factory()->count(25)->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('audit-logs')
            ->has('logs.data', 20)
            ->where('logs.data.0.created_at', fn ($value) => is_string($value)));
});

test('audit logs are created when apps and keys change', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    ApiKey::factory()->create(['app_id' => $app->id]);

    $key = $app->keys()->first();
    $key->update(['secret_hash' => str_repeat('a', 64), 'secret_preview' => 'sk_••••a']);

    $app->delete();

    expect(AuditLog::query()->where('action', AuditLog::ACTION_KEY_CREATED)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_KEY_ROTATED)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditLog::ACTION_APP_DELETED)->count())->toBe(1);
});

test('audit logs are created when webhooks change', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $webhook = Webhook::factory()->create(['app_id' => $app->id]);
    $webhook->update(['url' => 'https://example.com/changed']);
    $webhook->delete();

    expect(AuditLog::query()->where('action', AuditLog::ACTION_WEBHOOK_CHANGED)->count())->toBe(3);
});

test('audit logs can be filtered by action and app', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    AuditLog::factory()->ofAction(AuditLog::ACTION_KEY_CREATED)->forApp($app)->create(['user_id' => $user->id]);
    AuditLog::factory()->ofAction(AuditLog::ACTION_APP_CREATED)->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('audit-logs.index', ['action' => AuditLog::ACTION_KEY_CREATED]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('logs.data', 1));

    $this->get(route('audit-logs.index', ['app' => $app->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('logs.data', 1));
});

test('audit logs can be exported as csv', function () {
    $user = User::factory()->create();
    AuditLog::factory()->count(3)->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('audit-logs.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});

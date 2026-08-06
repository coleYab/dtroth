<?php

use App\Enums\TeamRole;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\UsageDailyRollup;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Notifications\ApiKeyExpiring;
use App\Notifications\InvoiceFailed;
use App\Notifications\UsageThresholdReached;
use App\Notifications\WebhookFailing;
use App\Services\NotificationGate;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->team = Team::factory()->withOwner()->create([
        'owner_user_id' => $this->owner->id,
    ]);
    $this->appModel = App::factory()->create([
        'user_id' => $this->owner->id,
    ]);
});

function notificationCount(User $user, string $type): int
{
    return $user->notifications()->where('type', $type)->count();
}

test('the notification preferences page lists every type enabled by default', function () {
    actingAs($this->owner);

    $this->get(route('notifications.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/notifications')
            ->has('types', 4)
            ->where('types.0.key', 'webhook_failing')
            ->where('types.0.enabled', true));
});

test('users can update their notification preferences', function () {
    actingAs($this->owner);

    $this->patch(route('notifications.update'), [
        'preferences' => ['webhook_failing' => false],
    ])->assertStatus(302);

    expect($this->owner->fresh()->notification_preferences)->toBe(['webhook_failing' => false]);

    $this->patch(route('notifications.update'), [
        'preferences' => ['webhook_failing' => true, 'key_expiring' => false],
    ])->assertStatus(302);

    expect($this->owner->fresh()->notification_preferences)->toBe([
        'webhook_failing' => true,
        'key_expiring' => false,
    ]);
});

test('the notification gate respects preferences and deduplicates within 24 hours', function () {
    $webhook = Webhook::factory()->create(['app_id' => $this->appModel->id]);
    $gate = app(NotificationGate::class);

    expect($gate->send($this->owner, new WebhookFailing($webhook), ['webhook_id' => $webhook->id]))->toBeTrue();

    expect(notificationCount($this->owner, WebhookFailing::class))->toBe(1);

    expect($gate->send($this->owner, new WebhookFailing($webhook), ['webhook_id' => $webhook->id]))->toBeFalse();

    expect(notificationCount($this->owner, WebhookFailing::class))->toBe(1);

    $this->owner->update(['notification_preferences' => ['webhook_failing' => false]]);

    expect($gate->send($this->owner, new WebhookFailing($webhook), ['webhook_id' => $webhook->id]))->toBeFalse();
});

test('the webhook failure command notifies accepted members once per window', function () {
    $developer = makeTeamMember($this->team, TeamRole::Developer);
    $viewer = makeTeamMember($this->team, TeamRole::Viewer);
    $pending = TeamMember::factory()->pending()->create([
        'team_id' => $this->team->id,
        'user_id' => User::factory(),
    ]);
    $webhook = Webhook::factory()->create(['app_id' => $this->appModel->id]);

    WebhookDelivery::factory()->create([
        'webhook_id' => $webhook->id,
        'status' => 'failed',
        'created_at' => now()->subHour(),
    ]);

    $this->artisan('notify:webhook-failures')->assertSuccessful();

    expect(notificationCount($this->owner, WebhookFailing::class))->toBe(1)
        ->and(notificationCount($developer, WebhookFailing::class))->toBe(1)
        ->and(notificationCount($viewer, WebhookFailing::class))->toBe(0);

    $pending->delete();

    $this->artisan('notify:webhook-failures')->assertSuccessful();

    expect(notificationCount($this->owner, WebhookFailing::class))->toBe(1)
        ->and(notificationCount($developer, WebhookFailing::class))->toBe(1);
});

test('healthy webhooks do not trigger the failure notification', function () {
    $webhook = Webhook::factory()->create(['app_id' => $this->appModel->id]);

    WebhookDelivery::factory()->create([
        'webhook_id' => $webhook->id,
        'status' => 'success',
    ]);

    $this->artisan('notify:webhook-failures')->assertSuccessful();

    expect(notificationCount($this->owner, WebhookFailing::class))->toBe(0);
});

test('the key expiry command notifies about keys expiring within 7 days', function () {
    ApiKey::factory()->create([
        'app_id' => $this->appModel->id,
        'expires_at' => now()->addDays(3),
    ]);
    ApiKey::factory()->create([
        'app_id' => $this->appModel->id,
        'expires_at' => now()->addDays(30),
    ]);
    ApiKey::factory()->create([
        'app_id' => $this->appModel->id,
        'expires_at' => now()->addDays(2),
        'revoked_at' => now(),
    ]);

    $this->artisan('notify:key-expiry')->assertSuccessful();

    expect(notificationCount($this->owner, ApiKeyExpiring::class))->toBe(1);
});

test('the usage threshold command notifies when a threshold is crossed', function () {
    UsageDailyRollup::factory()->create([
        'app_id' => $this->appModel->id,
        'date' => today()->toDateString(),
        'calls_total' => 500,
    ]);
    UsageDailyRollup::factory()->create([
        'app_id' => $this->appModel->id,
        'date' => today()->subDay()->toDateString(),
        'calls_total' => 300,
    ]);

    $this->artisan('notify:usage-thresholds')->assertSuccessful();

    expect(notificationCount($this->owner, UsageThresholdReached::class))->toBe(1)
        ->and($this->owner->notifications()->where('type', UsageThresholdReached::class)->first()->data['threshold'])->toBe(80);
});

test('the usage threshold command notifies for every crossed threshold', function () {
    UsageDailyRollup::factory()->create([
        'app_id' => $this->appModel->id,
        'date' => today()->toDateString(),
        'calls_total' => 1000,
    ]);

    $this->artisan('notify:usage-thresholds')->assertSuccessful();

    $thresholds = $this->owner->notifications()
        ->where('type', UsageThresholdReached::class)
        ->get()
        ->map(fn ($notification) => $notification->data['threshold'])
        ->sort()
        ->values()
        ->all();

    expect($thresholds)->toBe([80, 100]);
});

test('a failed chapa verification marks the invoice failed and notifies the owner once', function () {
    config(['services.chapa.secret' => 'test-secret']);

    Http::fake([
        'api.chapa.co/*' => Http::response([
            'data' => ['status' => 'failed'],
        ], 200),
    ]);

    $invoice = Invoice::factory()->pending()->create([
        'user_id' => $this->owner->id,
        'tx_ref' => 'tx-failed-123',
    ]);

    $this->post(route('billing.chapa.webhook'), [
        'tx_ref' => $invoice->tx_ref,
    ])->assertOk()->assertJson(['received' => true]);

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_FAILED)
        ->and(notificationCount($this->owner, InvoiceFailed::class))->toBe(1);

    $this->post(route('billing.chapa.webhook'), [
        'tx_ref' => $invoice->tx_ref,
    ])->assertOk();

    expect(notificationCount($this->owner, InvoiceFailed::class))->toBe(1);
});

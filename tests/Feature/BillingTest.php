<?php

use App\Models\App;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

test('the billing page shows the current plan and invoices', function () {
    $user = User::factory()->create();
    Subscription::factory()->active()->create(['user_id' => $user->id]);
    Invoice::factory()->count(2)->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->where('current_plan', 'pro')
            ->where('plans.1.is_current', true)
            ->has('invoices', 2)
            ->where('invoices.0.status', Invoice::STATUS_PAID));
});

test('a new user sees the trial state', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('current_plan', 'trial')
            ->where('subscription.status', 'trial')
            ->where('subscription.has_payment_method', false));
});

test('upgrading initializes a chapa checkout and redirects to it', function () {
    config(['services.chapa.secret' => 'test-secret']);

    Http::fake([
        'api.chapa.co/*' => Http::response([
            'message' => 'New transaction initiated',
            'status' => 'success',
            'data' => ['checkout_url' => 'https://checkout.chapa.co/session/abc123'],
        ]),
    ]);

    $user = User::factory()->create();
    actingAs($user);

    $this->from(route('billing.index'))
        ->post(route('billing.upgrade'), ['plan' => 'pro'])
        ->assertRedirect('https://checkout.chapa.co/session/abc123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.chapa.co/v1/transaction/initialize');

    expect($user->fresh()->subscription)->not->toBeNull()
        ->and($user->fresh()->subscription->plan)->toBe('pro')
        ->and($user->fresh()->invoices()->first()->status)->toBe(Invoice::STATUS_PENDING);
});

test('upgrading is blocked when chapa is not configured', function () {
    config(['services.chapa.secret' => null]);

    $user = User::factory()->create();
    actingAs($user);

    $this->post(route('billing.upgrade'), ['plan' => 'pro'])->assertStatus(503);
});

test('the chapa callback verifies the transaction and activates the subscription', function () {
    Http::fake([
        'api.chapa.co/*' => Http::response([
            'message' => 'Transaction verified',
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'channel' => 'telebirr',
                'amount' => '1499.00',
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'tx_ref' => 'tx_callback_test',
        'status' => Subscription::STATUS_TRIAL,
        'plan' => 'starter',
    ]);
    $invoice = Invoice::factory()->pending()->create([
        'user_id' => $user->id,
        'tx_ref' => 'tx_callback_test',
    ]);
    actingAs($user);

    $this->get(route('billing.chapa.callback', ['trx_ref' => 'tx_callback_test']))
        ->assertRedirect(route('billing.index'));

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID)
        ->and($invoice->fresh()->channel)->toBe('telebirr')
        ->and($subscription->fresh()->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($subscription->fresh()->renews_at)->not->toBeNull();
});

test('the chapa webhook marks failed transactions as failed invoices', function () {
    Http::fake([
        'api.chapa.co/*' => Http::response([
            'status' => 'success',
            'data' => ['status' => 'failed', 'channel' => 'card'],
        ]),
    ]);

    $user = User::factory()->create();
    $invoice = Invoice::factory()->pending()->create([
        'user_id' => $user->id,
        'tx_ref' => 'tx_webhook_failed',
    ]);

    $this->postJson(route('billing.chapa.webhook'), ['tx_ref' => 'tx_webhook_failed'])
        ->assertOk();

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_FAILED);
});

test('the billing usage page shows the per-app breakdown', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['user_id' => $user->id]);
    Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'renews_at' => now()->addDays(10)->addHours(5),
    ]);
    actingAs($user);

    $this->get(route('billing.usage'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/usage')
            ->where('apps.0.app_id', $app->id)
            ->where('cycle.total_calls', 0)
            ->where('cycle.estimated_cost_etb', 0)
            ->where('cycle.days_remaining', 11));
});

test('the billing usage page rounds days remaining up for trial users', function () {
    $user = User::factory()->create(['created_at' => now()->subHours(6)]);
    actingAs($user);

    $this->get(route('billing.usage'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/usage')
            ->where('cycle.days_remaining', 14)
            ->where('cycle.plan_calls', 1000));
});

test('users can download their own invoices as pdf', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    actingAs($user);

    $this->get(route('billing.invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('users cannot download another user invoice', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $owner->id]);
    actingAs($other);

    $this->get(route('billing.invoices.download', $invoice))->assertForbidden();
});

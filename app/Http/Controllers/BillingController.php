<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\ChapaService;
use App\Services\UsageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    /**
     * Show the billing page with plan, payment method, and invoices.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $subscription = $user->subscription;
        $currentPlan = $subscription?->plan ?? Subscription::PLAN_TRIAL;

        $invoices = $user->invoices()
            ->latest('created_at')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'amount' => $invoice->formattedAmount(),
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'channel' => $invoice->channel,
                'paid_at' => $invoice->paid_at?->toISOString(),
                'created_at' => $invoice->created_at?->toISOString(),
            ]);

        return Inertia::render('billing/index', [
            'plans' => collect(config('billing.plans'))
                ->reject(fn (array $plan, string $slug) => $slug === Subscription::PLAN_TRIAL)
                ->map(fn (array $plan, string $slug) => [
                    'slug' => $slug,
                    'name' => $plan['name'],
                    'price_etb' => $plan['price_etb'],
                    'description' => $plan['description'],
                    'is_current' => $slug === $currentPlan,
                    'price_formatted' => 'ETB '.number_format($plan['price_etb'] / 100, 0),
                ])
                ->sortBy(fn (array $plan) => $plan['price_etb'])
                ->values(),
            'current_plan' => $currentPlan,
            'subscription' => [
                'status' => $subscription?->status ?? Subscription::STATUS_TRIAL,
                'channel' => $subscription?->channel,
                'renews_at' => $subscription?->renews_at?->toISOString(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toISOString() ?? $user->created_at->addDays(config('billing.trial_days'))->toISOString(),
                'has_payment_method' => $subscription?->channel !== null,
                'payment_method' => $this->paymentMethodLabel($subscription?->channel),
            ],
            'invoices' => $invoices,
            'chapa_configured' => app(ChapaService::class)->isConfigured(),
        ]);
    }

    /**
     * Show the billing usage breakdown for the current cycle.
     */
    public function usage(Request $request, UsageService $usage): Response
    {
        $user = $request->user();

        $subscription = $user->subscription;
        $trialEnd = $user->created_at->addDays(config('billing.trial_days'));
        $periodStart = $subscription?->renews_at?->subMonth() ?? $user->created_at;
        $periodEnd = $subscription?->renews_at ?? $trialEnd;

        $cycle = $usage->forUser($user, $periodStart, $periodEnd);
        $daysRemaining = max(0, (int) ceil(now()->diffInDays($periodEnd)));

        $planCalls = config('billing.plans.'.$this->currentPlan($user).'.calls_per_month', 0);

        return Inertia::render('billing/usage', [
            'cycle' => [
                'total_calls' => $cycle['total_calls'],
                'total_verifications' => $cycle['total_verifications'],
                'estimated_cost_etb' => $cycle['estimated_cost_etb'],
                'cost_formatted' => 'ETB '.number_format($cycle['estimated_cost_etb'] / 100, 2),
                'plan_calls' => $planCalls,
                'usage_percent' => $planCalls > 0 ? min(100, round($cycle['total_calls'] / $planCalls * 100, 1)) : 0,
                'days_remaining' => $daysRemaining,
                'period_start' => $periodStart->toISOString(),
                'period_end' => $periodEnd->toISOString(),
            ],
            'apps' => $cycle['per_app']->map(fn (array $app) => [
                'app_id' => $app['app_id'],
                'app_name' => $app['app_name'],
                'calls' => $app['calls'],
                'verifications' => $app['verifications'],
                'estimated_cost_etb' => $app['estimated_cost_etb'],
            ]),
        ]);
    }

    /**
     * Initialize a Chapa checkout for the chosen plan and redirect to it.
     */
    public function upgrade(Request $request, ChapaService $chapa): RedirectResponse
    {
        $user = $request->user();

        $plan = $request->string('plan')->toString();

        if (! isset(config('billing.plans')[$plan])) {
            abort(422, 'Unknown plan.');
        }

        if (! $chapa->isConfigured()) {
            abort(503, 'Billing is not connected yet.');
        }

        $amount = config('billing.plans')[$plan]['price_etb'];
        $txRef = 'tx_'.str()->uuid();

        $response = $chapa->initialize([
            'amount' => (string) $amount,
            'currency' => config('billing.currency', 'ETB'),
            'email' => $user->email,
            'first_name' => $user->name,
            'tx_ref' => $txRef,
            'callback_url' => route('billing.chapa.callback'),
            'return_url' => route('billing.index'),
            'customization' => [
                'title' => config('app.name').' — '.config('billing.plans')[$plan]['name'],
                'description' => 'Monthly subscription',
            ],
            'meta' => [
                'hide_receipt' => false,
                'invoices' => [
                    ['key' => config('billing.plans')[$plan]['name'].' plan', 'value' => '1 month'],
                ],
            ],
        ]);

        if ($response->failed() || ! $response->json('data.checkout_url')) {
            abort(502, 'Could not start payment checkout.');
        }

        $subscription = $user->subscription ?? $user->subscription()->create([
            'plan' => Subscription::PLAN_TRIAL,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(config('billing.trial_days')),
        ]);

        $subscription->update([
            'tx_ref' => $txRef,
            'plan' => $plan,
        ]);

        $user->invoices()->create([
            'number' => 'INV-'.now()->format('Y').'-'.str_pad((string) ($user->invoices()->count() + 1), 4, '0', STR_PAD_LEFT),
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => config('billing.currency', 'ETB'),
            'status' => Invoice::STATUS_PENDING,
        ]);

        return redirect()->away($response->json('data.checkout_url'));
    }

    /**
     * Handle the Chapa callback after payment, verifying server-side.
     */
    public function chapaCallback(Request $request, ChapaService $chapa): RedirectResponse
    {
        $txRef = $request->string('trx_ref')->toString();

        if ($txRef !== '') {
            $this->processTransaction($txRef, $chapa);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thank you! Your payment is being confirmed.'),
        ]);

        return redirect()->route('billing.index');
    }

    /**
     * Handle a Chapa webhook for transaction confirmation.
     */
    public function chapaWebhook(Request $request, ChapaService $chapa): JsonResponse
    {
        $txRef = $request->string('tx_ref')->toString();

        if ($txRef === '') {
            $txRef = $request->input('data.tx_ref', '');
        }

        if ($txRef !== '') {
            $this->processTransaction($txRef, $chapa);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Download an invoice as a PDF.
     */
    public function downloadInvoice(Request $request, Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        return $pdf->download('invoice-'.$invoice->number.'.pdf');
    }

    /**
     * Verify a transaction and finalize the subscription.
     */
    private function processTransaction(string $txRef, ChapaService $chapa): void
    {
        $response = $chapa->verify($txRef);

        if ($response->failed()) {
            return;
        }

        $data = $response->json('data', []);

        if (($data['status'] ?? '') !== 'success') {
            Invoice::query()->where('tx_ref', $txRef)->update(['status' => Invoice::STATUS_FAILED]);

            return;
        }

        $invoice = Invoice::query()->where('tx_ref', $txRef)->where('status', Invoice::STATUS_PENDING)->first();

        if ($invoice === null) {
            return;
        }

        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'channel' => $data['channel'] ?? null,
            'paid_at' => now(),
            'period_start' => now(),
            'period_end' => now()->addMonth(),
        ]);

        $subscription = $invoice->user->subscription;

        if ($subscription !== null) {
            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'channel' => $data['channel'] ?? null,
                'renews_at' => now()->addMonth(),
                'tx_ref' => null,
            ]);
        }
    }

    /**
     * The current plan slug for a user.
     */
    private function currentPlan($user): string
    {
        return $user->subscription?->plan ?? Subscription::PLAN_TRIAL;
    }

    /**
     * A readable label for a payment channel.
     */
    private function paymentMethodLabel(?string $channel): ?string
    {
        return match ($channel) {
            'card' => 'Card',
            'telebirr' => 'Telebirr',
            'cbe_birr' => 'CBE Birr',
            'awash_birr' => 'Awash Birr',
            'hello_cash' => 'HelloCash',
            null => null,
            default => ucfirst($channel),
        };
    }
}

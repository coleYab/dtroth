<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApiCallController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\AppEnvironmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DocsFeedbackController;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\SandboxController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\VerificationFlowController;
use App\Http\Controllers\VerificationSettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::inertia('legal/terms', 'legal/terms')->name('legal.terms');
Route::inertia('legal/privacy-policy', 'legal/privacy-policy')->name('legal.privacy-policy');

Route::middleware('flow.headers')->group(function () {
    Route::get('verify/{token}', [PublicVerificationController::class, 'show'])->name('verify.flow');
    Route::post('verify/{token}', [PublicVerificationController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('verify.flow.submit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/activity', [ActivityController::class, 'index'])->name('dashboard.activity');

    Route::get('apps', [AppController::class, 'index'])->name('apps.index');
    Route::post('apps', [AppController::class, 'store'])->name('apps.store');
    Route::get('apps/{app}', [AppController::class, 'show'])->name('apps.show');
    Route::get('apps/{app}/settings', [AppController::class, 'edit'])->name('apps.edit');
    Route::patch('apps/{app}', [AppController::class, 'update'])->name('apps.update');
    Route::delete('apps/{app}', [AppController::class, 'destroy'])->name('apps.destroy');

    Route::get('apps/{app}/keys', [ApiKeyController::class, 'index'])->name('apps.keys.index');
    Route::get('apps/{app}/keys/new', [ApiKeyController::class, 'create'])->name('apps.keys.create');
    Route::post('apps/{app}/keys', [ApiKeyController::class, 'store'])->name('apps.keys.store');
    Route::post('apps/{app}/keys/{key}/rotate', [ApiKeyController::class, 'rotate'])->name('apps.keys.rotate');
    Route::patch('apps/{app}/keys/{key}/revoke', [ApiKeyController::class, 'revoke'])
        ->name('apps.keys.revoke');

    Route::get('apps/{app}/environments', [AppEnvironmentController::class, 'index'])
        ->name('apps.environments.index');
    Route::put('apps/{app}/environments/{environment}', [AppEnvironmentController::class, 'update'])
        ->name('apps.environments.update');

    Route::get('apps/{app}/verification-settings', [VerificationSettingsController::class, 'show'])
        ->name('apps.verification-settings.show');
    Route::patch('apps/{app}/verification-settings', [VerificationSettingsController::class, 'update'])
        ->name('apps.verification-settings.update');

    Route::get('apps/{app}/verification-flows', [VerificationFlowController::class, 'show'])
        ->name('apps.verification-flows.show');
    Route::patch('apps/{app}/verification-flows', [VerificationFlowController::class, 'update'])
        ->name('apps.verification-flows.update');

    Route::get('apps/{app}/webhooks', [WebhookController::class, 'index'])->name('apps.webhooks.index');
    Route::get('apps/{app}/webhooks/new', [WebhookController::class, 'create'])->name('apps.webhooks.create');
    Route::post('apps/{app}/webhooks', [WebhookController::class, 'store'])->name('apps.webhooks.store');
    Route::patch('apps/{app}/webhooks/{webhook}', [WebhookController::class, 'update'])
        ->name('apps.webhooks.update');
    Route::delete('apps/{app}/webhooks/{webhook}', [WebhookController::class, 'destroy'])
        ->name('apps.webhooks.destroy');
    Route::post('apps/{app}/webhooks/{webhook}/regenerate-secret', [WebhookController::class, 'regenerateSecret'])
        ->name('apps.webhooks.regenerate-secret');
    Route::get('apps/{app}/webhooks/{webhook}/logs', [WebhookController::class, 'logs'])
        ->name('apps.webhooks.logs');
    Route::post('apps/{app}/webhooks/{webhook}/deliveries/{delivery}/retry', [WebhookController::class, 'retryDelivery'])
        ->name('apps.webhooks.retry-delivery');

    Route::get('apps/{app}/consents', [ConsentController::class, 'index'])->name('apps.consents.index');
    Route::get('apps/{app}/consents/{consent}', [ConsentController::class, 'show'])->name('apps.consents.show');

    Route::get('apps/{app}/usage', [UsageController::class, 'index'])->name('apps.usage');
    Route::get('apps/{app}/logs', [ApiCallController::class, 'index'])->name('apps.logs');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/usage', [BillingController::class, 'usage'])->name('billing.usage');
    Route::post('billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::get('billing/invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])
        ->name('billing.invoices.download');

    Route::get('docs', [DocsController::class, 'index'])->name('docs.index');
    Route::get('docs/quickstart', [DocsController::class, 'quickstart'])->name('docs.quickstart');
    Route::post('docs/feedback', [DocsFeedbackController::class, 'store'])->name('docs.feedback');

    Route::get('sandbox', [SandboxController::class, 'index'])->name('sandbox.index');
    Route::post('sandbox/verify', [SandboxController::class, 'verify'])->name('sandbox.verify');

    Route::get('status', [StatusController::class, 'index'])->name('status.index');

    Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('support', [SupportTicketController::class, 'store'])->name('support.store');
});

Route::middleware('auth')->group(function () {
    Route::get('chapa/callback', [BillingController::class, 'chapaCallback'])->name('billing.chapa.callback');
});

Route::post('chapa/webhook', [BillingController::class, 'chapaWebhook'])->name('billing.chapa.webhook');

require __DIR__.'/settings.php';

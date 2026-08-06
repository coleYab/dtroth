<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Models\Verification;
use App\Models\WebhookEvent;
use App\Services\WebhookDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('consents:expire')]
#[Description('Mark expired consents and record the event')]
class ExpireConsents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WebhookDispatcher $dispatcher): int
    {
        $consents = Consent::query()
            ->whereNull('revoked_at')
            ->where('status', '!=', Consent::STATUS_EXPIRED)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($consents as $consent) {
            $consent->forceFill(['status' => Consent::STATUS_EXPIRED])->saveQuietly();

            ConsentEvent::create([
                'consent_id' => $consent->id,
                'type' => ConsentEvent::TYPE_EXPIRED,
                'created_at' => $consent->expires_at->copy()->addSecond(),
            ]);

            $this->dispatchExpiredWebhooks($consent, $dispatcher);
        }

        $this->info(sprintf('[consents:expire] Expired %d consents', $consents->count()));

        return Command::SUCCESS;
    }

    /**
     * Notify subscribers of the most recent completed verification.
     */
    private function dispatchExpiredWebhooks(Consent $consent, WebhookDispatcher $dispatcher): void
    {
        $verification = Verification::query()
            ->where('app_id', $consent->app_id)
            ->where('subject_identifier', $consent->subject_identifier)
            ->where('status', Verification::STATUS_COMPLETED)
            ->latest('created_at')
            ->first();

        if ($verification === null) {
            return;
        }

        $dispatcher->dispatch(
            WebhookEvent::VERIFICATION_EXPIRED,
            App::query()->findOrFail($consent->app_id),
            [
                'event' => WebhookEvent::VERIFICATION_EXPIRED,
                'query_id' => (string) $verification->query_id,
                'subject' => (string) $consent->subject_identifier,
                'expired_at' => $consent->expires_at->toIso8601String(),
                'occurred_at' => now()->toIso8601String(),
            ],
            (string) $verification->query_id,
        );
    }
}

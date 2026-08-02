<?php

namespace App\Models\Observers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Webhook;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class WebhookObserver
{
    /**
     * Handle the Webhook "created" event.
     */
    public function created(Webhook $webhook): void
    {
        $this->log(
            AuditLog::ACTION_WEBHOOK_CHANGED,
            "Webhook to {$webhook->url} created for \"{$webhook->app?->name}\".",
            $webhook->app_id,
        );
    }

    /**
     * Handle the Webhook "updated" event.
     */
    public function updated(Webhook $webhook): void
    {
        $this->log(
            AuditLog::ACTION_WEBHOOK_CHANGED,
            "Webhook to {$webhook->url} updated for \"{$webhook->app?->name}\".",
            $webhook->app_id,
        );
    }

    /**
     * Handle the Webhook "deleted" event.
     */
    public function deleted(Webhook $webhook): void
    {
        $this->log(
            AuditLog::ACTION_WEBHOOK_CHANGED,
            "Webhook to {$webhook->url} deleted for \"{$webhook->app?->name}\".",
            $webhook->app_id,
        );
    }

    private function log(string $action, string $description, ?int $appId): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            AuditLogger::log($user, $action, $description, $appId);
        }
    }
}

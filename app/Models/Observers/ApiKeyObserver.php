<?php

namespace App\Models\Observers;

use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class ApiKeyObserver
{
    /**
     * Handle the ApiKey "created" event.
     */
    public function created(ApiKey $key): void
    {
        $this->log(
            AuditLog::ACTION_KEY_CREATED,
            "API key \"{$key->name}\" created for \"{$key->app->name}\".",
            $key->app_id,
        );
    }

    /**
     * Handle the ApiKey "updated" event.
     */
    public function updated(ApiKey $key): void
    {
        if ($key->wasChanged('secret_hash')) {
            $this->log(
                AuditLog::ACTION_KEY_ROTATED,
                "API key \"{$key->name}\" rotated for \"{$key->app->name}\".",
                $key->app_id,
            );
        }
    }

    private function log(string $action, string $description, ?int $appId): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            AuditLogger::log($user, $action, $description, $appId);
        }
    }
}

<?php

namespace App\Models\Observers;

use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class AppObserver
{
    /**
     * Handle the App "created" event.
     */
    public function created(App $app): void
    {
        $this->log(AuditLog::ACTION_APP_CREATED, "Application \"{$app->name}\" created.");
    }

    /**
     * Handle the App "deleted" event.
     */
    public function deleted(App $app): void
    {
        $this->log(AuditLog::ACTION_APP_DELETED, "Application \"{$app->name}\" deleted.");
    }

    private function log(string $action, string $description): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            AuditLogger::log($user, $action, $description);
        }
    }
}

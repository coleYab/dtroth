<?php

namespace App\Models\Observers;

use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;

class AppObserver
{
    /**
     * Handle the App "creating" event: attach the creator's personal team.
     */
    public function creating(App $app): void
    {
        if ($app->team_id !== null) {
            return;
        }

        /** @var User|null $user */
        $user = $app->user;

        if ($user !== null) {
            $app->team_id = TeamService::ensurePersonalTeam($user)->id;
        }
    }

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

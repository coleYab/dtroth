<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\ApiKey;
use App\Notifications\ApiKeyExpiring;
use App\Services\NotificationGate;
use App\Services\TeamService;
use Illuminate\Console\Command;

class NotifyKeyExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:key-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email teams about API keys expiring within the next 7 days';

    /**
     * Execute the console command.
     */
    public function handle(NotificationGate $gate): int
    {
        $keys = ApiKey::query()
            ->with('app')
            ->whereNotNull('expires_at')
            ->whereNull('revoked_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->get();

        $notified = 0;

        foreach ($keys as $key) {
            foreach (TeamService::recipientsForApp($key->app, TeamRole::Developer) as $user) {
                if ($gate->send($user, new ApiKeyExpiring($key), ['key_id' => $key->id])) {
                    $notified++;
                }
            }
        }

        $this->info("Checked {$keys->count()} expiring key(s); {$notified} notification(s) sent.");

        return Command::SUCCESS;
    }
}

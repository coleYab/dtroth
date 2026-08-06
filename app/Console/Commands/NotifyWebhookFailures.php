<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\Webhook;
use App\Notifications\WebhookFailing;
use App\Services\NotificationGate;
use App\Services\TeamService;
use Illuminate\Console\Command;

class NotifyWebhookFailures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:webhook-failures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email teams whose webhooks have failed deliveries in the last 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(NotificationGate $gate): int
    {
        $webhooks = Webhook::query()
            ->with('app')
            ->whereHas('deliveries', fn ($query) => $query
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subHours(24)))
            ->get();

        $notified = 0;

        foreach ($webhooks as $webhook) {
            foreach (TeamService::recipientsForApp($webhook->app, TeamRole::Developer) as $user) {
                if ($gate->send($user, new WebhookFailing($webhook), ['webhook_id' => $webhook->id])) {
                    $notified++;
                }
            }
        }

        $this->info("Checked {$webhooks->count()} failing webhook(s); {$notified} notification(s) sent.");

        return Command::SUCCESS;
    }
}

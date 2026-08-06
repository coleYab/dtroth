<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\App;
use App\Models\Subscription;
use App\Models\UsageDailyRollup;
use App\Notifications\UsageThresholdReached;
use App\Services\NotificationGate;
use App\Services\TeamService;
use Illuminate\Console\Command;

class NotifyUsageThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:usage-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email teams when app usage crosses plan quota thresholds';

    /**
     * Execute the console command.
     */
    public function handle(NotificationGate $gate): int
    {
        $thresholds = array_values(array_unique(array_map(
            'intval',
            config('verify.usage_thresholds', [80, 100]),
        )));
        sort($thresholds);

        $notified = 0;

        foreach (App::query()->with('user.subscription')->cursor() as $app) {
            /** @var Subscription|null $subscription */
            $subscription = $app->user->subscription;

            $quota = (int) config('billing.plans.'.($subscription?->plan ?? Subscription::PLAN_TRIAL).'.calls_per_month', 0);

            $used = (int) UsageDailyRollup::query()
                ->where('app_id', $app->id)
                ->where('date', '>=', today()->subDays(30)->toDateString())
                ->sum('calls_total');

            $percentage = (int) floor($used / $quota * 100);

            foreach ($thresholds as $threshold) {
                if ($percentage < $threshold) {
                    continue;
                }

                foreach (TeamService::recipientsForApp($app, TeamRole::Developer) as $user) {
                    if ($gate->send($user, new UsageThresholdReached($app, $threshold, $used, $quota), [
                        'app_id' => $app->id,
                        'threshold' => $threshold,
                    ])) {
                        $notified++;
                    }
                }
            }
        }

        $this->info("Evaluated usage thresholds; {$notified} notification(s) sent.");

        return Command::SUCCESS;
    }
}

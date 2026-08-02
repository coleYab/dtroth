<?php

namespace App\Console\Commands;

use App\Models\ApiCall;
use App\Models\UsageDailyRollup;
use App\Models\Verification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RollupUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usage:rollup {--days=30 : Number of days to roll up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate daily API usage into rollup rows';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        foreach (range(0, $days) as $offset) {
            $date = today()->subDays($offset)->toDateString();
            $start = $date.' 00:00:00';
            $end = $date.' 23:59:59';

            $appIds = ApiCall::query()
                ->whereBetween('created_at', [$start, $end])
                ->distinct()
                ->pluck('app_id');

            foreach ($appIds as $appId) {
                $calls = ApiCall::query()
                    ->where('app_id', $appId)
                    ->whereBetween('created_at', [$start, $end])
                    ->get();

                $verifications = Verification::query()
                    ->where('app_id', $appId)
                    ->whereBetween('created_at', [$start, $end])
                    ->get();

                $total = $calls->count();
                $failed = $calls->where(fn (ApiCall $call) => ! $call->succeeded())->count();

                $mostUsed = $verifications
                    ->groupBy(fn (Verification $v) => $v->ageQuery() ?? 'unknown')
                    ->map(fn (EloquentCollection $group) => $group->count())
                    ->sortDesc()
                    ->keys()
                    ->first();

                UsageDailyRollup::updateOrCreate(
                    ['app_id' => $appId, 'date' => $date],
                    [
                        'calls_total' => $total,
                        'verifications_total' => $verifications->count(),
                        'failed_calls' => $failed,
                        'success_rate' => $total > 0 ? round(($total - $failed) / $total * 100, 2) : 0,
                        'most_used_query' => $mostUsed,
                    ],
                );
            }
        }

        $this->info('Usage rollups generated for the last '.($days + 1).' days.');

        return Command::SUCCESS;
    }
}

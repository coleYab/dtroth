<?php

namespace App\Services;

use App\Models\ApiCall;
use App\Models\App;
use App\Models\Verification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UsageService
{
    /**
     * Aggregate usage metrics for an app within a date range.
     *
     * @return array{
     *     total_calls: int,
     *     total_verifications: int,
     *     success_rate: float,
     *     most_used_query: string|null,
     *     daily: Collection<int, array{date: string, calls: int, verifications: int}>,
     *     by_query: Collection<int, array{query: string, calls: int}>
     * }
     */
    public function forApp(App $app, CarbonInterface $from, CarbonInterface $to): array
    {
        $calls = $app->calls()
            ->whereBetween('created_at', [$from, $to])
            ->get(['query_type', 'query_value', 'status_code', 'created_at']);

        $verifications = $app->verifications()
            ->whereBetween('created_at', [$from, $to])
            ->get(['query_type', 'query_value', 'created_at']);

        $totalCalls = $calls->count();
        $succeeded = $calls->where(fn (ApiCall $call) => $call->succeeded())->count();

        $daily = $this->dailySeries(
            $calls->groupBy(fn (ApiCall $call) => $call->created_at->toDateString())
                ->map(fn (Collection $group) => $group->count()),
            $verifications->groupBy(fn (Verification $v) => $v->created_at->toDateString())
                ->map(fn (Collection $group) => $group->count()),
        );

        $byQuery = $calls->filter(fn (ApiCall $call) => $call->ageQuery() !== null)
            ->groupBy(fn (ApiCall $call) => $call->ageQuery())
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->map(fn (int $count, string $query) => ['query' => $query, 'calls' => $count])
            ->values();

        $mostUsed = $verifications->filter(fn (Verification $v) => $v->ageQuery() !== null)
            ->groupBy(fn (Verification $v) => $v->ageQuery())
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'total_calls' => $totalCalls,
            'total_verifications' => $verifications->count(),
            'success_rate' => $totalCalls > 0 ? round($succeeded / $totalCalls * 100, 1) : 100.0,
            'most_used_query' => $mostUsed,
            'daily' => $daily,
            'by_query' => $byQuery,
        ];
    }

    /**
     * Merge per-day call and verification counts into a sorted series.
     *
     * @param  Collection<int, int>  $callsByDay
     * @param  Collection<int, int>  $verificationsByDay
     * @return Collection<int, array{date: string, calls: int, verifications: int}>
     */
    private function dailySeries(Collection $callsByDay, Collection $verificationsByDay): Collection
    {
        return $callsByDay
            ->union($verificationsByDay->map(fn (int $count) => 0))
            ->map(fn (int $count, string $date) => [
                'date' => $date,
                'calls' => $count,
                'verifications' => $verificationsByDay->get($date, 0),
            ])
            ->sortKeys()
            ->values();
    }

    /**
     * Aggregate billable usage for a user across all apps.
     *
     * @return array{
     *     total_calls: int,
     *     total_verifications: int,
     *     estimated_cost_etb: int,
     *     per_app: Collection<int, array{app_id: int, app_name: string, calls: int, verifications: int, estimated_cost_etb: int}>
     * }
     */
    public function forUser($user, CarbonInterface $from, CarbonInterface $to): array
    {
        $perApp = $user->apps()
            ->withCount([
                'calls' => fn ($query) => $query->whereBetween('created_at', [$from, $to]),
                'verifications' => fn ($query) => $query->whereBetween('created_at', [$from, $to]),
            ])
            ->get()
            ->map(fn (App $app) => [
                'app_id' => $app->id,
                'app_name' => $app->name,
                'calls' => $app->calls_count,
                'verifications' => $app->verifications_count,
                'estimated_cost_etb' => $this->estimateCost($app->calls_count),
            ]);

        $totalCalls = $perApp->sum('calls');

        return [
            'total_calls' => $totalCalls,
            'total_verifications' => $perApp->sum('verifications'),
            'estimated_cost_etb' => $perApp->sum('estimated_cost_etb'),
            'per_app' => $perApp,
        ];
    }

    /**
     * Estimate the cost for a call volume, in ETB cents.
     */
    public function estimateCost(int $calls): int
    {
        $threshold = config('billing.plans.starter.calls_per_month');
        $rate = (int) config('billing.overage_rate_per_1000_calls');

        if ($calls <= $threshold) {
            return 0;
        }

        $overageThousands = (int) ceil(($calls - $threshold) / 1000);

        return max(0, $overageThousands) * $rate;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ComponentStatus;
use App\Models\StatusCheck;
use App\Models\StatusIncident;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StatusController extends Controller
{
    /**
     * Show the system status page.
     */
    public function index(): Response
    {
        $components = [];

        foreach (config('status.components', []) as $component) {
            $latest = StatusCheck::query()
                ->where('component', $component['key'])
                ->latest('checked_at')
                ->first();

            $components[] = [
                'key' => $component['key'],
                'label' => $component['label'],
                'description' => $component['description'],
                'status' => $latest?->status?->value ?? ComponentStatus::Down->value,
                'lastCheckedAt' => $latest?->checked_at?->toIso8601String(),
                'responseTimeMs' => $latest?->response_time_ms,
                'uptime30' => $this->uptimePercentage($component['key'], 30),
                'uptime90' => $this->uptimePercentage($component['key'], 90),
            ];
        }

        $incidents = StatusIncident::query()
            ->with('updates')
            ->latest('started_at')
            ->limit(20)
            ->get()
            ->map(fn (StatusIncident $incident) => [
                'id' => $incident->id,
                'title' => $incident->title,
                'component' => $incident->component,
                'startedAt' => $incident->started_at->toIso8601String(),
                'resolvedAt' => $incident->resolved_at?->toIso8601String(),
                'updates' => $incident->updates->map(fn ($update) => [
                    'status' => $update->status->value,
                    'message' => $update->message,
                    'createdAt' => $update->created_at->toIso8601String(),
                ]),
            ])
            ->values()
            ->all();

        return Inertia::render('status', [
            'overall' => $this->overallStatus($components),
            'components' => $components,
            'incidents' => $incidents,
        ]);
    }

    /**
     * Derive the overall status banner from the latest component checks.
     *
     * @param  array<int, array<string, mixed>>  $components
     * @return array{label: string, tone: string, description: string}
     */
    private function overallStatus(array $components): array
    {
        $statuses = collect($components)->pluck('status')->map(fn (string $s) => ComponentStatus::tryFrom($s));

        if ($statuses->contains(ComponentStatus::Down)) {
            return [
                'label' => 'Major Outage',
                'tone' => 'down',
                'description' => 'One or more components are unavailable. We are working to restore service.',
            ];
        }

        if ($statuses->contains(ComponentStatus::Degraded)) {
            return [
                'label' => 'Degraded Performance',
                'tone' => 'degraded',
                'description' => 'Some components are experiencing elevated latency or errors.',
            ];
        }

        return [
            'label' => 'All Systems Operational',
            'tone' => 'ok',
            'description' => 'All components are operating normally.',
        ];
    }

    /**
     * The share of successful checks in the last N days, or null when there
     * is not enough data to compute it.
     */
    private function uptimePercentage(string $component, int $days): ?float
    {
        $since = Carbon::now()->subDays($days);

        $checks = StatusCheck::query()
            ->where('component', $component)
            ->where('checked_at', '>=', $since)
            ->get();

        if ($checks->isEmpty()) {
            return null;
        }

        $ok = $checks->where('status', ComponentStatus::Ok)->count();

        return round($ok / $checks->count() * 100, 2);
    }
}

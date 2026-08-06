<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityEventResource;
use App\Models\ApiKey;
use App\Models\Verification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the developer dashboard with aggregated stats.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'activeApps' => $user->apps()->where('status', 'active')->count(),
            'apiCalls30d' => $user->apps()->withCount([
                'calls as calls_30d' => fn ($query) => $query->where('created_at', '>=', now()->subDays(30)),
            ])->get()->sum('calls_30d'),
            'verifications30d' => Verification::query()
                ->whereHas('app', fn ($query) => $query->where('user_id', $user->id))
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'activeAlerts' => ApiKey::query()
                ->whereHas('app', fn ($query) => $query->where('user_id', $user->id))
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(30)])
                ->count()
                + Verification::query()
                    ->whereHas('app', fn ($query) => $query->where('user_id', $user->id))
                    ->where('status', 'failed')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
        ];

        $recentActivity = $user->apps()
            ->with(['events' => fn ($query) => $query->latest('created_at')->limit(5), 'events.app'])
            ->get()
            ->pluck('events')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentActivity' => ActivityEventResource::collection($recentActivity),
        ]);
    }
}

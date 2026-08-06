<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'team' => fn () => $this->teamPayload($request->user()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'ticket' => fn () => $request->session()->get('ticket'),
            ],
        ];
    }

    /**
     * The shared team summary for the sidebar, or null for guests.
     *
     * @return array{name: string, slug: string, role: string|null, member_count: int}|null
     */
    private function teamPayload(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $team = TeamService::teamForUser($user);

        if ($team === null) {
            return null;
        }

        return [
            'name' => $team->name,
            'slug' => $team->slug,
            'role' => $team->roleFor($user)?->value,
            'member_count' => $team->memberCount(),
        ];
    }
}

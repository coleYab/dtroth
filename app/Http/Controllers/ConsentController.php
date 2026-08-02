<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppResource;
use App\Http\Resources\ConsentResource;
use App\Models\App;
use App\Models\Consent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsentController extends Controller
{
    /**
     * Show the consents page for an application.
     */
    public function index(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app);

        $status = $request->string('status')->toString();
        $query = $request->string('query')->toString();

        $consents = $app->consents()
            ->when($status !== '' && in_array($status, ['active', 'revoked', 'expired'], true), function (Builder $builder) use ($status) {
                match ($status) {
                    'active' => $builder->active(),
                    'revoked' => $builder->whereNotNull('revoked_at'),
                    'expired' => $builder->whereNull('revoked_at')->where('expires_at', '<', now()),
                    default => null,
                };
            })
            ->when($query !== '', function (Builder $builder) use ($query) {
                $builder->where(function (Builder $inner) use ($query) {
                    $inner->where('subject_identifier', 'like', "%{$query}%")
                        ->orWhere('query_type', $query)
                        ->orWhere('query_value', $query);
                });
            })
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'active' => (clone $app->consents())->active()->count(),
            'revoked' => (clone $app->consents())->whereNotNull('revoked_at')->count(),
            'expired' => (clone $app->consents())->whereNull('revoked_at')->where('expires_at', '<', now())->count(),
        ];

        return Inertia::render('apps/consents/index', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'consents' => ConsentResource::collection($consents),
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'query' => $query,
            ],
        ]);
    }

    /**
     * Show a single consent with its full event timeline.
     */
    public function show(Request $request, App $app, Consent $consent): Response
    {
        $app = $this->ownedApp($request, $app);

        $consent = $app->consents()
            ->with('events')
            ->findOrFail($consent->id);

        return Inertia::render('apps/consents/show', [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'consent' => (new ConsentResource($consent))->resolve(),
        ]);
    }

    /**
     * Resolve the app owned by the authenticated user.
     */
    private function ownedApp(Request $request, App $app): App
    {
        return $request->user()->apps()->findOrFail($app->id);
    }
}

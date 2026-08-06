<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppEnvironmentRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Models\AppEnvironment;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppEnvironmentController extends Controller
{
    /**
     * Show the application's environment configuration.
     */
    public function index(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        return Inertia::render('apps/environments', [
            'app' => (new AppResource($app))->resolve(),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
        ]);
    }

    /**
     * Update an environment's configuration.
     */
    public function update(
        UpdateAppEnvironmentRequest $request,
        App $app,
        AppEnvironment $environment,
    ): RedirectResponse {
        $app = $this->ownedApp($request, $app, 'manage_app');

        $environment = $app->environments()->findOrFail($environment->id);

        $environment->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Environment updated.'),
        ]);

        return to_route('apps.environments.index', $app);
    }

    /**
     * Resolve the app the authenticated user can access with the given
     * permission.
     */
    private function ownedApp(Request $request, App $app, string $permission = 'view'): App
    {
        $app = TeamService::appForMember($request->user(), $app->id);
        TeamService::authorize($request->user(), $app, $permission);

        return $app;
    }
}

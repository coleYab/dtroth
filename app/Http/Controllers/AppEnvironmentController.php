<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAppEnvironmentRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Models\AppEnvironment;
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
        $app = $request->user()->apps()->with('environments')->findOrFail($app->id);

        return Inertia::render('apps/environments', [
            'app' => (new AppResource($app))->resolve(),
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
        $app = $request->user()->apps()->findOrFail($app->id);

        $environment = $app->environments()->findOrFail($environment->id);

        $environment->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Environment updated.'),
        ]);

        return to_route('apps.environments.index', $app);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AppStatus;
use App\Enums\EnvironmentName;
use App\Http\Requests\StoreAppRequest;
use App\Http\Requests\UpdateAppRequest;
use App\Http\Resources\ApiKeyResource;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    /**
     * List the user's applications.
     */
    public function index(Request $request): Response
    {
        $apps = App::query()
            ->forMember($request->user())
            ->with('environments')
            ->when($request->string('q')->toString() !== '', function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('q')->toString().'%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('apps/index', [
            'apps' => AppResource::collection($apps),
            'canCreate' => TeamService::canCreateApps($request->user()),
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    /**
     * Store a newly created application.
     */
    public function store(StoreAppRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $app = $request->user()->apps()->create([
            'name' => $validated['name'],
            'image' => $this->storeImage($request),
            'status' => AppStatus::Active,
            'redirect_uris' => $validated['redirect_uris'],
            'allowed_origins' => $validated['allowed_origins'] ?? [],
        ]);

        $app->environments()->create([
            'name' => $request->environmentName(),
            'redirect_uris' => null,
            'allowed_origins' => null,
        ]);

        $otherName = $request->environmentName() === EnvironmentName::Sandbox
            ? EnvironmentName::Production
            : EnvironmentName::Sandbox;

        $app->environments()->create([
            'name' => $otherName,
            'is_live' => $otherName === EnvironmentName::Production,
            'redirect_uris' => null,
            'allowed_origins' => null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Application created.'),
        ]);

        return to_route('apps.show', $app);
    }

    /**
     * Show the application overview page.
     */
    public function show(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        return Inertia::render('apps/show', [
            'app' => (new AppResource($app))->resolve(),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
            'stats' => [
                'apiCalls' => $app->calls()->count(),
                'lastVerification' => $app->verifications()->latest('created_at')->value('created_at'),
                'activeConsents' => $app->consents()->where('status', 'active')->count(),
            ],
            'keys' => ApiKeyResource::collection(
                $app->keys()->with('environment')->latest('created_at')->limit(3)->get()
            ),
        ]);
    }

    /**
     * Show the edit application form.
     */
    public function edit(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'manage_app');

        return Inertia::render('apps/settings', [
            'app' => (new AppResource($app))->resolve(),
            'permissions' => TeamService::permissionsFor($request->user(), $app),
        ]);
    }

    /**
     * Update the application metadata.
     */
    public function update(UpdateAppRequest $request, App $app): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_app');

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($app);
            $validated['image'] = $request->file('image')->store('app-images', 'public');
        } else {
            unset($validated['image']);
        }

        $app->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Application updated.'),
        ]);

        return to_route('apps.edit', $app);
    }

    /**
     * Delete the application and all related data.
     */
    public function destroy(Request $request, App $app): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'delete_app');

        $this->deleteImage($app);

        $app->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Application deleted.'),
        ]);

        return to_route('apps.index');
    }

    /**
     * Store the uploaded logo on the public disk.
     */
    private function storeImage(StoreAppRequest|UpdateAppRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('app-images', 'public') ?: null;
    }

    /**
     * Delete the app logo from the public disk when present.
     */
    private function deleteImage(App $app): void
    {
        if ($app->image !== null) {
            Storage::disk('public')->delete($app->image);
        }
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

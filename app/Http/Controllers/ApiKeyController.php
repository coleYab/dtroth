<?php

namespace App\Http\Controllers;

use App\Enums\EnvironmentName;
use App\Http\Requests\StoreApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Http\Resources\AppResource;
use App\Models\ApiKey;
use App\Models\App;
use Database\Factories\ApiKeyFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Show the API keys page for an application.
     */
    public function index(Request $request, App $app): Response
    {
        return $this->renderKeysPage($request, $app);
    }

    /**
     * Show the keys page with the generate dialog open.
     */
    public function create(Request $request, App $app): Response
    {
        return $this->renderKeysPage($request, $app, generateOpen: true);
    }

    /**
     * Generate a new public/secret key pair for an environment.
     */
    public function store(StoreApiKeyRequest $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app);

        $environment = $app->environments()->where('name', $request->environmentName())->firstOrFail();

        $pair = $this->generatePair($request->environmentName());

        $key = $app->keys()->create([
            'app_environment_id' => $environment->id,
            'name' => $request->string('name')->toString() ?: 'API key',
            'public_key' => $pair['public_key'],
            'secret_hash' => $pair['secret_hash'],
            'secret_preview' => $pair['secret_preview'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('API key generated.'),
        ]);

        return $this->renderKeysPage($request, $app, revealedKey: [
            'id' => $key->id,
            'name' => $key->name,
            'environment' => $request->environmentName()->value,
            'public_key' => $pair['public_key'],
            'secret_key' => $pair['secret_key'],
        ]);
    }

    /**
     * Rotate an existing key pair, replacing the secret without changing identity.
     */
    public function rotate(Request $request, App $app, ApiKey $key): Response
    {
        $app = $this->ownedApp($request, $app);

        $key = $app->keys()->with('environment')->findOrFail($key->id);

        if ($key->isRevoked()) {
            abort(422, 'Revoked keys cannot be rotated.');
        }

        $environment = $key->environment ?? $app->environments()->firstOrFail();

        $pair = $this->generatePair($environment->name);

        $key->update([
            'secret_hash' => $pair['secret_hash'],
            'secret_preview' => $pair['secret_preview'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('API key rotated.'),
        ]);

        return $this->renderKeysPage($request, $app, revealedKey: [
            'id' => $key->id,
            'name' => $key->name,
            'environment' => $environment->name->value,
            'public_key' => $pair['public_key'],
            'secret_key' => $pair['secret_key'],
        ]);
    }

    /**
     * Permanently revoke a key pair.
     */
    public function revoke(Request $request, App $app, ApiKey $key): Response
    {
        $app = $this->ownedApp($request, $app);

        $key = $app->keys()->findOrFail($key->id);

        if ($key->isRevoked()) {
            abort(422, 'This key is already revoked.');
        }

        $key->update(['revoked_at' => now()]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('API key revoked.'),
        ]);

        return $this->renderKeysPage($request, $app);
    }

    /**
     * Render the keys page with the given transient props.
     *
     * @param  array<string, mixed>|null  $revealedKey
     */
    private function renderKeysPage(
        Request $request,
        App $app,
        bool $generateOpen = false,
        ?array $revealedKey = null,
    ): Response {
        $app = $this->ownedApp($request, $app);

        $keys = $app->keys()
            ->with('environment')
            ->latest('created_at')
            ->get();

        return Inertia::render('apps/keys', [
            'app' => (new AppResource($app))->resolve(),
            'keys' => ApiKeyResource::collection($keys),
            'generateOpen' => $generateOpen,
            'revealedKey' => $revealedKey,
        ]);
    }

    /**
     * Resolve the app owned by the authenticated user.
     */
    private function ownedApp(Request $request, App $app): App
    {
        return $request->user()->apps()->with('environments')->findOrFail($app->id);
    }

    /**
     * Generate a fresh public/secret key pair for the given environment.
     *
     * @return array{public_key: string, secret_key: string, secret_hash: string, secret_preview: string}
     */
    private function generatePair(EnvironmentName $environment): array
    {
        return ApiKeyFactory::generatePair($environment);
    }
}

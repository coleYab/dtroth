<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVerificationFlowRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VerificationFlowController extends Controller
{
    /**
     * The disk used for verification flow branding assets.
     */
    private const LOGO_DISK = 'public';

    /**
     * Show the verification flows page for an application.
     */
    public function show(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        return Inertia::render('apps/verification-flows', $this->payload($request, $app));
    }

    /**
     * Update the verification flow branding and redirects for an application.
     */
    public function update(UpdateVerificationFlowRequest $request, App $app): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_app');

        $flow = $app->verificationFlow()->firstOrCreate(['app_id' => $app->id]);

        $data = [
            'brand_color' => strtoupper($request->string('brand_color')->toString()),
            'success_redirect_url' => $request->string('success_redirect_url')->trim()->toString() ?: null,
            'failure_redirect_url' => $request->string('failure_redirect_url')->trim()->toString() ?: null,
            'callback_url' => $request->string('callback_url')->trim()->toString() ?: null,
        ];

        if ($request->hasFile('logo')) {
            if ($flow->logo_path !== null) {
                Storage::disk(self::LOGO_DISK)->delete($flow->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('verification-flows', self::LOGO_DISK);
        }

        $flow->update($data);

        AuditLogger::log(
            $request->user(),
            AuditLog::ACTION_VERIFICATION_CHANGED,
            "Verification flow branding updated for \"{$app->name}\".",
            $app->id,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Verification flow saved.'),
        ]);

        return redirect()->back();
    }

    /**
     * The shared page payload.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request, App $app): array
    {
        $flow = $app->verificationFlow;

        return [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'flow' => [
                'logo_url' => $flow?->logo_path !== null
                    ? Storage::disk(self::LOGO_DISK)->url($flow->logo_path)
                    : null,
                'brand_color' => $flow?->brand_color ?? '#0f172a',
                'success_redirect_url' => $flow?->success_redirect_url,
                'failure_redirect_url' => $flow?->failure_redirect_url,
                'callback_url' => $flow?->callback_url,
                'updated_at' => $flow?->updated_at?->toISOString(),
            ],
            'permissions' => TeamService::permissionsFor($request->user(), $app),
        ];
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

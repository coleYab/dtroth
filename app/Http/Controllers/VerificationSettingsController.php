<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVerificationSettingsRequest;
use App\Http\Resources\AppResource;
use App\Models\AgeQueryPreset;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\VerificationSetting;
use App\Services\AuditLogger;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationSettingsController extends Controller
{
    /**
     * Show the verification settings page for an application.
     */
    public function show(Request $request, App $app): Response
    {
        $app = $this->ownedApp($request, $app, 'view');

        return Inertia::render('apps/verification-settings', $this->payload($request, $app));
    }

    /**
     * Update the verification settings for an application.
     */
    public function update(UpdateVerificationSettingsRequest $request, App $app): RedirectResponse
    {
        $app = $this->ownedApp($request, $app, 'manage_app');

        $settings = $app->verificationSetting()->firstOrCreate(
            ['app_id' => $app->id],
            ['re_verify_months' => 6],
        );

        $settings->update([
            'consent_text' => $request->string('consent_text')->trim()->toString() ?: null,
            're_verify_months' => $request->integer('re_verify_months'),
        ]);

        $presets = $request->input('presets', []);

        $app->ageQueryPresets()->sync($presets);

        AuditLogger::log(
            $request->user(),
            AuditLog::ACTION_VERIFICATION_CHANGED,
            "Verification settings updated for \"{$app->name}\".",
            $app->id,
        );

        if ($presets !== []) {
            AuditLogger::log(
                $request->user(),
                AuditLog::ACTION_PRESET_CHANGED,
                'Age-query presets updated for "'.$app->name.'".',
                $app->id,
            );
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Verification settings saved.'),
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
        $settings = $app->verificationSetting;

        $presets = AgeQueryPreset::query()
            ->with('apps')
            ->available()
            ->get()
            ->map(fn (AgeQueryPreset $preset) => [
                'id' => $preset->id,
                'label' => $preset->label,
                'type' => $preset->type->value,
                'value' => $preset->value,
                'description' => $preset->description,
                'is_enabled' => $preset->apps->contains('id', $app->id),
            ]);

        return [
            'app' => (new AppResource($app->load('environments')))->resolve(),
            'settings' => [
                'consent_text' => $settings?->consent_text ?? VerificationSetting::DEFAULT_CONSENT_TEXT,
                're_verify_months' => $settings?->re_verify_months ?? 6,
                'consent_updated_at' => $settings?->updated_at?->toISOString(),
            ],
            'presets' => $presets,
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

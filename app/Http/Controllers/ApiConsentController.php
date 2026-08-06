<?php

namespace App\Http\Controllers;

use App\Exceptions\VerificationException;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Models\WebhookEvent;
use App\Services\VerificationService;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiConsentController extends Controller
{
    /**
     * Revoke an active consent by ID.
     *
     * Revocation is idempotent: revoking an already-revoked or expired
     * consent succeeds without creating a second event.
     */
    public function __invoke(Request $request, int $consent, VerificationService $service, WebhookDispatcher $dispatcher): JsonResponse
    {
        try {
            $key = $service->authenticate($request->bearerToken() ?? '');
        } catch (VerificationException $e) {
            return response()->json([
                'error' => [
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                ],
            ], $e->statusCode);
        }

        $model = Consent::query()->where('id', $consent)->where('app_id', $key->app_id)->first();

        if ($model === null) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'No consent matches the provided ID.',
                ],
            ], 404);
        }

        $stateChanged = false;

        if (! $model->isRevoked()) {
            $model->forceFill([
                'status' => Consent::STATUS_REVOKED,
                'revoked_at' => now(),
            ])->saveQuietly();

            ConsentEvent::create([
                'consent_id' => $model->id,
                'type' => ConsentEvent::TYPE_REVOKED,
                'created_at' => now(),
            ]);

            $stateChanged = true;
        }

        if ($stateChanged) {
            $revokedAt = now();

            $dispatcher->dispatch(
                WebhookEvent::CONSENT_REVOKED,
                $model->app,
                [
                    'event' => WebhookEvent::CONSENT_REVOKED,
                    'consent_id' => $model->id,
                    'subject' => (string) $model->subject_identifier,
                    'revoked_at' => $revokedAt->toIso8601String(),
                    'occurred_at' => $revokedAt->toIso8601String(),
                ],
                'consent:'.$model->id,
            );
        }

        $revokedAt = $model->fresh()?->revoked_at?->toIso8601String();

        return response()->json([
            'consent_id' => $model->id,
            'status' => 'revoked',
            'revoked_at' => $revokedAt,
        ]);
    }
}

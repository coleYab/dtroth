<?php

namespace App\Http\Controllers;

use App\Exceptions\VerificationException;
use App\Models\Verification;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiVerificationLookupController extends Controller
{
    /**
     * Look up a previously issued verification result by query ID.
     *
     * Results are scoped to the authenticated app and never trigger a
     * provider round trip.
     */
    public function __invoke(Request $request, string $queryId, VerificationService $service): JsonResponse
    {
        $started = hrtime(true);

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

        $verification = Verification::query()
            ->where('query_id', $queryId)
            ->where('app_id', $key->app_id)
            ->where('status', '!=', Verification::STATUS_PENDING)
            ->first();

        if ($verification === null) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'No verification result matches the provided query ID.',
                ],
            ], 404);
        }

        $expired = $verification->expires_at !== null && $verification->expires_at->isPast();

        if ($expired) {
            return response()->json([
                'error' => [
                    'code' => 'expired',
                    'message' => 'This verification result has expired.',
                ],
            ], 410);
        }

        return response()->json([
            'query_id' => (string) $verification->query_id,
            'result' => (bool) $verification->result,
            'age' => (int) $verification->query_value,
            'type' => $verification->query_type?->value,
            'verified_at' => $verification->created_at->toIso8601String(),
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'expired' => false,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AgeQueryType;
use App\Exceptions\VerificationException;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\Verification;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiVerifySessionController extends Controller
{
    /**
     * Create a hosted verification session for the given age query.
     */
    public function __invoke(Request $request, VerificationService $service): JsonResponse
    {
        $started = hrtime(true);
        $key = null;

        try {
            $key = $service->authenticate($request->bearerToken() ?? '');
        } catch (VerificationException $e) {
            return $this->error($service, $e, $started);
        }

        $validator = Validator::make($request->only('age', 'type', 'subject'), [
            'age' => ['required', 'integer', 'between:1,130'],
            'type' => ['required', 'string', 'in:'.collect(AgeQueryType::cases())->pluck('value')->implode(',')],
            'subject' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $service,
                new VerificationException(422, 'validation_error', $validator->errors()->first()),
                $started,
                $key,
                (int) $request->input('age', 0),
                AgeQueryType::tryFrom((string) $request->input('type', '')),
                (string) $request->input('subject', ''),
            );
        }

        $age = (int) $validator->validated()['age'];
        $type = AgeQueryType::from($validator->validated()['type']);
        $subject = $validator->validated()['subject'];

        try {
            $service->ensurePresetAllowed($key->app_id, $type, $age);
        } catch (VerificationException $e) {
            return $this->error($service, $e, $started, $key, $age, $type, $subject);
        }

        $verification = Verification::create([
            'app_id' => $key->app_id,
            'app_environment_id' => $key->app_environment_id,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes(15),
            'status' => Verification::STATUS_PENDING,
            'query_type' => $type,
            'query_value' => $age,
            'subject_identifier' => $subject,
            'callback_url' => $this->callbackUrlFor($key->app_id),
            'created_at' => now(),
        ]);

        $payload = [
            'session_id' => (string) $verification->token,
            'verification_url' => route('verify.flow', ['token' => $verification->token]),
            'expires_at' => $verification->expires_at->toIso8601String(),
        ];

        $service->recordCall(
            $key,
            $age,
            $type,
            $subject,
            $payload,
            200,
            $this->responseTimeMs($started),
            '/v1/verify/session',
        );

        return response()->json($payload);
    }

    /**
     * Build an error response and record the failed call when possible.
     */
    private function error(
        VerificationService $service,
        VerificationException $e,
        int $started,
        ?ApiKey $key = null,
        int $age = 0,
        ?AgeQueryType $type = null,
        string $subject = '',
    ): JsonResponse {
        if ($key !== null) {
            $service->recordCall(
                $key,
                $age,
                $type ?? AgeQueryType::EqualAbove,
                $subject,
                ['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]],
                $e->statusCode,
                $this->responseTimeMs($started),
                '/v1/verify/session',
            );
        }

        return response()->json([
            'error' => [
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
            ],
        ], $e->statusCode);
    }

    /**
     * Ensure the app has a flow with a callback secret before a session is created.
     */
    private function callbackUrlFor(int $appId): ?string
    {
        $flow = App::query()->findOrFail($appId)->verificationFlow()->firstOrCreate();

        if ($flow->callback_secret === null) {
            $flow->forceFill(['callback_secret' => Str::random(48)])->saveQuietly();
        }

        return $flow->callback_url;
    }

    /**
     * Measure elapsed time since the request started.
     */
    private function responseTimeMs(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}

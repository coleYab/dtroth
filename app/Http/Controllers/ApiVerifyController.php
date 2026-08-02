<?php

namespace App\Http\Controllers;

use App\Enums\AgeQueryType;
use App\Exceptions\VerificationException;
use App\Models\ApiKey;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiVerifyController extends Controller
{
    /**
     * Handle a request to the age-query endpoint.
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

        $age = is_numeric($request->input('age')) ? (int) $request->input('age') : 0;
        $type = AgeQueryType::tryFrom((string) $request->input('type'));
        $subject = (string) $request->input('subject', '');

        if ($validator->fails()) {
            return $this->error(
                $service,
                new VerificationException(422, 'validation_error', $validator->errors()->first()),
                $started,
                $key,
                $age,
                $type,
                $subject,
            );
        }

        $age = (int) $validator->validated()['age'];
        $type = AgeQueryType::from($validator->validated()['type']);
        $subject = $validator->validated()['subject'];

        try {
            $payload = $service->verify($key, $age, $type, $subject);
        } catch (VerificationException $e) {
            return $this->error($service, $e, $started, $key, $age, $type, $subject);
        }

        $service->recordCall($key, $age, $type, $subject, $payload, 200, $this->responseTimeMs($started));

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
     * Measure elapsed time since the request started.
     */
    private function responseTimeMs(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Exceptions\VerificationException;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\SandboxIdentity;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SandboxController extends Controller
{
    /**
     * Show the interactive sandbox.
     */
    public function index(Request $request): Response
    {
        return $this->render($request->user());
    }

    /**
     * Send a verification request against the sandbox backend.
     */
    public function verify(Request $request, VerificationService $service): Response|RedirectResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->only('app_id', 'age', 'type', 'subject'), [
            'app_id' => ['required', 'integer', 'exists:apps,id'],
            'age' => ['required', 'integer', 'between:1,130'],
            'type' => ['required', 'string', 'in:equal_above,equal_below'],
            'subject' => ['required', 'string', 'max:100'],
        ]);

        $app = App::query()->forMember($user)->where('id', $request->input('app_id'))->first();

        if ($app === null) {
            return back()->withErrors(['app_id' => 'The selected application is invalid.'])->withInput();
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $limit = (int) config('sandbox.requests_per_minute');
        $rateKey = 'sandbox:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
            return $this->render($user, [
                'statusCode' => 429,
                'durationMs' => null,
                'payload' => [
                    'error' => [
                        'code' => 'too_many_requests',
                        'message' => 'You have reached the sandbox limit of '.$limit.' requests per minute. Please wait a moment and try again.',
                    ],
                ],
            ], RateLimiter::availableIn($rateKey));
        }

        $identity = SandboxIdentity::query()->where('key', $request->input('subject'))->first();

        if ($identity === null) {
            return $this->render($user, [
                'statusCode' => 404,
                'durationMs' => null,
                'payload' => [
                    'error' => [
                        'code' => 'unknown_subject',
                        'message' => 'The selected test identity is not available. Pick one from the list.',
                    ],
                ],
            ]);
        }

        $sandboxEnvironment = $app->environments()->where('name', EnvironmentName::Sandbox)->first();

        if ($sandboxEnvironment === null) {
            return $this->render($user, [
                'statusCode' => 400,
                'durationMs' => null,
                'payload' => [
                    'error' => [
                        'code' => 'no_sandbox_environment',
                        'message' => 'This application has no sandbox environment. Create one in App settings.',
                    ],
                ],
            ]);
        }

        $key = ApiKey::query()
            ->where('app_id', $app->id)
            ->where('app_environment_id', $sandboxEnvironment->id)
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($key === null) {
            return $this->render($user, [
                'statusCode' => 400,
                'durationMs' => null,
                'payload' => [
                    'error' => [
                        'code' => 'no_sandbox_key',
                        'message' => 'This application has no active sandbox API key. Create one in App settings → API keys.',
                    ],
                ],
            ]);
        }

        RateLimiter::hit($rateKey, $limit);

        $age = (int) $request->input('age');
        $type = AgeQueryType::from($request->input('type'));
        $subject = $request->input('subject');

        $started = hrtime(true);

        try {
            $payload = $service->verify($key, $age, $type, $subject);
            $statusCode = 200;
        } catch (VerificationException $e) {
            $payload = ['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]];
            $statusCode = $e->statusCode;
        }

        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        $service->recordCall($key, $age, $type, $subject, $payload, $statusCode, $durationMs);

        return $this->render($user, [
            'statusCode' => $statusCode,
            'durationMs' => $durationMs,
            'payload' => $payload,
        ]);
    }

    /**
     * Render the sandbox page with shared props.
     *
     * @param  array{statusCode: int, durationMs: int|null, payload: array<string, mixed>}  $outcome
     */
    private function render(User $user, ?array $outcome = null, ?int $retryAfter = null): Response
    {
        $limit = (int) config('sandbox.requests_per_minute');
        $rateKey = 'sandbox:'.$user->id;

        return Inertia::render('sandbox', [
            'apps' => App::query()
                ->forMember($user)
                ->with(['environments' => fn ($query) => $query->where('name', EnvironmentName::Sandbox)])
                ->orderBy('name')
                ->get()
                ->filter(fn (App $app) => $app->environments->isNotEmpty())
                ->map(fn (App $app) => ['id' => $app->id, 'name' => $app->name])
                ->values()
                ->all(),
            'identities' => SandboxIdentity::query()
                ->orderBy('age')
                ->get()
                ->map(fn (SandboxIdentity $identity) => [
                    'key' => $identity->key,
                    'label' => $identity->label,
                    'age' => $identity->age,
                ])
                ->all(),
            'outcome' => $outcome,
            'rateLimit' => [
                'limit' => $limit,
                'remaining' => max(0, RateLimiter::remaining($rateKey, $limit)),
                'retryAfter' => $retryAfter,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AgeQueryType;
use App\Exceptions\IdentityProvider\IdentityProviderException;
use App\Exceptions\IdentityProvider\ProviderDeclinedException;
use App\Exceptions\IdentityProvider\SubjectNotFoundException;
use App\Models\ApiCall;
use App\Models\Consent;
use App\Models\ConsentEvent;
use App\Models\Verification;
use App\Models\VerificationSetting;
use App\Models\WebhookEvent;
use App\Services\IdentityProviderRegistry;
use App\Services\VerificationService;
use App\Services\WebhookDispatcher;
use App\Services\WebhookSignature;
use App\ValueObjects\IdentityQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Serves the hosted verification flow: the branded public page where a
 * subject enters their national ID and consents to the age query. Runs
 * outside the dashboard auth group and never stores the national ID.
 */
class PublicVerificationController extends Controller
{
    public function __construct(
        private readonly IdentityProviderRegistry $registry,
        private readonly WebhookSignature $signature,
        private readonly WebhookDispatcher $dispatcher,
    ) {}

    /**
     * Render the hosted flow page for a pending session.
     */
    public function show(string $token): Response
    {
        $verification = $this->verificationFor($token);

        return $this->render($verification, $this->stateFor($verification));
    }

    /**
     * Accept the subject's national ID and consent on the hosted flow.
     */
    public function submit(string $token, Request $request): Response|RedirectResponse
    {
        $verification = $this->verificationFor($token);
        $state = $this->stateFor($verification);

        if ($state !== 'valid') {
            return $this->render($verification, $state);
        }

        $isSandbox = $verification->environment?->name->value === 'sandbox';
        $validator = Validator::make($request->only('national_id', 'consent'), [
            'national_id' => ['required', 'string', $isSandbox ? 'max:40' : 'regex:/^\d{15}$/'],
            'consent' => ['required', 'accepted'],
        ], [
            'national_id.required' => 'Please enter your national ID.',
            'national_id.regex' => 'Your national ID must be 15 digits.',
            'national_id.max' => 'Enter a valid sandbox test key.',
            'consent.accepted' => 'You must consent before verification can proceed.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        $nationalId = (string) $validator->validated()['national_id'];

        $provider = $this->registry->resolve($verification->environment->name);

        $started = hrtime(true);

        try {
            $providerResult = $provider->verify(
                new IdentityQuery($nationalId, (int) $verification->query_value, $verification->query_type),
            );
        } catch (SubjectNotFoundException $e) {
            return $this->failInline($verification, $e, $started, 'The national ID you entered could not be matched. Please check it and try again.');
        } catch (ProviderDeclinedException $e) {
            $payload = $this->callbackPayload($verification, null, null, $e->errorCode);
            $this->complete($verification, null, $started, $payload);
            $this->sendCallback($verification, $payload);

            return $this->render($verification, 'valid', null, [
                'code' => $e->errorCode,
                'message' => 'This national ID cannot be used for verification at this time. Please contact the service you were verifying for.',
            ]);
        } catch (IdentityProviderException $e) {
            return $this->failInline($verification, $e, $started, 'The verification service is temporarily unavailable. Please try again in a moment.');
        }

        $result = $providerResult->result;
        $payload = $this->callbackPayload($verification, $result, $providerResult->reference);
        $this->complete($verification, $result, $started, $payload);

        if ($result) {
            $this->upsertConsent($verification, true);
        }

        $this->sendCallback($verification, $payload);

        return $this->redirectOutcome($verification, $result, $payload);
    }

    /**
     * Complete the session and record the call for usage purposes.
     *
     * @param  array<string, mixed>  $payload
     */
    private function complete(Verification $verification, ?bool $result, int $started, array $payload): void
    {
        $verification->forceFill([
            'status' => Verification::STATUS_COMPLETED,
            'completed_at' => now(),
            'result' => $result,
            'expires_at' => $this->consentExpiry($verification->app_id),
        ])->saveQuietly();

        $this->recordFlowCall($verification, $started, $payload);

        $this->dispatcher->dispatch(
            WebhookEvent::VERIFICATION_COMPLETED,
            $verification->app,
            [
                'event' => WebhookEvent::VERIFICATION_COMPLETED,
                'query_id' => (string) $verification->query_id,
                'subject' => (string) $verification->subject_identifier,
                'age' => (int) $verification->query_value,
                'type' => $verification->query_type?->value,
                'result' => $result,
                'verified_at' => now()->toIso8601String(),
                'expires_at' => $result === true ? $this->consentExpiry($verification->app_id)->toIso8601String() : null,
                'environment' => $verification->environment?->name->value ?? 'production',
                'occurred_at' => now()->toIso8601String(),
            ],
            (string) $verification->query_id,
        );
    }

    /**
     * Record the flow call for usage purposes. The national ID is never
     * persisted — the request payload only carries the session reference.
     *
     * @param  array<string, mixed>  $payload
     */
    private function recordFlowCall(Verification $verification, int $started, array $payload): void
    {
        ApiCall::create([
            'app_id' => $verification->app_id,
            'method' => 'POST',
            'path' => '/verify/'.$verification->token,
            'query_type' => $verification->query_type,
            'query_value' => $verification->query_value,
            'result' => $payload['result'] ?? null,
            'status_code' => 200,
            'response_time_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
            'request_payload' => ['session_id' => $verification->token],
            'response_payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /**
     * Build the signed callback payload for the flow's callback URL.
     *
     * @return array<string, mixed>
     */
    private function callbackPayload(Verification $verification, ?bool $result, ?string $reference = null, ?string $errorCode = null): array
    {
        return [
            'query_id' => (string) $verification->query_id,
            'subject' => (string) $verification->subject_identifier,
            'age' => (int) $verification->query_value,
            'type' => $verification->query_type->value,
            'result' => $result,
            'error' => $errorCode !== null ? ['code' => $errorCode] : null,
            'provider_reference' => $reference,
            'verified_at' => now()->toIso8601String(),
            'expires_at' => $result === true ? $this->consentExpiry($verification->app_id)->toIso8601String() : null,
            'occurred_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Deliver the completed callback to the developer, signed with HMAC.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendCallback(Verification $verification, array $payload): void
    {
        $flow = $verification->app->verificationFlow()->first();

        if ($flow?->callback_url === null || $flow->callback_secret === null) {
            return;
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Troth-Event' => 'verification.completed',
                    'X-Troth-Signature' => $this->signature->sign((string) $body, $flow->callback_secret),
                    'X-Troth-Request-ID' => (string) Str::uuid(),
                ])
                ->withBody((string) $body, 'application/json')
                ->post($flow->callback_url);
        } catch (\Throwable $e) {
            Log::warning(sprintf('Hosted flow callback failed for verification %d: %s', $verification->id, $e->getMessage()));
        }
    }

    /**
     * Send the subject to the app's redirect URL with the outcome query params.
     *
     * @param  array<string, mixed>  $payload
     */
    private function redirectOutcome(Verification $verification, bool $result, array $payload): RedirectResponse|Response
    {
        $flow = $verification->app->verificationFlow()->first();
        $target = $result ? $flow?->success_redirect_url : $flow?->failure_redirect_url;

        if ($target === null || $target === '') {
            return $this->render($verification, $result ? 'success' : 'failure', $payload);
        }

        $params = [
            'verification' => $result ? 'success' : 'failed',
            'query_id' => (string) $verification->query_id,
            'subject' => (string) $verification->subject_identifier,
            'result' => $result ? 'true' : 'false',
            'verified_at' => $payload['verified_at'],
            'expires_at' => $payload['expires_at'],
            'error' => $result ? null : 'identity_failed',
        ];

        return redirect()->away($target.'?'.http_build_query(array_filter($params)));
    }

    /**
     * Render the flow page with an inline error, without completing the session.
     */
    private function failInline(
        Verification $verification,
        IdentityProviderException $e,
        int $started,
        string $message,
    ): Response {
        $this->recordFlowCall($verification, $started, [
            'error' => ['code' => $e->errorCode],
        ]);

        return $this->render($verification, 'valid', null, [
            'code' => $e->errorCode,
            'message' => $message,
        ]);
    }

    /**
     * Determine the display state for a verification token.
     */
    private function stateFor(?Verification $verification): string
    {
        if ($verification === null) {
            return 'invalid';
        }

        if ($verification->status === Verification::STATUS_COMPLETED) {
            return 'used';
        }

        if ($verification->status === Verification::STATUS_EXPIRED
            || ($verification->expires_at !== null && $verification->expires_at->isPast())) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Load a verification by its opaque token with its app and branding.
     */
    private function verificationFor(string $token): ?Verification
    {
        return Verification::query()
            ->with(['app.verificationFlow', 'app.verificationSetting', 'environment'])
            ->where('token', $token)
            ->first();
    }

    /**
     * Render the flow page for the given state.
     *
     * @param  array<string, mixed>|null  $outcome
     * @param  array{code: string, message: string}|null  $error
     */
    private function render(
        ?Verification $verification,
        string $state,
        ?array $outcome = null,
        ?array $error = null,
    ): Response {
        $app = $verification?->app;
        $flow = $app?->verificationFlow;
        $setting = $app?->verificationSetting;

        return Inertia::render('verify/flow', [
            'app' => ['name' => $app->name ?? 'Troth'],
            'branding' => [
                'logo_url' => $flow?->logo_path !== null ? asset('storage/'.$flow->logo_path) : null,
                'brand_color' => $flow->brand_color ?? '#0f172a',
                'consent_text' => $setting->consent_text ?? VerificationSetting::DEFAULT_CONSENT_TEXT,
                'success_redirect_url' => $flow?->success_redirect_url,
                'failure_redirect_url' => $flow?->failure_redirect_url,
            ],
            'query' => [
                'label' => $verification?->ageQuery(),
                'description' => $this->queryDescription($verification),
            ],
            'mode' => $verification?->environment?->name->value ?? 'production',
            'state' => $state,
            'error' => $error,
            'outcome' => $outcome,
            'token' => $verification?->token,
        ]);
    }

    /**
     * A short human description of the query, e.g. "confirm you are 18 or older".
     */
    private function queryDescription(?Verification $verification): ?string
    {
        if ($verification === null || $verification->query_type === null || $verification->query_value === null) {
            return null;
        }

        return match ($verification->query_type) {
            AgeQueryType::EqualAbove => 'Confirm that you are '.$verification->query_value.' or older.',
            AgeQueryType::EqualBelow => 'Confirm that you are '.$verification->query_value.' or younger.',
        };
    }

    /**
     * Determine when the result expires, honouring the app's re-verification cadence.
     */
    private function consentExpiry(int $appId): Carbon
    {
        $months = (int) VerificationSetting::query()->where('app_id', $appId)->value('re_verify_months');

        return $months > 0 ? Carbon::instance(now()->addMonths($months)) : Carbon::instance(now()->addDays(VerificationService::RESULT_TTL_DAYS));
    }

    /**
     * Record (or renew) the subject's consent and its event trail.
     */
    private function upsertConsent(Verification $verification, bool $result): void
    {
        $consent = Consent::query()
            ->where('app_id', $verification->app_id)
            ->where('subject_identifier', $verification->subject_identifier)
            ->first();

        $eventType = ConsentEvent::TYPE_GRANTED;
        $expiresAt = $this->consentExpiry($verification->app_id);

        if ($consent === null) {
            $consent = new Consent;
            $consent->app_id = $verification->app_id;
            $consent->subject_identifier = (string) $verification->subject_identifier;
        } elseif (! $consent->isRevoked() && ! $consent->isExpired()) {
            $eventType = ConsentEvent::TYPE_RENEWED;
        } else {
            $consent->revoked_at = null;
            $consent->status = Consent::STATUS_ACTIVE;
        }

        $consent->query_type = $verification->query_type;
        $consent->query_value = $verification->query_value;
        $consent->result = $result;
        $consent->expires_at = $expiresAt;
        $consent->save();

        ConsentEvent::create([
            'consent_id' => $consent->id,
            'type' => $eventType,
            'created_at' => now(),
        ]);
    }
}

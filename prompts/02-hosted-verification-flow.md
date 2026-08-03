# Phase 2 — Hosted Verification Flow (branded consent page with ID entry)

## Goal

Give developers a **hosted, brandable verification page** they can send their
end users to — the age-verification equivalent of Stripe Checkout. The
developer calls a new session endpoint, gets back a short-lived URL, redirects
their user to it, the user enters their Ethiopian national ID on a page
branded with the developer's logo/colors and consent text, the mock provider
verifies the age, and the user is redirected back with the verdict — while the
developer's server also receives a signed server-to-server callback.

This phase builds on Phase 1's provider layer. Do **not** implement real Fayda
— the flow uses the mock provider.

## Current state (context)

- `app/Models/VerificationFlow.php` — `logo_path`, `brand_color`,
  `success_redirect_url`, `failure_redirect_url`, `callback_url`. **These
  settings currently have no consumer** — this phase makes them real.
- `app/Models/VerificationSetting.php` — `consent_text`, `re_verify_months`
  (also currently config-without-consumer).
- `app/Models/Verification.php` — `app_id`, `status`, `query_type`,
  `query_value`, `result`, `created_at`; `$timestamps = false`.
- `app/Models/Consent.php` — consent records with status lifecycle
  (active/revoked/expired), `subject_identifier`, `expires_at`, `revoked_at`.
- `app/Models/ConsentEvent.php` — event log rows (`type`, `created_at`) that
  are currently **never written**. This phase starts writing them.
- `app/Enums/EnvironmentName.php` — `Sandbox` / `Production`.
- `routes/web.php` — everything except landing/legal is behind
  `auth + verified`. Public routes live at the top level (e.g. `/`).
- `app/Services/VerificationService.php` — Phase 1 refactored version
  resolving via `IdentityProviderRegistry`.
- `app/Http/Controllers/VerificationFlowController.php` — manages branding
  settings (this stays as-is).
- Frontend: shadcn/ui components in `resources/js/components/ui/*`,
  Tailwind v4, Inertia v3 (`Inertia::render`), Wayfinder route helpers.

## Deliverables

### 1. Schema changes

Migration `expand_verifications_table.php` (plus supporting tables):

- `verifications` gains: `app_environment_id` (FK, nullable),
  `token` (string, unique, index, nullable), `expires_at` (datetime, nullable),
  `completed_at` (datetime, nullable), `subject_identifier` (string, nullable),
  `callback_url` (string, nullable), `verification_url` — no, URL is derived.
  Status strings: `pending`, `completed`, `expired`, `failed`.
- Keep `Verification` timestamps off (existing behavior); derive display dates
  from `created_at`/`completed_at`.

The token is a **signed opaque token**: use `Str::random(40)` (or
`Str::uuid()`) stored raw — do not store an HMAC/encrypted version; the token
is short-lived (15 minutes) and single-use. Simplicity wins.

### 2. Session endpoint — `POST /v1/verify/session`

Route in `routes/api.php` (same auth + throttle middleware as the age
endpoint, named `api.v1.verify.session`):

- Auth: `VerificationService::authenticate()` (bearer secret) — reuse it.
- Body: `{age (1..130), type (equal_above|equal_below), subject (1..100 chars)}`
  where `subject` is the **developer's own opaque user identifier** for their
  end user (their internal user id / UUID). It is echoed back in callbacks and
  redirects, never resolved against identity tables.
- Validation identical in style to `ApiVerifyController` (preset checks run at
  session creation time — fail fast with `403 preset_not_allowed` if the
  query isn't enabled).
- Behavior: create a `pending` `Verification` row with token, expiry
  (`now()->addMinutes(15)`), app, environment (from the key), query, subject.
- Response `200`:

```json
{
  "session_id": "…",
  "verification_url": "https://your-domain.app/verify/<token>",
  "expires_at": "2026-08-05T12:15:00+00:00"
}
```

- Record the session creation in `api_calls` (method `POST`,
  path `/v1/verify/session`, status 200) so it shows in logs/usage.

New controller `app/Http/Controllers/ApiVerifySessionController.php`
(`__invoke`), mirroring the existing `ApiVerifyController` structure and its
error envelope. Move the request validation into a reusable
`app/Http/Requests/VerifyRequest.php` if that reduces duplication cleanly —
both endpoints must enforce identical rules (share it).

### 3. Public flow routes

In `routes/web.php` — **outside** the auth middleware group:

- `GET /verify/{token}` named `verify.flow` → renders the branded page.
- `POST /verify/{token}` named `verify.flow.submit` → processes the ID entry.

New controller `app/Http/Controllers/PublicVerificationController.php`:

- `show(string $token)`: load pending verification by token; if missing or
  expired → render an expired/invalid state page (professional, branded
  minimal, with a "return to <app name>" link if available). Load the app's
  `VerificationFlow` + `VerificationSetting` for branding. Render
  `Inertia::render('verify/flow', [...])` with a **public layout** (no
  sidebar/auth).
- `submit(string $token)`: validate `{national_id (digits, 15), consent (accepted:true)}`.
  Refuse without consent. Resolve the app's environment from the stored
  verification (which environment the session was created in), get the
  provider via `IdentityProviderRegistry`, call
  `provider->verify(new IdentityQuery($nationalId, $age, $type))`, map
  `IdentityProviderException`s to flow errors (render an inline error state on
  the page with the provider's message — e.g. "This national ID could not be
  verified. Please check and try again.").
- On success:
  - Mark verification `completed` (+ `completed_at`).
  - Upsert a `Consent` record for `(app_id, subject_identifier)`:
    `status = active`, `query_type`, `query_value`, `result`,
    `expires_at = now()->addDays(VerificationService::RESULT_TTL_DAYS)`
    (or respect `VerificationSetting.re_verify_months` when set), null out
    `revoked_at`. If an existing active consent exists, keep it active and
    refresh expiry — do not duplicate.
  - Write `ConsentEvent` rows: `type = 'granted'` (and `'renewed'` when
    refreshing an existing consent).
  - Record the call in `api_calls` (path `/verify/{token}`, 200) with the
    verdict payload.
  - **Redirect** (302) to `success_redirect_url` with query params
    `?verification=success&query_id=<id>&subject=<subject>&result=passed`.
    If no success URL is configured, render a branded success state page
    instead.
  - Send the server-to-server **callback** (below).
- On failure (verdict `false`, provider declined, invalid ID):
  - Mark verification `completed` (verdict false is still a completed check).
  - Redirect to `failure_redirect_url` with
    `?verification=failed&error=<error_code>`. If no URL, render a branded
    failure state page.
  - Do **not** create/refresh a consent for a failed check (no consent, no
    verification data — privacy-first). Record the call as a failed verdict
    (status 200 with `result: false`, or a `403`-style entry — decide and be
    consistent; recommend recording the call with the verdict false).
  - **Do send the callback** so the developer's backend learns about the
    attempt.

**Callback** (only for completed attempts, success or failure):

- `POST` to `callback_url` with JSON:
  `{query_id, subject, age, type, result, verified_at, expires_at, error?}`.
- Sign with the app's webhook-style secret? No — use the app's **flow
  callback secret**: generate and store one per flow on first use
  (`VerificationFlow` gains `callback_secret` — encrypted string column, add
  migration + regenerate mechanism next to the branding form, or reuse the
  existing webhook secret approach if you find an existing key vault; if the
  flow has no secret yet, generate `Str::random(48)` at session creation and
  store it on the flow). Header `X-Troth-Signature: sha256=<hmac_sha256(body, secret)>`
  — same scheme as webhook deliveries (`app/Jobs/SendWebhookDelivery.php`).
- Send **synchronously** with `Http::timeout(5)` (do not queue — the user is
  waiting); failures are logged, never fatal. Include
  `X-Troth-Event: verification.completed`.

### 4. Branding data contract

The `show` payload for the flow page:

- `app`: `{name}` (sanitized — the app name is developer-controlled; escape it)
- `branding`: `{logo_url (from Storage::disk('public')), brand_color,
  consent_text (default text if unset: the product's standard consent wording),
  success_redirect_url, failure_redirect_url}`
- `query`: `{label (e.g. "18+"), description}`
- `mode`: `'sandbox' | 'production'` (sandbox sessions render a subtle
  "test environment" banner so developers never confuse the two)

### 5. Frontend pages

- `resources/js/pages/verify/flow.tsx` — public page, **no auth layout**.
  - Centered card, max-w-md, mobile-first. Uses `brand_color` for the
    accent/button (via inline style), renders `logo_url` or the app logo
    fallback, shows the query ("Confirm you are 18 or older"), the national ID
    input (numeric, 15 digits, formatted `### ### ### ### ###`),
    consent checkbox with `consent_text`, and a submit button with loading
    state.
  - Sandbox banner (amber, subtle): "Test environment — no real identity
    checks are performed."
  - Inline error handling: reuse Inertia `useForm` + `errors`/`flash` props.
  - Expired/invalid state renders a minimal "This verification link has
    expired or is invalid" card.
- Optional small public layout wrapper `resources/js/layouts/verify-layout.tsx`
  (centered, plain background, no sidebar/footer links).

### 6. Security requirements

- Token: 15-minute expiry, single-use (mark `expired` on reuse attempt; a
  second `POST` → 410-style error state page), unique index.
- Rate limit the public flow: `RateLimiter` per-IP, e.g. 10 submissions /
  minute (`throttle:10,1` on the `POST` route is fine too).
- `national_id` is never stored — only the verdict and consent reference.
  It must not appear in `api_calls.request_payload` for the flow (the
  request payload should contain `subject` = developer's identifier, not the
  national ID).
- CSRF: the public `POST` is a cross-site form target by design; guard with
  the Inertia CSRF token (`X-CSRF-TOKEN` / `_token` — keep Laravel's session
  CSRF middleware on the web routes as-is).
- CSP: `Content-Security-Policy` header on the flow routes disallowing
  `frame-ancestors` (no clickjacking) — via middleware or route-level headers.
- Referrer policy: `no-referrer` on the flow pages so the developer's
  redirect URLs don't leak token/query data in `Referer`.

### 7. Docs updates

`resources/docs/`:

- `age-query-endpoint.md` — document `POST /v1/verify/session` with a curl
  example and a full redirect example.
- New section in `introduction.md` (or a new `hosted-flow.md` file added to
  `DocsService::SECTION_FILES`): how the hosted flow works, branding,
  callback signature verification, sandbox vs production banner.
- `error-codes.md` — add flow-specific errors (`session_expired`,
  `session_already_used`, `consent_required`, `invalid_national_id`).
- `quickstart.md` — add a "Try the hosted flow" step.

## Test list (Pest)

Create `tests/Feature/PublicVerificationFlowTest.php` (or split into two
files: session + flow):

1. Session creation: valid body → 200 with `verification_url`, pending row
   created; invalid preset → 403; unauthenticated → 401; bad body → 422.
2. Flow GET: valid token renders page with branding props; unknown token →
   invalid state; expired token → expired state; used token → expired state.
3. Flow POST success: valid national ID + consent → verification completed,
   consent created (or renewed with ConsentEvent `granted`/`renewed`),
   redirect to `success_redirect_url` with correct query params, callback
   POSTed (mock `Http::fake`) with valid HMAC header, `api_calls` recorded
   without the national ID.
4. Flow POST declined identity (`blocked` status ID): inline error render,
   verification completed, no consent, callback sent with `error`.
5. Flow POST unknown ID: inline error, no consent, no redirect.
6. Flow POST without consent checkbox → validation error, nothing written.
7. Flow POST with verdict `false` (e.g. age 16 ID vs 18+): redirect to
   failure URL with `error` param, consent **not** created.
8. Reuse of a consumed token → invalid state, 410/error page.
9. Rate limit: 11th request per minute → 429.
10. Sandbox sessions render the test banner (assert prop).
11. Callback HMAC verification helper is unit-tested (sign → verify → tamper
    fails). Create `app/Services/WebhookSignature.php` (or reuse the exact
    hashing from `SendWebhookDelivery` — extract shared helper if you find
    duplication).
12. `tests/Feature/ConsentsTest.php` — update if consent creation now flows
    through the hosted flow (existing tests must stay green).

## Acceptance criteria

- A developer can create a session, open the URL in an incognito window, enter
  a seeded national ID, tick consent, and land on their configured success URL
  with a `query_id`.
- The page is fully branded (logo, color, consent text) and mobile-friendly.
- Callbacks arrive signed; the docs show how to verify the signature.
- No national ID ever lands in the database or logs.
- Sandbox sessions visibly say "test environment".
- The `VerificationFlow` branding settings now actually do something.

## Polish / professionalism

- Copy on the page reads like a real age-gate: "You'll only be asked to
  confirm you're {18}+. Your national ID is never stored or shared with
  {app name}."
- Loading state on submit ("Checking your details…"), inline validation
  formatting for the 15-digit ID.
- Expired-link page includes the app name and a "Return to {app}" button.
- The callback payload includes a Unix-ish `occurred_at` and the provider
  reference for support.
- Update the support-ticket categories or docs only if needed — keep scope
  tight.

## Verification commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build   # if the new pages have type errors, fix them
```

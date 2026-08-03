# Phase 1 — Mock Identity Provider (production-grade mock of the Fayda integration)

## Goal

Replace the hardcoded `501 identity_provider_unavailable` stub for
non-sandbox environments with a **real provider abstraction** and a
professional, configurable **mock Fayda provider**. After this phase,
production-environment API keys work end-to-end against simulated Ethiopian
digital-ID identities, with realistic latency, typed failure modes, and a
clean seam (`App\Contracts\IdentityProvider`) where the real Fayda integration
can be dropped in later without touching controllers, services, or tests.

Do **not** implement a real Fayda/Federal Ministry of Ethiopia network
integration. The mock must look and behave like one.

## Current state (context)

- `app/Services/VerificationService.php` — `verify()` throws
  `VerificationException(501, 'identity_provider_unavailable', …)` for any
  environment that is not `EnvironmentName::Sandbox`. Sandbox verifications
  resolve the `subject` against the `sandbox_identities` table (seeded
  `fayda-test-a` = 20, `fayda-test-b`, `fayda-test-c`, `fayda-test-d`).
- `app/Http/Controllers/ApiVerifyController.php` — validates
  `{age (1..130), type (equal_above|equal_below), subject (<=100 chars)}`,
  authenticates via `VerificationService::authenticate()`, calls `verify()`,
  and records every call via `recordCall()` into `api_calls`.
- `app/Exceptions/VerificationException.php` — `$statusCode`, `$errorCode`,
  `$message`. The API envelope is `{"error": {"code": …, "message": …}}`.
- `app/Enums/EnvironmentName.php` — `Sandbox` and `Production`.
- `app/Models/SandboxIdentity.php` — `key`, `label`, `age`.
- `config/sandbox.php` — sandbox rate limiting.
- `routes/api.php` — single route `POST api/v1/verify/age` named
  `api.v1.verify.age`, throttled `throttle:30,1`.

## Deliverables

### 1. Provider contract

Create `app/Contracts/IdentityProvider.php`:

```php
interface IdentityProvider
{
    public function name(): string;
    public function isAvailable(): bool;
    public function verify(IdentityQuery $query): ProviderResult;
}
```

### 2. Value objects

Create `app/ValueObjects/IdentityQuery.php` — constructor promoted readonly
properties: `string $subject`, `int $age`, `AgeQueryType $type`.

Create `app/ValueObjects/ProviderResult.php` — readonly properties:
`bool $result`, `string $reference` (provider-side reference, e.g. a
`fayda-…` token), `DateTimeImmutable $verifiedAt`, plus an optional
`?string $rawProviderData = null` (JSON string for debugging/audit, must be
nulled out or omitted from any payload sent to clients).

### 3. Typed provider exceptions

Create `app/Exceptions/IdentityProvider/` with:

- `IdentityProviderException.php` (base, abstract — carries a public `string $errorCode`)
- `ProviderUnreachableException.php` → error code `identity_provider_unreachable`, HTTP 502
- `ProviderTimeoutException.php` → error code `identity_provider_timeout`, HTTP 504
- `SubjectNotFoundException.php` → error code `subject_not_found`, HTTP 404
- `ProviderDeclinedException.php` → error code `identity_provider_declined`, HTTP 403
- `ProviderRateLimitedException.php` → error code `identity_provider_rate_limited`, HTTP 429

Each maps to a stable HTTP status via a `statusCode(): int` method or a match
in the mapping service.

### 4. The mock provider — `app/Providers/Identity/MockFaydaProvider.php`

Implements `IdentityProvider`. This is the showpiece: it must behave like a
national ID provider backend:

**Identity resolution.** Query the `provider_identities` table (new, below) by
national ID:

- Match on `national_id` (normalized: strip spaces/hyphens, uppercase).
- If the identity does not exist → `SubjectNotFoundException`.
- If `status !== 'active'` (e.g. `blocked` / `lost`) → `ProviderDeclinedException`
  with a professional message ("the national ID presented is not valid for
  verification at this time").
- Compute the verdict against `birth_date` and the query: `EqualAbove` →
  age >= threshold, `EqualBelow` → age <= threshold. **Never return the birth
  date or age to the caller** — the provider result only carries the boolean
  verdict and a reference. (Privacy-first; mirrors the product promise.)
- Return `ProviderResult` with reference like `fayda-<uuid>` and
  `verifiedAt = now()`.

**Simulated network behavior** (configurable via `config/verify.php`):

- Sleep a random latency between `latency_min_ms` and `latency_max_ms`
  (defaults 40–120 ms) before responding.
- With probability `failure_rate` (default `0.0`), throw
  `ProviderUnreachableException` or `ProviderTimeoutException` (split 50/50).
- Simulate occasional provider hiccups deterministically for tests: if
  `latency_max_ms` exceeds `timeout_ms` for a request, throw
  `ProviderTimeoutException`.

**Availability.** `isAvailable()` returns true unless `config('verify.provider')`
is set to a value that resolves to a provider marked unavailable (kept simple:
mock is always available; the contract keeps `isAvailable()` for the real
integration later).

### 5. Sandbox adapter — `app/Providers/Identity/SandboxIdentityProvider.php`

Implements the same `IdentityProvider` contract, wrapping the existing
`sandbox_identities` behavior so **both environments flow through the same
contract**. This keeps the two from drifting and makes the sandbox feel like a
first-class environment rather than a special case:

- Resolve `subject` against `sandbox_identities`; unknown → `SubjectNotFoundException`.
- `EqualAbove` / `EqualBelow` comparison against `age`.
- Zero latency (or the same simulated latency, decided by config — default zero).
- `isAvailable()` always true.

### 6. Provider registry + configuration

Create `config/verify.php`:

```php
return [
    'provider' => env('VERIFY_PROVIDER', 'mock_fayda'), // 'mock_fayda' | 'sandbox'
    'providers' => [
        'mock_fayda' => [
            'class' => MockFaydaProvider::class,
            'latency_min_ms' => (int) env('VERIFY_PROVIDER_LATENCY_MIN', 40),
            'latency_max_ms' => (int) env('VERIFY_PROVIDER_LATENCY_MAX', 120),
            'timeout_ms' => (int) env('VERIFY_PROVIDER_TIMEOUT_MS', 1000),
            'failure_rate' => (float) env('VERIFY_PROVIDER_FAILURE_RATE', 0.0),
        ],
        'sandbox' => [
            'class' => SandboxIdentityProvider::class,
        ],
    ],
];
```

Create `app/Services/IdentityProviderRegistry.php`:

- `resolve(EnvironmentName $environment): IdentityProvider` — returns the
  sandbox adapter for `Sandbox`, and the configured provider (from
  `config('verify.provider')`) for `Production`. Throw a
  `RuntimeException` if the configured class doesn't implement the contract.
- Constructor-inject it everywhere via Laravel's container; register the two
  providers as singletons in `app/Providers/AppServiceProvider::register()`.

### 7. Refactor `VerificationService`

Modify `app/Services/VerificationService.php`:

- Inject `IdentityProviderRegistry` (constructor promotion).
- `verify()` becomes: authenticate key (unchanged) → `ensurePresetAllowed`
  (unchanged) → resolve provider for `$key->environment->name` →
  `try { $provider->verify(new IdentityQuery($subject, $age, $type)) }` and
  map `IdentityProviderException`s to `VerificationException`s with the
  provider's error code and status.
- The response payload shape stays exactly as before:
  `{result, query_id, verified_at, expires_at}` (`RESULT_TTL_DAYS = 90`).
  The provider reference should be recorded in `recordCall`'s
  `response_payload` (e.g. `provider_reference`) so it appears in request
  logs — **never** raw birth data.
- `recordCall()` unchanged in signature; optionally accept the provider
  reference to include in `response_payload`.
- `ApiVerifyController` stays untouched (the goal is that the whole
  controller/service seam survives the future real integration).

### 8. Schema + seeder

Migration `2026_08_xx_xxxxxx_create_provider_identities_table.php`:

- `id`, `national_id` (string, unique, index), `full_name` (string, nullable —
  used only for display in demo/tools, marked private), `birth_date` (date),
  `status` (string: `active`/`blocked`/`lost`, default `active`),
  `created_at`, `updated_at`.

Seeder (idempotent, appended to the existing database seeder or a new
`ProviderIdentitySeeder` registered in the existing seeder class): seed ~15
identities with **realistic-looking 15-digit Ethiopian national IDs** (the
Ethiopian National ID is a 15-digit number), e.g.:

```
185026948372154 — female, born 2003-04-11, active  (age 23 → passes 18+, fails 25+)
185038420915677 — male,   born 1997-08-02, active
185041759284301 — female, born 1985-01-19, active
185052613847902 — male,   born 1972-12-25, active
185063384725618 — female, born 2010-06-30, active (age 16 → fails 18+)
185074159362840 — male,   born 2005-03-17, active (age 21 → passes 18+)
185085237491603 — female, born 2016-09-08, active (age 9  → fails 18+)
185096184527339 — male,   born 2001-11-23, active
185107362594081 — female, born 1990-02-14, active
185118457203694 — male,   born 1965-07-07, active
185129384651207 — female, born 2007-05-21, active
185130274958316 — male,   born 2013-01-30, active
185141583902674 — female, born 1999-09-09, blocked
185152906478215 — male,   born 1994-04-01, lost
185163582719046 — female, born 2004-10-05, active
```

**Important:** ages must be computed from `birth_date` relative to "now", not
hardcoded, so the seeder stays correct forever. A dedicated `Faker`-based
factory `Database\Factories\ProviderIdentityFactory` must exist with states:
`active`, `blocked`, `lost`.

### 9. Status page component

Extend `config/status.php` (read it first; it lists `components`) so the
status checker records the identity provider as a component. Simplest
professional approach: add a `status:check` handler inside
`app/Console/Commands/CheckComponents.php` or a separate section in
`config/status.php` pointing at the provider availability check
(`GET /v1/health`-style internal check). Prefer: add a new component entry
with `key: 'identity_provider'` whose URL hits the API's health endpoint
described in Phase 5, or if you judge it premature, defer to Phase 5 — but
the `IdentityProvider::isAvailable()` hook must exist and be exercised by the
command at least once in a unit test. Keep it minimal; Phase 5 owns the public
status page.

## Test list (Pest)

Create/update:

1. `tests/Feature/IdentityProviderTest.php` — contract-level tests:
   - Mock provider resolves a known active ID for `equal_above` and
     `equal_below` with correct verdicts (boundary: exact-age match).
   - Unknown ID → `SubjectNotFoundException`.
   - `blocked`/`lost` status → `ProviderDeclinedException`.
   - Provider result never contains birth date/age/full name.
   - `failure_rate = 1.0` (config override) → unreachable/timeout exceptions.
   - Sandbox adapter still resolves `fayda-test-*` identities.
2. Update `tests/Feature/ApiVerifyTest.php`:
   - New tests: production-environment key + `VERIFY_PROVIDER=mock_fayda`
     verifies a real seeded ID successfully (200, `result: true` for 18+).
   - Production verify with unknown ID → 404 `subject_not_found`.
   - Production verify with `blocked` ID → 403 `identity_provider_declined`.
   - Existing sandbox tests must keep passing unchanged.
3. `tests/Unit/IdentityProviderRegistryTest.php` — registry resolves sandbox
   adapter for sandbox env and configured mock for production env; throws
   RuntimeException for an invalid class.
4. `tests/Feature/ApiKeyTest.php` — unchanged, must stay green.
5. `tests/Feature/StatusPageTest.php` — adjust if `status_checks` now include
   the provider component; otherwise unchanged.

## Acceptance criteria

- `POST /v1/verify/age` with a **production** key + a seeded national ID
  returns `200` with `{result, query_id, verified_at, expires_at}`.
- The `501 identity_provider_unavailable` branch is gone from the service
  (search the codebase for `501`).
- Switching `VERIFY_PROVIDER` between `mock_fayda` and `sandbox` only affects
  the production environment's behavior; sandbox behavior is unchanged.
- Latency is visible in `api_calls.response_time_ms` and reflects the
  simulated range (test with a config override, e.g. latency 100–200 ms).
- No birth date, age, or name is ever present in `api_calls` payloads.
- The registry + provider contract are the only files a real integration
  would need to add on top of (a new `FaydaProvider` class + config entry).

## Polish / professionalism

- Provider error messages read like production support copy, e.g.
  "The identity provider could not be reached. Please try again." — no
  "mock", "fake", or "stub" wording anywhere in user-facing strings.
- Log provider-side failures via `Log::warning` with the provider name and
  error code (no PII, no secrets).
- Add `provider_reference` to the API call response payload so support
  tickets can reference the provider-side token.
- Update `resources/docs/authentication.md` and
  `resources/docs/age-query-endpoint.md` to state that production
  environments resolve real (simulated) national IDs, and document the new
  error codes in `resources/docs/error-codes.md`.

## Verification commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

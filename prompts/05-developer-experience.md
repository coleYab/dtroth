# Phase 5 — Developer Experience (rate limits, OpenAPI, public status, log export)

## Goal

Make the portal feel like a serious developer product rather than a demo:

1. **Rate-limit headers** on the public API (`X-RateLimit-*` + `Retry-After`).
2. **OpenAPI 3.1 spec** served by the app + downloadable, with the docs
   updated to reference it.
3. **Public status page** (currently auth-gated) with a machine-readable
   JSON feed.
4. **API-log CSV export** on the app logs page (mirroring the existing
   audit-log export).

No new dependencies. No backend framework changes beyond the middleware
below.

## Current state (context)

- `routes/api.php` — `POST api/v1/verify/age` with `throttle:30,1` (and
  Phase 2/3 additions: `POST v1/verify/session`, `GET v1/verify/{query_id}`,
  `POST v1/consents/{id}/revoke`).
- `app/Http/Controllers/SandboxController.php` — already implements
  per-user rate limiting with `RateLimiter::remaining()` and a `retryAfter`
  surfaced to the UI. The **public API** currently returns no headers.
- `app/Http/Controllers/StatusController.php` — `status.index` route is
  inside the `['auth', 'verified']` group in `routes/web.php` (line ~94).
- `app/Console/Commands/CheckComponents.php` — writes `status_checks`
  every 5 min (`config/status.php` defines components; `status_checks` has
  `component, status, response_time_ms, checked_at`).
- `app/Http/Controllers/AuditLogController.php::export` — existing CSV
  export pattern (`response()->streamDownload`, fputcsv, 10k row cap,
  filename `audit-log-<date>.csv`).
- `resources/js/pages/apps/logs.tsx` — app request log page
  (`ApiCallController::index`), paginated with `ApiCallResource`.
- `resources/js/pages/status.tsx` — status page UI (currently behind auth).
- `resources/docs/` — `rate-limits.md`, `error-codes.md`, `introduction.md`,
  `authentication.md`, `age-query-endpoint.md`, `webhooks.md`, `quickstart.md`;
  `app/Services/DocsService.php::SECTION_FILES` is the nav order.

## Deliverables

### 1. Named rate limiter + headers middleware

- In `app/Providers/AppServiceProvider::boot()` (or a dedicated
  `RateLimitServiceProvider` if cleaner — follow existing provider layout),
  define a named limiter:

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(config('verify.api_requests_per_minute', 60))
        ->by($request->user()?->id ?: $request->ip());
});
```

  Add `api_requests_per_minute` to `config/verify.php` (env
  `VERIFY_API_REQUESTS_PER_MINUTE`, default 60).

- Replace `throttle:30,1` on the API routes with the named limiter:
  `->middleware('throttle:api')`.
- New middleware `app/Http/Middleware/EnsureRateLimitHeaders.php`, registered
  in `bootstrap/app.php` (read how the app registers middleware aliases
  first — `bootstrap/app.php` uses the Laravel 11+ style) and applied to the
  API route group. It must:
  - Read the limiter's state for the request (`RateLimiter::remaining($key,
    $limit)` / `RateLimiter::availableIn($key)`) and set
    `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
    (epoch seconds).
  - On 429 responses, ensure `Retry-After` is present (Laravel's throttle
    already sends it — leave it; don't double-set).
  - Never fail the request; if limiter state can't be computed, continue
    without headers.
- Update `resources/docs/rate-limits.md`: document the named limiter,
    the new headers, the reset semantics, and the per-route limits table
    (verify, session, lookup, revoke).

### 2. OpenAPI 3.1 spec

- Author `public/openapi.yaml` (hand-written, ~200-300 lines) covering:
  - Info: title "Troth Verify API", version `1.0.0`, description pointing at
    the docs.
  - `servers`: from config — single server block with `url` =
    `config('app.url')`-derived API base (document how to change it; the YAML
    is static, so use a reasonable placeholder like
    `https://api.example.com` only if no better option — prefer generating
    the spec with the current APP_URL at build time via a small artisan
    command OR keep it static and document `api.example.com` as
    configurable).
  - Security scheme: `bearerAuth` (HTTP bearer).
  - Paths: `POST /v1/verify/age`, `POST /v1/verify/session`,
    `GET /v1/verify/{query_id}`, `POST /v1/consents/{id}/revoke` — each with
    request body schemas, response examples, and the `429` + error envelope
    documented once via `components.responses`.
  - Components: `Error` envelope, `VerificationResult`, `SessionCreated`,
    `ConsentState`, parameter schemas.
  - Tags: `Verification`, `Consents`.
- Serve it: route `GET /docs/openapi` (inside the existing auth'd docs
  group) returning the YAML as `text/yaml` via
  `response()->file(public_path('openapi.yaml'))` (or `DownloadResponse`).
  Add a controller action to `app/Http/Controllers/DocsController.php` and a
  "Download OpenAPI spec" link on the docs page UI
  (`resources/js/pages/docs*.tsx` — find where the sidebar/actions render).
- Optional but recommended: also serve `GET /docs/openapi.json` (convert
  YAML→JSON at runtime with Symfony's `Yaml` + `json_encode` — no new
  dependency; Symfony Yaml ships with Laravel).

### 3. Public status page + JSON feed

- **Route changes** in `routes/web.php`:
  - Move `status.index` out of the `auth + verified` group so anyone can
    view `GET /status` (landing-page footer already links to status — make
    the link actually usable by unauthenticated visitors).
  - Add `GET /status.json` (public) named `status.json` returning machine
    data.
- **`StatusController` updates**:
  - `index()` must handle guests: load `status_checks` (latest check per
    component), `status_incidents` (open + recent resolved), and derive
    per-component uptime % for the last 30 days from `status_checks`
    (ok+degraded vs down). Pass the same props shape it currently sends so
    the React page needs minimal changes — add `is_authenticated` prop and
    hide the auth-only extras (if any) for guests.
  - `statusJson()` (`__invoke` or named method): return
    `{status: 'operational'|'degraded'|'down', updated_at, components: [{key,
    status, response_time_ms, uptime_30d, checked_at}], incidents: [...open
    ones...]}`. Cache for 60 seconds (`Cache::remember`) to avoid hammering
    SQLite.
  - Privacy: `status.json` must not leak internal details (no IPs, no
    internal URLs, no app names).
- **Frontend** `resources/js/pages/status.tsx`:
  - Works when unauthenticated (no sidebar assumptions — check its current
    layout; if it uses the app shell, wrap in the public layout for guests
    or render a self-contained page).
  - Add a "Last 30 days" uptime bar per component (simple progress-style
    bar via existing `progress.tsx` component).
  - Link to `/status.json` ("API feed") for integrators.
- **Phase 1 hookup**: if Phase 1 deferred the provider component, add
  `identity_provider` to `config/status.php` now (check URL + expected
  status; use a local probe or the API health check — keep it simple and
  non-fragile, e.g. a `/up` liveness route returning 200, or drop the
  component if it can't be checked reliably from shared hosting — decide
  during implementation and document it).

### 4. API-log CSV export

- `app/Http/Controllers/ApiCallController.php` — add `export()` mirroring
  `AuditLogController::export` exactly (same query filters as `index`:
  status, date range, subject — read the controller's current filters
  first), columns:
  `Timestamp, Status, Method, Path, Query type, Query value, Result,
  Response time (ms)`.
- Filename: `api-log-<date>.csv`. Streamed download, 10k cap, same
  `Content-Type: text/csv`.
- Route: `GET apps/{app}/logs/export` named `apps.logs.export` (inside the
  auth group).
- Frontend `resources/js/pages/apps/logs.tsx`: add an "Export CSV" button
  (mirror the audit-logs page's button — read `resources/js/pages/audit-logs.tsx`
  to match its pattern and copy the `Link`/download approach).

### 5. Docs updates

- `resources/docs/introduction.md` — mention the OpenAPI spec + public
  status page + status feed.
- `resources/docs/rate-limits.md` — updated (section 1).
- `resources/docs/error-codes.md` — ensure `429` retry semantics documented.
- Quickstart unchanged unless a step references rate limits.

## Test list (Pest)

1. `tests/Feature/ApiRateLimitHeadersTest.php`:
   - Successful API call returns `X-RateLimit-Limit` and
     `X-RateLimit-Remaining` headers.
   - Exceeding the limit (call the endpoint 61 times with a fixed IP via
     `$this->withHeaders(...)` + config override to a small limit, e.g.
     `config(['verify.api_requests_per_minute' => 3])`) → 429 with
     `Retry-After`.
   - Session/lookup/revoke routes also emit headers.
2. `tests/Feature/OpenApiSpecTest.php`:
   - `GET /docs/openapi` returns 200 + `text/yaml` content-type for
     authenticated users.
   - The YAML parses (`Symfony\Component\Yaml\Yaml::parse`) and contains
     paths for `/v1/verify/age`, `/v1/verify/session`,
     `/v1/verify/{query_id}`, `/v1/consents/{id}/revoke`, plus the
     `bearerAuth` security scheme.
3. `tests/Feature/StatusPageTest.php` — update:
   - Guests can GET `/status` (200, no auth redirect).
   - `/status.json` returns the expected shape for guests (operational
     status, components array), cached.
   - Authenticated users still get the same page.
4. `tests/Feature/ApiCallExportTest.php`:
   - Export CSV returns streamed download with header row + rows; respects
     status/date filters; capped.
5. Existing suites must stay green (`AppsTest`, `DocsPageTest`,
   `UsageLogsTest`).

## Acceptance criteria

- `curl -i` against `/v1/verify/age` shows the three `X-RateLimit-*` headers;
  hammering it yields `429` + `Retry-After`.
- The OpenAPI file describes every public endpoint and opens cleanly in
  Swagger UI / Redoc (validate by parsing the YAML in a test).
- A logged-out browser can view `/status` and `curl /status.json` returns
  JSON.
- App logs export produces a valid CSV with a header row.
- All docs pages that reference the API are consistent with the spec.

## Polish / professionalism

- The 429 body stays in the standard envelope
  (`{"error":{"code":"too_many_requests",...}}`) so SDKs can parse it.
- `status.json` includes `uptime_30d` per component — operators love this.
- Export uses `ob_clean`-safe streaming and the same naming convention as
  the audit export (`<resource>-<date>.csv`).
- The docs "rate-limits" table lists the exact limiter per endpoint so
  developers can build their own backoff correctly.

## Verification commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

# Phase 3 — Result Lookup & Consent Lifecycle APIs

## Goal

Close the loop on the verification lifecycle so developers never have to
re-verify a user who already has a valid verdict:

1. **`GET /v1/verify/{query_id}`** — fetch a stored verification result by its
   `query_id` (the ID returned by `POST /v1/verify/age` and by the hosted
   flow). Developers can cache `query_id` and re-check on every login until
   `expires_at` instead of forcing the user through the flow again.
2. **Consent revocation API** — let developers revoke a user's consent
   server-side (`POST /v1/consents/{id}/revoke`), which records the event and
   (in Phase 4) fires `consent.revoked` webhooks.
3. **Consent expiry scheduler** — a scheduled command that marks consents
   past their expiry as `expired` and (in Phase 4) fires `verification.expired`
   webhooks.
4. **Consent event timeline** — populate the currently-unused
   `consent_events` table and surface the timeline in the dashboard
   (`apps/consents/show.tsx`).

## Current state (context)

- `app/Http/Controllers/ApiVerifyController.php` returns
  `{result, query_id, verified_at, expires_at}`. The `query_id` is a
  `Str::uuid()` generated at verify-time in
  `VerificationService::verify()` (Phase 1 version) — **it is not persisted
  anywhere yet**, so it can't be looked up. This phase persists it.
- `app/Models/Verification.php` — `app_id, status, query_type, query_value,
  result, created_at`; `$timestamps = false`. Phase 2 added token/session
  columns. It gains a `query_id` (uuid) column.
- `app/Models/Consent.php` — full lifecycle model with
  `STATUS_ACTIVE/REVOKED/EXPIRED`, `isRevoked()`, `isExpired()`,
  `scopeActive()`, `events()` HasMany to `ConsentEvent`.
- `app/Models/ConsentEvent.php` — `consent_id`, `type`, `created_at`; never
  written today. Phase 2 writes `granted`/`renewed`. This phase adds
  `revoked`/`expired` and a dashboard timeline.
- `app/Http/Controllers/ConsentController.php` — `index` + `show` only
  (dashboard, view-only).
- `app/Http/Resources/ConsentResource.php` — exists.
- `routes/console.php` — scheduling with `Schedule::command(…)->…()`.
- `app/Console/Commands/` — commands use `#[Signature]`/`#[Description]`
  attributes (see `RollupUsage`, `CheckComponents`).
- `resources/js/pages/apps/consents/show.tsx` — consent detail page
  (currently no event timeline).

## Deliverables

### 1. Persist `query_id`

- Migration `expand_verifications_table.php` (if not already extended in
  Phase 2 — coordinate): add `query_id` (uuid string, unique, index, nullable —
  nullable for legacy rows).
- In `VerificationService::verify()` (Phase 1 version), generate the
  `query_id` **before** resolving and persist a `Verification` row at
  verification time (status `completed`). Keep the API response shape
  byte-identical. If Phase 2 already created verification rows for hosted
  sessions, reuse that flow: the session's `query_id` is its UUID.
- `recordCall()` keeps storing the payload for logs; the persisted row is
  what the lookup endpoint reads.

### 2. Lookup endpoint — `GET /v1/verify/{query_id}`

Route in `routes/api.php` named `api.v1.verify.show`, same middleware as the
age endpoint.

New controller `app/Http/Controllers/ApiVerificationLookupController.php`
(`__invoke`):

- Auth: `VerificationService::authenticate()`.
- Load `Verification` where `query_id` matches **and `app_id` belongs to the
  key's app** — cross-app lookups must 404 (never leak).
- NotFound (or missing query_id) → 404 `not_found`.
- Expired result (`expires_at` past) → 410 with `expired` code (distinct from
  not-found; see docs).
- Success → 200:

```json
{
  "query_id": "…",
  "result": true,
  "age": 18,
  "type": "equal_above",
  "verified_at": "…",
  "expires_at": "…",
  "expired": false
}
```

- No provider call is made — this is pure storage lookup. Cache-friendly:
  developers hit this instead of the verify endpoint.

### 3. Consent revocation API — `POST /v1/consents/{id}/revoke`

Route in `routes/api.php` named `api.v1.consents.revoke` (POST).

New controller `app/Http/Controllers/ApiConsentController.php`:

- Auth via `authenticate()`.
- Load consent by `id` where `app_id` matches the key's app; else 404.
- Idempotent: revoking an already-revoked consent returns 200 with the
  current state (no-op), revoking an expired consent is allowed (still marks
  revoked — it supersedes).
- On success: set `status = revoked`, `revoked_at = now()`, write
  `ConsentEvent` `type = 'revoked'`. Return 200:

```json
{ "consent_id": 12, "status": "revoked", "revoked_at": "…" }
```

- This is the trigger that Phase 4's `consent.revoked` webhook listens to.

### 4. Consent expiry scheduler

New command `app/Console/Commands/ExpireConsents.php`:

- `#[Signature('consents:expire')]`, `#[Description('Mark expired consents and record the event')]`.
- Query consents `whereNull('revoked_at')` and `expires_at < now()` not yet
  marked expired (`status !== expired` — add a `status` guard; the `status`
  column already exists).
- For each: `status = expired`, write `ConsentEvent` `type = 'expired'`.
- Output a summary line like `[consents:expire] Expired 3 consents`.
- Schedule in `routes/console.php`: `Schedule::command(ExpireConsents::class)
  ->hourly()->withoutOverlapping();`
- Phase 4 hooks the `verification.expired` webhook here; this phase just
  marks + logs events.

### 5. Consent event timeline in the dashboard

- Update `app/Http/Controllers/ConsentController.php::show` to eager-load
  `events` and pass them through `ConsentResource` (or a small new
  `ConsentEventResource` — follow `ConsentResource`'s existing style).
- Update `resources/js/pages/apps/consents/show.tsx`:
  - New "Consent timeline" section listing events newest-first with a
    relative timestamp (`relative-time.tsx` component exists), event type
    badge, and a friendly label map (granted → "Consent granted",
    renewed → "Consent renewed", revoked → "Consent revoked",
    expired → "Consent expired").
  - Show current status chip (`consent-status-badge.tsx` exists).
  - Revoked consents display the `revoked_at`; expired show `expires_at`.
- No API changes beyond the resource — the dashboard reads what the API can
  now also do.

### 6. Docs updates

- `resources/docs/age-query-endpoint.md`: document `GET /v1/verify/{query_id}`
  with curl + response examples and the 404/410 semantics.
- New `resources/docs/consents.md` (add to `DocsService::SECTION_FILES`):
  consent model, revocation endpoint, expiry behavior, and the guarantee that
  no PII is stored.
- `resources/docs/error-codes.md`: add `not_found`, `expired` (410), and the
  revocation endpoint notes.

## Test list (Pest)

Create/update:

1. `tests/Feature/ApiVerificationLookupTest.php`:
   - Lookup own app's query_id → 200 with stored verdict, correct fields.
   - Lookup another app's query_id → 404 (no leak).
   - Unknown query_id → 404 `not_found`.
   - Expired result → 410 `expired` (seed a Verification with past
     `expires_at` via factory).
   - Unauthenticated → 401; revoked key → 401.
   - Lookup performs no provider call (assert `IdentityProviderRegistry`
     spy/mock is never invoked — use a fake or assert via `expects` on the
     registry binding).
2. `tests/Feature/ApiConsentRevokeTest.php`:
   - Revoke own app's consent → 200, status `revoked`, `revoked_at` set,
     ConsentEvent `revoked` written.
   - Revoke another app's consent → 404.
   - Double revoke → 200 idempotent, single event row.
   - Unknown consent → 404.
3. `tests/Feature/ExpireConsentsTest.php`:
   - Command marks past-expiry consents `expired` + writes events; active
     consents untouched; already-expired untouched (idempotent); revoked
     consents untouched.
4. Update `tests/Feature/ConsentsTest.php`:
   - `show` page now includes the timeline (assert event rows render).
   - Seed/update factories: `ConsentFactory` gains states `active`,
     `revoked`, `expired`; `VerificationFactory` gains `query_id` support
     and a `completed` state.
5. `tests/Unit/` — if the query_id generation is extracted to a helper, add
   a unit test for it (otherwise cover through feature tests).

## Acceptance criteria

- Every successful verification (API or hosted flow) leaves a persisted,
  retrievable record with its `query_id`.
- Lookups are app-scoped and return no data across apps.
- Revocation is idempotent, evented, and ready for Phase 4's webhook.
- `php artisan consents:expire` is safe to run repeatedly and produces
  exactly one `expired` event per consent.
- The consent detail page shows a human-readable lifecycle timeline.

## Polish / professionalism

- Lookup responses include an `expired` boolean plus the `expires_at` so
  clients can branch without parsing dates.
- Revocation response mirrors the consent resource shape so SDK users get a
  consistent object.
- The timeline labels match the webhook event names 1:1
  (`granted`/`renewed`/`revoked`/`expired`) — one vocabulary across
  dashboard, API, and webhooks.
- Command output is greppable (`consents:expire` prefix) for cron logs.

## Verification commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

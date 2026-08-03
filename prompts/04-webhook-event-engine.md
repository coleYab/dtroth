# Phase 4 — Webhook Event Engine (automatic dispatch)

## Goal

Make webhooks actually fire. Today the portal lets developers configure
webhooks for `verification.completed`, `verification.expired`, and
`consent.revoked`, but **nothing dispatches them automatically** — the only
way to get a delivery is the manual "test" button. This phase builds a
`WebhookDispatcher` service and wires it to every real lifecycle event so
developers can build event-driven integrations:

- `verification.completed` — every successful age check (API verify +
  hosted flow completion).
- `verification.expired` — when a stored verification result passes its
  `expires_at` (piggybacks on Phase 3's expiry work where applicable).
- `consent.revoked` — when a developer revokes consent via the Phase 3 API
  (or a future hosted consent-withdraw flow).

Do **not** rework the existing signing, retry, or delivery logic
(`app/Jobs/SendWebhookDelivery.php` already sends HMAC-signed deliveries with
retries and `WebhookDelivery` records) — this phase only adds **triggering**.

## Current state (context)

- `app/Models/Webhook.php` — `url`, `secret` (encrypted), `isFailing()`,
  `events()` BelongsToMany via `webhook_event` pivot.
- `app/Models/WebhookEvent.php` — constants: `VERIFICATION_COMPLETED =
  'verification.completed'`, `VERIFICATION_EXPIRED =
  'verification.expired'`, `CONSENT_REVOKED = 'consent.revoked'`; seeded rows
  with `name` = the constant values.
- `app/Models/WebhookDelivery.php` — `webhook_id, event, payload,
  response_status, response_body, attempts, status, delivered_at`.
- `app/Jobs/SendWebhookDelivery.php` — sends with `X-Troth-Signature:
  sha256=<hmac(sha256, raw body, secret)>` and `X-Troth-Event` headers,
  increments attempts, marks delivered/failed; maximum attempts before
  failure.
- `app/Http/Controllers/WebhookController.php` — CRUD + manual test send
  (`retryDelivery` dispatches the job); `create` shows the secret once.
- `app/Services/VerificationService.php` — Phase 1 version, entry point for
  API verifications; Phase 3 version persists `Verification` rows with
  `query_id` + `expires_at` (`RESULT_TTL_DAYS = 90`).
- `app/Http/Controllers/PublicVerificationController.php` — Phase 2 hosted
  flow completion (writes Verification + Consent + ConsentEvents).
- `app/Http/Controllers/ApiConsentController.php` — Phase 3 revocation
  (writes ConsentEvent `revoked`).
- `app/Console/Commands/ExpireConsents.php` — Phase 3 expiry command.
- `routes/console.php` — hourly `consents:expire`.
- `resources/js/pages/apps/webhooks/*` — webhook management UI.

## Deliverables

### 1. Dispatcher service — `app/Services/WebhookDispatcher.php`

```php
public function dispatch(string $event, App $app, array $payload, ?string $idempotencyKey = null): void
```

- Query webhooks belonging to `$app` that subscribe to `$event`
  (`whereHas('events', fn ($q) => $q->where('name', $event))`).
- **Idempotency:** if `$idempotencyKey` is provided (e.g. the `query_id`),
  skip creating a delivery when a `WebhookDelivery` already exists for the
  same `(webhook_id, event)` with a payload containing that key — implement
  as a `unique(webhook_id, event, idempotency_key)` nullable column on
  `webhook_deliveries` (migration `expand_webhook_deliveries_table.php`).
  This protects against double-fires when jobs are retried or flows replay.
- For each subscribed webhook: create `WebhookDelivery` (`status` =
  `pending`, `attempts = 0`, store `payload` JSON, `idempotency_key`), then
  `SendWebhookDelivery::dispatch($delivery)`.
- Update `$webhook->last_triggered_at` for each webhook triggered.
- **Never throw** on empty subscriptions or failed creation — logging only.
- Also expose a `hasSubscribers(string $event, App $app): bool` helper if
  call sites want to skip expensive work when nobody's listening (optional).

### 2. Event payload contracts

Keep one vocabulary across all events (matching Phase 3's timeline labels):

**`verification.completed`**

```json
{
  "event": "verification.completed",
  "query_id": "…",
  "subject": "<developer's opaque identifier>",
  "age": 18,
  "type": "equal_above",
  "result": true,
  "verified_at": "…",
  "expires_at": "…",
  "environment": "sandbox|production",
  "occurred_at": "…"
}
```

**`verification.expired`**

```json
{
  "event": "verification.expired",
  "query_id": "…",
  "subject": "…",
  "expired_at": "…"
}
```

**`consent.revoked`**

```json
{
  "event": "consent.revoked",
  "consent_id": 12,
  "subject": "…",
  "revoked_at": "…"
}
```

Document these shapes in `resources/docs/webhooks.md` (the file already
documents headers/signatures — add payload reference + event glossary).

### 3. Wire the call sites

- **`VerificationService::verify()`** (API age endpoint): after a successful
  verification and row persistence (Phase 3), dispatch
  `verification.completed` with `$idempotencyKey = $queryId`. Fire **only**
  on verdict success *and* verdict-false completions? — Decision: fire for
  every completed check (`result: false` included; the developer needs the
  negative outcome too), payload carries the boolean.
- **`PublicVerificationController::submit()`** (hosted flow): on completion
  (success or failure), dispatch `verification.completed` with the same
  shape. Since the synchronous callback already notifies the developer's
  server, this webhook is for apps that prefer webhooks over callbacks —
  both go out.
- **`ApiConsentController::revoke()`**: dispatch `consent.revoked`
  (`$idempotencyKey = "consent:{$consent->id}"`). Fire even when the consent
  was already revoked (idempotent revoke = still notifying is acceptable;
  or skip if no state change — pick one, document it, test it; recommend
  firing only on state change).
- **`ExpireConsents` command**: for each consent it marks `expired`, dispatch
  `verification.expired` for each of its verification records (or for the
  consent's latest verification — read Phase 3's data model and use the
  most recent completed verification per expired consent).

### 4. Dashboard & delivery logs

The existing `apps/webhooks/logs` page shows deliveries — no structural
changes needed. Small polish (optional but recommended):

- Delivery rows show the `event` (already stored) — verify the page displays
  it; if not, add it.
- Add an "Events" readout on the app dashboard or webhooks page: count of
  deliveries in the last 7 days per event. Only if cheap — do not build new
  charts.

### 5. Docs updates

- `resources/docs/webhooks.md`:
  - Replace any "delivered manually / test only" wording with the real
    trigger list ("sent automatically when…").
  - Add the event payload reference (section 2 shapes).
  - Document the idempotency guarantee (`query_id`-based dedupe).
  - Keep the existing HMAC verification code samples untouched.

## Test list (Pest)

Create/update:

1. `tests/Unit/WebhookDispatcherTest.php` (or feature — pick per existing
   style; recommend feature with `Http::fake`):
   - Dispatch with no subscribers → no delivery rows, no jobs, no exception.
   - Dispatch with subscribers → delivery row per webhook, job queued
     (`Queue::fake` / assert `SendWebhookDelivery` pushed with the right
     payload + `last_triggered_at` updated).
   - Idempotency: second dispatch with same key → no duplicate delivery.
2. `tests/Feature/WebhookTriggerTest.php` (integration):
   - API verify (production + sandbox) with a webhook subscribed to
     `verification.completed` → delivery created with correct payload
     (assert `payload.event`, `query_id`, `result`); `Http::fake` + run the
     queued job (`Queue::fake` then `dispatchSync` or assert on delivery
     row — prefer asserting the delivery row + `(new SendWebhookDelivery($d))->handle()`).
   - Hosted flow completion → `verification.completed` delivery created.
   - Revoke consent → `consent.revoked` delivery created; double revoke
     does not duplicate (matches the chosen state-change behavior).
   - `consents:expire` run → `verification.expired` deliveries created for
     subscribed apps.
   - Unsubscribed webhook receives nothing.
   - HMAC header correctness is already covered by existing WebhookTest —
     keep those green.
3. Existing `tests/Feature/WebhookTest.php` — update only where behavior
   changed (e.g. test-send button flow still works).

## Acceptance criteria

- Real verifications, revocations, and expirations now produce deliveries
  automatically — no manual action needed.
- No duplicate deliveries for the same `query_id` / consent id even when the
  underlying operation runs twice.
- Every existing webhook test stays green.
- The webhooks docs page accurately describes when each event fires.

## Polish / professionalism

- Delivery payloads include `environment` so developers can filter sandbox
  noise in their own systems.
- `occurred_at` everywhere (ISO-8601, UTC) — consistent with `verified_at`.
- The dispatcher logs a single line per dispatched delivery:
  `[webhooks] verification.completed → https://… (webhook #12, delivery #34)`
  without the payload body (no PII in logs).
- Event names are the single source of truth in `WebhookEvent` constants —
  no string literals at call sites.

## Verification commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

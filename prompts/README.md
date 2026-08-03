# Troth Verify — Phase Build Prompts

This folder contains one detailed, agent-ready prompt per build phase. Each file is
a complete spec: what to build, exactly which files to create or modify (with
paths), schema changes, routes, contracts, test requirements, acceptance
criteria, and the "professional polish" details that keep the product looking
finished rather than demo-quality.

The real Fayda digital-ID integration is intentionally **not** implemented.
Instead we build a configurable, realistic **mock identity provider** behind a
proper provider abstraction so the real integration can be swapped in later
without touching the rest of the system.

## Phase order

Run the phases in order — each one depends on the ones before it.

| Phase | File | Deliverable | Depends on |
| ----- | ---- | ----------- | ---------- |
| 1 | `01-mock-identity-provider.md` | Provider abstraction + professional mock provider; production envs verify against simulated Fayda identities | — |
| 2 | `02-hosted-verification-flow.md` | Hosted branded consent page (ID entry flow) + `POST /v1/verify/session` | 1 |
| 3 | `03-result-and-consent-apis.md` | `GET /v1/verify/{query_id}`, consent revocation API, consent expiry scheduler, consent event timeline | 1 |
| 4 | `04-webhook-event-engine.md` | Automatic webhook dispatch wired to real events | 1, 2, 3 |
| 5 | `05-developer-experience.md` | Rate-limit headers, OpenAPI spec, public status page, API-log CSV export | 1–4 |
| 6 | `06-notifications-and-teams.md` | Email alerts + team/roles model *(future work — write the spec, do not implement)* | all |

## How to use

1. Pick the next unfinished phase file.
2. Hand the file to your coding agent (or follow it yourself) **as a single
   instruction**: "Implement everything in `prompts/02-hosted-verification-flow.md`."
3. The agent must run the phase's verification commands before declaring done:
   `php artisan test --compact`, `vendor/bin/pint --dirty --format agent`.
4. After a phase lands, mark it off in the checklist table above.

## Global conventions (apply in every phase)

The codebase is Laravel 13 (PHP 8.5) + Inertia.js v3 + React 19 + Tailwind CSS v4,
SQLite, Pest 5, Fortify, Laravel Wayfinder. Follow these rules in every phase:

- **Tests are mandatory.** Every behavior needs a Pest feature test (or unit
  test where appropriate) in `tests/Feature` or `tests/Unit`. Run
  `php artisan test --compact` (optionally `--filter=…`) after the work.
- **Style.** Run `vendor/bin/pint --dirty --format agent` before finishing.
  Use PHP 8 constructor promotion, explicit return types, typed parameters,
  PHPDoc array shapes, and curly braces for all control structures.
- **Models.** Follow the existing convention: `#[Fillable([…])]` /
  `#[Hidden([…])]` attributes, `casts()` method, `HasFactory` trait, PHPDoc
  `@property` blocks, `Database\Factories\*Factory` factories with useful
  states. Create factories via `php artisan make:model --factory`.
- **Migrations.** Use `php artisan make:migration`. Name alterations
  `expand_<table>_table` like `2026_08_02_071244_expand_consents_table.php`.
  Keep seeders idempotent (the app's seeder already is).
- **Routes.** API routes go in `routes/api.php` with named routes
  (`api.v1.*`). Web routes go in `routes/web.php` with named routes. Wayfinder
  will regenerate client-side route functions automatically via the Vite
  plugin — frontend code must import route helpers from `@/routes` or actions
  from `@/actions`, never hardcode URLs.
- **Frontend.** Reuse existing components (`resources/js/components/ui/*`,
  `code-block.tsx`, `page-header.tsx`, `empty-state.tsx`, `stat-card.tsx`,
  `result-badge.tsx`, `payload-view-dialog.tsx`, etc.). New pages go in
  `resources/js/pages/...`. Use `Inertia::render()` from controllers.
- **Observers** are registered manually in
  `app/Providers/AppServiceProvider::boot()` (e.g. `App::observe(AppObserver::class)`).
- **Scheduling** lives in `routes/console.php` using `Schedule::command(…)->…()`.
  Console commands use the `#[Signature('name')` and `#[Description('…')]`
  attributes (see `app/Console/Commands/CheckComponents.php`).
- **Error responses** on the API use the existing envelope:
  `{"error": {"code": "…", "message": "…"}}` with `App\Exceptions\VerificationException`
  (fields: `$statusCode`, `$errorCode`, `$message`).
- **Webhooks already exist** for CRUD + manual test sends with HMAC-SHA256
  `X-Troth-Signature` headers. Phase 4 wires them to automatic dispatch; do not
  rework the signing or retry logic unless a phase explicitly says so.
- **Secrets.** Never log or expose secrets. API key secrets are stored hashed
  (`secret_hash`), webhook secrets encrypted (`'secret' => 'encrypted'`).
- **No new dependencies** unless a phase explicitly calls for one and the user
  approves. This app runs on shared hosting with SQLite — keep that in mind.
- **Docs** (in `resources/docs/*.md`, rendered by `DocsService`) must be kept in
  sync whenever an endpoint, error code, or behavior changes.

## Definition of done (every phase)

1. All listed files created/modified, following the conventions above.
2. All tests from the phase's test list pass (`php artisan test --compact`).
3. `vendor/bin/pint --dirty --format agent` reports no changes needed.
4. Frontend builds cleanly (`npm run build`) when the phase touches the UI.
5. The phase's acceptance criteria all hold — verified with tests or a quick
   manual smoke test against `php artisan serve`.

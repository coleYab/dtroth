# Phase 6 — Notifications & Teams *(future work — spec only, do not implement)*

> **Status: FUTURE WORK.** This file is a specification for later. Do not
> implement it in the current build cycle. It exists so the work is scoped,
> sequenced, and ready when the user decides to take it on.

## Goal

Two capabilities that turn the portal from a single-developer tool into a
team product:

1. **Email notifications** — proactive alerts when things need attention
   (failing webhooks, expiring keys, usage thresholds, failed payments).
2. **Teams & roles** — invite collaborators onto an account with scoped
   permissions (owner / developer / viewer), share apps and keys safely.

## Current state (context)

- `app/Models/User.php` — single-user accounts; `apps` are owned via
  `user_id`. No organization concept exists anywhere.
- `app/Models/Webhook.php::isFailing(int $windowHours = 24)` — helper that
  already detects failing webhooks (failed deliveries in a window).
- `app/Models/ApiKey.php` — has `expires_at`; nothing warns about it today.
- `app/Console/Commands/RollupUsage.php` — daily usage rollup into
  `usage_daily_rollups` (per app: `calls_total`, `verifications_total`,
  `failed_calls`, `success_rate`).
- `app/Models/Subscription.php` / `app/Models/Invoice.php` —
  `subscriptions` (plan, status, trial_ends_at, renews_at, tx_ref, channel)
  and `invoices` (status, amount, paid_at); billing via Chapa.
- `config/mail.php` — standard Laravel mail; check the mail driver before
  assuming SES/SMTP availability on shared hosting.
- `resources/js/pages/settings/*` — settings pages exist (profile, security,
  appearance); notifications settings would slot in alongside.
- `routes/settings.php` — settings routes pattern.

---

## Part A — Email notifications

### 1. Notification types & triggers

| Notification | Trigger | Channel |
| ------------ | ------- | ------- |
| `WebhookFailing` | `Webhook::isFailing()` true for a webhook the account owns | email to owner (and team members with `developer`+ role) |
| `ApiKeyExpiring` | key `expires_at` within 7 days (daily check) | email |
| `UsageThresholdReached` | app's daily rollup crosses 80% and 100% of the plan's monthly quota (config-driven thresholds) | email |
| `InvoiceFailed` | invoice `status = failed` (or Chapa webhook reports failure) | email |

### 2. Deliverables (when implemented)

- `app/Notifications/` classes (Laravel mail notifications) with
  `shouldSend()` guards (e.g. skip if the same notification was sent in the
  last 24h — store `notifications` via the `Notifiable` trait; users table
  needs `notifications` tables? No — Laravel's `notifications` table
  migration + `DatabaseNotification` handles it; add the standard
  `create_notifications_table` migration).
- Scheduled commands:
  - `notify:webhook-failures` — daily (morning) scan of webhooks failing in
    the last 24h.
  - `notify:key-expiry` — daily scan of keys expiring within 7 days
    (and none already notified).
  - `notify:usage-thresholds` — hourly (after `usage:rollup`), evaluates
    rollups against plan quotas.
  - Invoice failures handled inline in the Chapa webhook handler
    (`app/Http/Controllers/BillingController.php::chapaWebhook`).
- Notification preference toggles: `app/Models/User` gains a
  `notification_preferences` JSON column (or a dedicated
  `user_notification_preferences` table); `resources/js/pages/settings/
  notifications.tsx` page with switches per notification type (all
  on-by-default).
- Plain-text + HTML mail views following the existing app styling (check
  how `SupportTicketReceived` mail is built — reuse its layout approach).
- Tests: each trigger fires the right notification once per window, honors
  preferences, and never sends to revoked/deleted users.

### 3. Sequencing notes

- Depends on `usage_daily_rollups` (exists), `Webhook::isFailing()` (exists),
  invoice lifecycle (exists). No hard dependency on Part B, but team-aware
  sending is cleaner after teams land — sequence notifications **after**
  teams if both are planned.

---

## Part B — Teams & roles

### 1. Data model

- `teams` table: `id`, `owner_user_id` (FK), `name`, `slug`, timestamps.
  Or reuse `users.team_id`? — Prefer a `teams` table with membership (below);
  a team can own many apps, and apps can be shared with multiple teams
  later. Keep it simple: one team per account initially (auto-created), not
  multi-team support yet.
- `team_members` pivot: `team_id`, `user_id`, `role` enum
  (`owner` | `developer` | `viewer`), `invited_by`, `accepted_at`,
  timestamps. Owner = the account creator (never demotable); developer =
  full CRUD on apps/keys/webhooks; viewer = read-only.
- Migration: add `team_id` to `apps` (FK, nullable for migration ease, then
  backfill + make required).

### 2. Invites

- `invitations` table (email, team_id, role, token, expires_at, accepted_at)
  — or reuse Laravel's `Invite` pattern; keep self-built and simple.
- Flows:
  - Owner invites: `POST /teams/{team}/invitations` (email + role).
    Email contains an accept link (`/invitations/{token}`). Unauthenticated
    visitors land on register → auto-accept after account creation;
    authenticated users accept immediately.
  - `resources/js/pages/teams/*` UI: team page (members list, roles,
    invites, pending invite resend/cancel), invite dialog, role change
    select (owner-only), remove-member confirm.
- Rules: owner can't be removed/demoted; developers can't invite (only
  owner invites, initially); viewers can't see secret previews or create
  keys/webhooks.

### 3. Authorization

- Policies: `AppPolicy` gains team-aware logic — all app-scoped controllers
  (`AppController`, `ApiKeyController`, `WebhookController`,
  `ConsentController`, etc.) must authorize against team membership + role
  instead of `user_id` ownership. `App::scopeForUser()` becomes
  `scopeForMember()` (via `team_members`).
- Central helper: `app/Services/TeamService.php` —
  `isMember(User $user, App $app): bool`, `roleFor(User $user, App $app): ?Role`,
  `authorize(User $user, App $app, string $permission): void` — or rely on
  policies; pick one approach and be consistent.
- Seed: `dev@example.com` becomes owner of the demo team; seeder stays
  idempotent.
- Tests: role matrix table test (owner/developer/viewer × action →
  allowed/denied), invite accept flow (new user + existing user),
  revoke-on-remove (removed member loses access immediately), audit-log
  entries for member changes.

### 4. UI

- Settings/nav: replace "account" nav footer with the team name + member
  count (read `nav-user.tsx`); teams page at `/teams`.
- App pages unchanged for owners; developers see an "owner-only" notice on
  restricted controls; viewers get read-only pages (hide or disable
  action buttons based on a `permissions` prop passed from the server).

---

## Definition of done (when this phase is executed)

1. All Part A notifications fire once per window, respect preferences, and
   are covered by tests.
2. Team CRUD, invites, role enforcement, and removal all work with the
   full role matrix tested.
3. `php artisan test --compact` green; `vendor/bin/pint --dirty` clean.
4. Docs updated: support/help docs mention team features; settings help
   text updated.

## Notes for the implementing agent

- Do **not** build multi-team membership (one team per user) in the first
  pass — document the extension point instead.
- Mail on shared hosting: verify `MAIL_MAILER` works before assuming
  anything; use the queue for notification mail (`SendWebhookDelivery` job
  pattern already exists) or send sync if no queue worker is running —
  check `install.sh`/README cron guidance: the queue worker runs
  `--stop-when-empty`, so queued mail will be delivered by cron.
- Keep the role enum as `app/Enums/TeamRole.php` (TitleCase cases per
  project convention).

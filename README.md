# Troth Verify

Consent-first age and identity verification, powered by the Ethiopian Fayda
digital ID. A single API request confirms whether a user is above any age
threshold — in milliseconds, without ever storing their date of birth.

Built with Laravel 13, Inertia.js v3 + React 19, Tailwind CSS v4, and an SQLite
database.

## Features

- **Instant age verification** — one `POST /v1/verify/age` returns a boolean
  verdict in ~140ms against the Fayda digital ID
- **Consent-first privacy** — only the answer is stored, never the date of
  birth; every check is tied to explicit user consent
- **Developer experience** — sandbox environments with simulated Fayda
  identities, API keys with rotation and instant revocation, and full request
  logs
- **Realtime webhooks** — notified the moment a verification completes, with
  automatic retries and delivery logs
- **Usage analytics** — live dashboards for volume, latency, and success rates
  across every environment
- **Interactive sandbox** — send real verification requests against mock test
  identities right from the browser

## Tech stack

| Layer      | Technology                                   |
| ---------- | -------------------------------------------- |
| Backend    | PHP 8.3+, Laravel 13, Fortify                |
| Frontend   | Inertia.js v3, React 19, Tailwind CSS v4     |
| Database   | SQLite (no external database server needed)  |
| Auth       | Laravel Fortify (email + password)           |
| PDFs       | barryvdh/laravel-dompdf                      |

## Local development

Requirements: PHP 8.3+, Composer, Node.js 20+ and npm.

```bash
# install dependencies, create .env, generate key, migrate
composer setup

# start the dev servers (Laravel + Vite) in one command
composer run dev
```

The app is served at `http://localhost:8000`.

To set up the database manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

The seeder is idempotent and creates:

- A demo user (`dev@example.com` / `password`)
- Age-query presets and webhook event types
- Sandbox test identities (`fayda-test-a` … `fayda-test-d`)

## Shared hosting installation

Upload the project to your host (e.g. into `~/domains/yourdomain.com/public_html`
or a subdirectory) and run:

```bash
cd /path/to/project
APP_URL=https://yourdomain.com bash install.sh
```

The script performs every step automatically:

1. Creates `.env` from `.env.example` and sets production values
2. Generates the application key
3. Installs Composer dependencies (production-only by default)
4. Creates the SQLite database, runs migrations and seeds
5. Generates Wayfinder route files
6. Installs npm dependencies and builds the frontend assets
7. Links storage and caches config/routes/views
8. Sets storage permissions and removes stale Vite dev files

Optional flags:

| Variable          | Effect                                        |
| ----------------- | --------------------------------------------- |
| `APP_URL`         | Public URL of the app (required once)         |
| `INSTALL_WITH_DEV=1` | Also install Composer dev dependencies     |
| `SKIP_FRONTEND=1` | Skip npm build (upload a built `public/build`) |
| `SKIP_CACHING=1`  | Skip config/route/view caching                |

After installation, add these cron jobs (adjust the path):

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

The app's scheduled tasks are `usage:rollup` (daily at 00:30) and
`status:check` (every 5 minutes).

> **Note:** PHP must be at least 8.3. If `node`/`npm` are unavailable on your
> host, build the assets locally (`npm install && npm run build`) and upload
> the `public/build` directory, then re-run with `SKIP_FRONTEND=1`.

## Testing

```bash
php artisan test
```

## License

MIT — see the [LICENSE](LICENSE) file for details.

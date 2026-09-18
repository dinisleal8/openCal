# openCal

A self-hosted, mobile-first calorie tracking app. Log meals manually, by **AI photo recognition**, or by barcode; track water, weight and exercise; and sync activity from a wearable connected to Google Health.

> **Status / disclaimer**
> This project was **vibe-coded** as an experiment to try out AI-assisted development. It is functional — tests, lint, static analysis, type-check and production build all pass — but it is a work in progress. The maintainer is now taking over the rest of its development. Expect rough edges, breaking changes, and missing polish. Forks, issues and pull requests are welcome.

## Features

- **Goals & targets** — onboarding wizard calculates BMR/TDEE (Mifflin-St Jeor) and daily calorie/macro/water targets from your body stats, activity level and goal.
- **Today dashboard** — calorie ring, macro bars, meals by type, water quick-add, weight and exercise, plus band activity stats.
- **AI photo logging** — snap a meal and let Google Gemini or OpenCode Zen/Go estimate its nutrition.
- **Barcode lookup** — resolve products via Open Food Facts (backend ready; UI in progress).
- **History** — 7-day and 30-day views (paged week by week) with weight trend chart and averages.
- **Google Health sync** — connect a Fitbit / Pixel Watch / band and pull steps, active calories and weight.
- **Multi-language** — English and Portuguese (extensible dictionary-based i18n).
- **Owner-only accounts** — the first seeded account owns the instance and is the only one allowed to create more users; public registration is disabled.
- **Mobile-first UI** — bottom tab navigation, single centered column, light/dark mode.

## Tech stack

- **Backend:** Laravel 13 (PHP 8.3+, PHP 8.4 recommended)
- **Frontend:** Inertia 3 + React 19 + TypeScript, Tailwind CSS 4, Vite
- **Auth:** Laravel Fortify (login, 2FA, passkeys, password reset) with registration disabled
- **Routing helpers:** Laravel Wayfinder (generated TypeScript route functions)
- **Database:** SQLite by default (MySQL/PostgreSQL also supported)
- **Integrations:** AI photo recognition (Google Gemini or any OpenAI-compatible API), Google Health API (wearables), Open Food Facts (barcodes)

## Requirements

- PHP **8.3+** with the usual Laravel extensions (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`)
- [Composer](https://getcomposer.org/) 2
- [Node.js](https://nodejs.org/) 20+ and npm
- A database — SQLite ships out of the box and needs no setup

Optional (only for the related features):

- An **AI provider key** for AI photo recognition: a Google Gemini API key, or an OpenCode Zen/Go key (or any OpenAI-compatible endpoint)
- **Google Health OAuth credentials** for wearable sync

## Installation

```bash
# 1. Clone the repository
git clone <your-repo-url> openCal
cd openCal

# 2. Install PHP and JS dependencies
composer install
npm install

# 3. Create your environment file
cp .env.example .env          # macOS/Linux
# copy .env.example .env      # Windows (cmd/PowerShell)

# 4. Generate the application key
php artisan key:generate

# 5. Create the SQLite database (skip if using MySQL/PostgreSQL)
touch database/database.sqlite          # macOS/Linux
# New-Item database/database.sqlite     # Windows PowerShell

# 6. Run migrations and seed the owner account
php artisan migrate --seed

# 7. Build the frontend
npm run build
```

> **Shortcut:** steps 2–7 (except seeding) are wrapped in the `composer setup` script. After it completes, run `php artisan db:seed` to create the owner account.

### Windows note

Laravel Herd is the easiest way to serve the app on Windows and macOS. With Herd, the project is automatically available at `https://opencal.test` — no `php artisan serve` needed.

## Running the app

### Option A — Laravel Herd (recommended)

If the project folder is named `openCal`, Herd serves it automatically at:

```
https://opencal.test
```

### Option B — Artisan + Vite (one command)

```bash
composer run dev
```

This starts the PHP dev server, the queue listener and Vite (HMR) together via `concurrently`.

### Option C — Manually, in separate terminals

```bash
php artisan serve     # http://localhost:8000
npm run dev           # Vite dev server with HMR
php artisan queue:listen
```

For production, run `npm run build` so the Vite manifest is generated; otherwise you will get a `ViteException: Unable to locate file in Vite manifest`.

### First login

The owner account is created by the seeder from your `.env` values:

| Variable         | Default               |
| ---------------- | --------------------- |
| `OWNER_NAME`     | `Owner`               |
| `OWNER_EMAIL`    | `owner@opencal.local` |
| `OWNER_PASSWORD` | `password`            |

> Change `OWNER_PASSWORD` in `.env` **before** running `php artisan migrate --seed`. The owner is the only account allowed to create additional users.

## Configuration

All configuration lives in `.env`. The most relevant variables:

| Variable                                        | Purpose                                                                                             |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `APP_URL`                                       | Base URL used to build OAuth redirect URIs                                                          |
| `APP_LOCALE`                                    | Default locale (`en` or `pt`)                                                                       |
| `DB_CONNECTION`                                 | `sqlite` (default), `mysql`, `pgsql`                                                                |
| `OWNER_NAME` / `OWNER_EMAIL` / `OWNER_PASSWORD` | First (owner) account created by the seeder                                                         |
| `OPENCAL_PHOTO_DISK`                            | Filesystem disk for meal photos (default `local`)                                                   |
| `OPENCAL_AI_PROVIDER`                           | AI photo provider: `gemini` (default) or `opencode`                                                 |
| `GEMINI_API_KEY`                                | Google Gemini key (when `OPENCAL_AI_PROVIDER=gemini`)                                               |
| `GEMINI_MODEL`                                  | Gemini model (default `gemini-2.5-flash`)                                                           |
| `OPENCODE_API_KEY`                              | OpenCode Zen/Go key (when `OPENCAL_AI_PROVIDER=opencode`)                                           |
| `OPENCODE_BASE_URL`                             | OpenAI-compatible base URL (Go: `https://opencode.ai/zen/go/v1`, Zen: `https://opencode.ai/zen/v1`) |
| `OPENCODE_MODEL`                                | Vision model id (default `deepseek-v4-flash-vision-exp`)                                            |
| `GOOGLE_HEALTH_CLIENT_ID`                       | Google OAuth client ID for wearable sync                                                            |
| `GOOGLE_HEALTH_CLIENT_SECRET`                   | Google OAuth client secret                                                                          |
| `GOOGLE_HEALTH_REDIRECT`                        | OAuth callback — must match your Google Cloud console entry                                         |
| `OPENFOODFACTS_USER_AGENT`                      | User-Agent sent to the Open Food Facts API                                                          |

### AI photo recognition

Choose one provider via `OPENCAL_AI_PROVIDER`. The model must support **vision** (image input).

**Option A — Google Gemini** (`OPENCAL_AI_PROVIDER=gemini`, default)

1. Create an API key at [Google AI Studio](https://aistudio.google.com/app/apikey).
2. Set `GEMINI_API_KEY` in `.env` (optionally `GEMINI_MODEL`).
3. Restart the app.

**Option B — OpenCode Zen / Go** (`OPENCAL_AI_PROVIDER=opencode`)

OpenCode Zen and Go share the same API key and expose an OpenAI-compatible endpoint, but they use **different base URLs**:

- **Go** (fixed subscription): `https://opencode.ai/zen/go/v1`
- **Zen** (pay-as-you-go): `https://opencode.ai/zen/v1`

Using the wrong one will fail with a `CreditsError: Insufficient balance`.

1. Sign in at [opencode.ai/zen](https://opencode.ai/zen), copy your API key.
2. Set in `.env` (Go subscription shown):

    ```dotenv
    OPENCAL_AI_PROVIDER=opencode
    OPENCODE_API_KEY=your-key
    OPENCODE_BASE_URL=https://opencode.ai/zen/go/v1
    OPENCODE_MODEL=deepseek-v4-flash-vision-exp
    ```

    `deepseek-v4-flash-vision-exp` is a cheap, high-throughput vision model included with Go. Any other OpenAI-compatible vision model works too — point `OPENCODE_BASE_URL`/`OPENCODE_MODEL` at it (OpenAI, OpenRouter, a local Ollama server, etc.).

3. Restart the app. The photo tile on the food form will return AI estimates.

Both providers share the same response contract and the same tests, so switching only requires the `.env` changes above.

### Wearable sync (Google Health)

1. Create an OAuth 2.0 Client in the [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
2. Enable the Google Fitness API.
3. Add `${APP_URL}/settings/google-health/callback` as an authorized redirect URI.
4. Set `GOOGLE_HEALTH_CLIENT_ID` and `GOOGLE_HEALTH_CLIENT_SECRET`.
5. While the OAuth consent screen is in **Testing** mode, add your Google account as a test user.

> Note: in Testing mode Google expires refresh tokens after **7 days**, so connected accounts periodically need to reconnect.

## Quality checks

```bash
php artisan test          # Pest test suite
composer lint:check       # Pint (PHP code style)
composer types:check      # PHPStan
npm run types:check       # TypeScript
npm run check             # Lint + format check (JS/TS/CSS)
npm run check:fix         # Auto-fix lint/format
composer test             # Lint + PHPStan + tests
```

## Project structure

```
app/
  Http/Controllers/        Dashboard, history, goals, tracking CRUD, settings, integrations
  Http/Requests/           Form request validation
  Models/                  Goal, FoodEntry, WaterLog, WeighIn, ExerciseLog, ActivityDay, Product, Photo, GoogleHealthAccount
  Policies/                Per-model authorization
  Services/                DailyTargetsService, DailySummaryService, GoogleHealthService, FoodPhotoAnalyzer (Gemini / OpenAI-compatible drivers)
resources/
  js/pages/                Inertia pages (dashboard, history, onboarding, goal edit, settings)
  js/components/           UI + feature components (forms, charts, navigation)
  js/lang/                 en / pt translation dictionaries
  js/lib/i18n.tsx          Lightweight i18n provider
routes/                    web, tracking, settings, api
tests/                     Pest feature and unit tests
```

## Roadmap

- Barcode scanning UI on the food form (backend + Open Food Facts lookup already implemented)
- Finish Portuguese translations for the 2FA / passkey settings components
- Progressive Web App (offline logging, install prompt)
- Additional charts and weekly reports

## Contributing

This project is open to use, fork and learn from. Issues and pull requests are welcome. If you build on it, a link back is appreciated but not required.

## License

Released under the [MIT License](LICENSE).

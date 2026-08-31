# StockLane on Vercel

StockLane is a **Laravel + Inertia** app. Vite assets land in `public/build`, not `dist`. The `vercel.json` files fix the common error:

> No Output Directory named "dist" found after the Build completed.

## Recommended Vercel project settings

| Setting | Value |
|---------|--------|
| **Root Directory** | `backend` |
| **Framework Preset** | Other (or auto; `framework: null` in vercel.json overrides) |
| **Build Command** | *(from `backend/vercel.json`)* |
| **Output Directory** | `public` |
| **Install Command** | *(from `backend/vercel.json`)* |

If Root Directory is `backend`, Vercel uses `backend/vercel.json`.

If Root Directory is the repo root (`.`), Vercel uses the root `vercel.json` (paths prefixed with `backend/`).

## Required environment variables (Vercel dashboard)

Set these in **Project → Settings → Environment Variables**:

| Variable | Example / notes |
|----------|-----------------|
| `APP_KEY` | `base64:...` from `php artisan key:generate --show` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your `*.vercel.app` URL |
| `DB_CONNECTION` | `pgsql` (Neon / Supabase / Render Postgres URL) |
| `DATABASE_URL` | Full Postgres URL if using Laravel's url driver |
| `SESSION_DRIVER` | `cookie` |
| `CACHE_STORE` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `LOG_CHANNEL` | `stderr` |

Optional portfolio placeholders: `PAYMONGO_WEBHOOK_SECRET`, `BUSYBEE_API_KEY`, `LOW_STOCK_SMS_TO`.

## Limitations (honesty)

- Vercel runs Laravel as **serverless PHP** — no `queue:work` worker, no Redis on the free tier.
- **Render + Docker** (`render.yaml`) remains the full-stack reference deploy (Postgres + migrations + seed).
- Use Vercel for a **demo URL**; use Render for queues and persistent file storage patterns.

## Local parity check

```bash
cd backend
composer install --no-dev --optimize-autoloader
npm ci && npm run build
# Assets must exist under public/build (not dist)
ls public/build
```

## One-click Render (permanent production)

https://render.com/deploy?repo=https://github.com/jcuady/StockLane

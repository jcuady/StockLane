# StockLane on Vercel

StockLane is a **Laravel + Inertia** app. Vite assets land in `public/build`, not `dist`. The `vercel.json` files fix the common error:

> No Output Directory named "dist" found after the Build completed.

## Recommended Vercel project settings

| Setting | Value |
|---------|--------|
| **Root Directory** | `backend` |
| **Framework Preset** | Other (`framework: null` in vercel.json) |
| **Node.js Version** | 20.x (matches `package.json` engines) |
| **Install Command** | `npm ci` only — **do not** call `composer` here |
| **Build Command** | `npm run build` |
| **Output Directory** | `public` |

**Why no `composer` in Install Command:** Vercel's install step runs in a Node-only shell. The `vercel-php` runtime installs PHP dependencies automatically when it packages `api/index.php`. Calling `composer install` in `installCommand` fails with `composer: command not found` (exit 127).

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
npm ci && npm run build
# Assets must exist under public/build (not dist)
ls public/build
# Composer is for local/Render/Docker only — vercel-php runs it on deploy
composer install --no-dev --optimize-autoloader
```

## One-click Render (permanent production)

https://render.com/deploy?repo=https://github.com/jcuady/StockLane

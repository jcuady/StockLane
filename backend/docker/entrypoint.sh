#!/bin/sh
set -eu

cd /var/www/html

# Laravel requires APP_KEY like base64:... — Render generateValue is often plain random.
case "${APP_KEY:-}" in
  base64:*)
    ;;
  *)
    export APP_KEY="$(php artisan key:generate --force --show)"
    echo "Normalized APP_KEY to Laravel base64 format for this boot"
    ;;
esac

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force || echo "migrate skipped/failed; continuing"
fi

ROLE="${1:-web}"
case "$ROLE" in
  web)
    PORT="${PORT:-8000}"
    exec php artisan serve --host=0.0.0.0 --port="$PORT"
    ;;
  worker)
    exec php artisan queue:work "${QUEUE_CONNECTION:-database}" --sleep=1 --tries=3 --max-time=3600
    ;;
  *)
    exec "$@"
    ;;
esac

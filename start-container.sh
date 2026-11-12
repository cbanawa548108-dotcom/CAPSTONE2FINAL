#!/bin/bash

set -e

if [ ! -x /app/.venv/bin/python ]; then
  python -m venv /app/.venv
fi

/app/.venv/bin/python -m pip install --disable-pip-version-check --no-cache-dir -r /app/requirements.txt
export PATH="/app/.venv/bin:$PATH"

if [ "$RAILPACK_SKIP_MIGRATIONS" != "true" ]; then
  echo "Running migrations ..."
  php artisan migrate --force
fi

php artisan storage:link
php artisan optimize:clear
php artisan optimize

echo "Starting Laravel server ..."
docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1

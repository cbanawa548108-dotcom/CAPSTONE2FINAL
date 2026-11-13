#!/bin/bash

set -e

cd /app

if [ ! -x /app/.venv/bin/python ]; then
  python -m venv /app/.venv
fi

/app/.venv/bin/python -m pip install --disable-pip-version-check --no-cache-dir -r /app/requirements.txt
export PATH="/app/.venv/bin:$PATH"

echo "Running Laravel scheduler at $(date -u +%Y-%m-%dT%H:%M:%SZ)"
php artisan schedule:run --verbose

#!/bin/bash

set -e

cd /app

if [ ! -x /app/.venv/bin/python ]; then
  echo "Forecast Python environment is missing from the built image." >&2
  exit 1
fi

/app/.venv/bin/python -c "import pandas, numpy, statsmodels, xgboost, sklearn, mysql.connector"

echo "Running Laravel scheduler at $(date -u +%Y-%m-%dT%H:%M:%SZ)"
php artisan schedule:run --verbose

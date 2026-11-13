#!/bin/bash

set -e

cd /app

/app/.scheduler-venv/bin/python -c "import pandas, numpy, statsmodels, xgboost, sklearn, mysql.connector"
export ML_PYTHON=/app/.scheduler-venv/bin/python

echo "Running Laravel scheduler at $(date -u +%Y-%m-%dT%H:%M:%SZ)"
php artisan schedule:run --verbose

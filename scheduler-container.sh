#!/bin/bash

set -e

cd /app

python -c "import pandas, numpy, statsmodels, xgboost, sklearn, mysql.connector"

echo "Running Laravel scheduler at $(date -u +%Y-%m-%dT%H:%M:%SZ)"
php artisan schedule:run --verbose

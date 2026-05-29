#!/bin/bash
# Phase 1b - Database Unification: run the user migration script.
# Usage:  bash scripts/run_migration.sh

set -euo pipefail

cd "$(dirname "$0")/.."

# Source .env so environment variables are available
if [ -f .env ]; then
    set -a
    source .env
    set +a
fi

# Use the Flask API virtual environment where python-dotenv is installed
VENV_PYTHON="/var/www/caiziyou/api/venv/bin/python3"
if [ -x "$VENV_PYTHON" ]; then
    PYTHON="$VENV_PYTHON"
else
    PYTHON="python3"
fi

echo "[$(date)] Starting user migration (using $PYTHON) ..."
$PYTHON scripts/migrate_users.py
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "[$(date)] Migration completed successfully."
else
    echo "[$(date)] Migration completed with errors (exit code $EXIT_CODE)." >&2
fi

exit $EXIT_CODE

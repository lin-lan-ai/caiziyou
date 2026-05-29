#!/bin/bash
# Phase 3: Run FULLTEXT index migration.
# Usage:  bash scripts/run_fulltext_migration.sh
#
# Adds FULLTEXT indexes for search to the community database.
# Safe to re-run: IF NOT EXISTS is implicit in ALTER TABLE ADD INDEX
# (MySQL will warn but not error if the index already exists).

set -euo pipefail

cd "$(dirname "$0")/.."

# Source .env so DB credentials are available
if [ -f .env ]; then
    set -a
    source .env
    set +a
fi

DB_HOST="${COMMUNITY_DB_HOST:-localhost}"
DB_USER="${COMMUNITY_DB_USER:-caiziyou_community}"
DB_PASS="${COMMUNITY_DB_PASS:-Community@2026}"
DB_NAME="${COMMUNITY_DB_NAME:-caiziyou_community_db}"

echo "[$(date)] Adding FULLTEXT indexes to ${DB_NAME} ..."

mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < scripts/migration_fulltext_indexes.sql

echo "[$(date)] FULLTEXT indexes added successfully."

#!/usr/bin/env bash
# Servidor para las pruebas de navegador: base SQLite propia, nunca la base real.
set -euo pipefail
cd "$(dirname "$0")/../.."

BASE="$(pwd)/database/e2e.sqlite"
export APP_ENV=local APP_DEBUG=false DB_CONNECTION=sqlite DB_DATABASE="$BASE" DB_URL=
export CACHE_STORE=file SESSION_DRIVER=file QUEUE_CONNECTION=sync MAIL_MAILER=array
export TWO_FACTOR_ROLES= BACKUP_PASSWORD= ADMIN_PASSWORD="${ADMIN_PASSWORD:-Admin-E2e-2026}"
export APP_URL="http://127.0.0.1:${E2E_PORT:-8800}"

rm -f "$BASE" && touch "$BASE"
php artisan migrate:fresh --seed --force --no-interaction > /dev/null
exec php artisan serve --host=127.0.0.1 --port="${E2E_PORT:-8800}"

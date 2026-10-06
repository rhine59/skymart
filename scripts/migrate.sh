#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
COMPOSE_FILES="${SKYMART_COMPOSE_FILES:--f docker-compose.yml -f docker-compose.dev.yml}"
# shellcheck disable=SC2086
compose(){ docker compose $COMPOSE_FILES "$@"; }
echo "==> Ensuring migration ledger"
compose exec -T db mariadb -uroot -p"${SKYMART_DB_ROOT_PASSWORD:-skymart-root-dev-only}" "${SKYMART_DB_NAME:-skymart}" -e "CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);"
for file in migrations/*.sql; do
 name="$(basename "$file")"
 applied="$(compose exec -T db mariadb -N -uroot -p"${SKYMART_DB_ROOT_PASSWORD:-skymart-root-dev-only}" "${SKYMART_DB_NAME:-skymart}" -e "SELECT COUNT(*) FROM schema_migrations WHERE filename='$name';")"
 if [ "$applied" = "0" ]; then
  echo "==> Applying $name"
  compose exec -T db mariadb -uroot -p"${SKYMART_DB_ROOT_PASSWORD:-skymart-root-dev-only}" "${SKYMART_DB_NAME:-skymart}" < "$file"
  compose exec -T db mariadb -uroot -p"${SKYMART_DB_ROOT_PASSWORD:-skymart-root-dev-only}" "${SKYMART_DB_NAME:-skymart}" -e "INSERT INTO schema_migrations(filename) VALUES('$name');"
 fi
done
echo "PASS: migrations current"

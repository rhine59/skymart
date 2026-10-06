#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.dev.yml"
echo "==> Rebuilding SkyMart development containers"
$COMPOSE down --remove-orphans
$COMPOSE build --pull --no-cache
$COMPOSE up -d
echo "==> Waiting for health"
i=0
until curl -fsS "http://127.0.0.1:${SKYMART_PORT:-8080}/health.php" >/dev/null; do
 i=$((i+1)); [ "$i" -ge 60 ] && { $COMPOSE ps; $COMPOSE logs --tail=100; exit 1; }; sleep 2
done
$COMPOSE ps
echo "PASS: SkyMart rebuilt and healthy"

#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
echo "==> Rebuilding SkyMart containers"
docker compose down --remove-orphans
docker compose build --pull --no-cache
docker compose up -d
echo "==> Waiting for health"
i=0
until curl -fsS "http://127.0.0.1:${SKYMART_PORT:-8080}/health.php" >/dev/null; do
  i=$((i+1)); [ "$i" -ge 60 ] && { docker compose ps; docker compose logs --tail=100; exit 1; }
  sleep 2
done
docker compose ps
echo "PASS: SkyMart rebuilt and healthy"

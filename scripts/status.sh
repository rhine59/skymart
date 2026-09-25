#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
docker compose ps
printf '\nHealth endpoint:\n'
curl -fsS "http://127.0.0.1:${SKYMART_PORT:-8080}/health.php" || true
printf '\n'

#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.dev.yml"

command -v git >/dev/null 2>&1 || { echo "FAIL: git is required to prove the deployment revision"; exit 1; }
[ -z "$(git status --porcelain)" ] || { echo "FAIL: working tree is dirty; commit or discard changes before deployment"; git status --short; exit 1; }
export SKYMART_BUILD_SHA="$(git rev-parse HEAD)"

echo "==> Rebuilding SkyMart development containers from $SKYMART_BUILD_SHA"
$COMPOSE down --remove-orphans
$COMPOSE build --pull --no-cache
$COMPOSE up -d

echo "==> Waiting for health"
i=0
until curl -fsS "http://127.0.0.1:${SKYMART_PORT:-8080}/health.php" >/dev/null; do
 i=$((i+1)); [ "$i" -ge 60 ] && { $COMPOSE ps; $COMPOSE logs --tail=100; exit 1; }; sleep 2
done

echo "==> Verifying running revision"
RUNNING_SHA="$($COMPOSE exec -T web printenv SKYMART_BUILD_SHA)"
[ "$RUNNING_SHA" = "$SKYMART_BUILD_SHA" ] || { echo "FAIL: deployment drift: expected $SKYMART_BUILD_SHA but container reports $RUNNING_SHA"; exit 1; }
IMAGE_SHA="$(docker inspect "$($COMPOSE ps -q web)" --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}')"
[ "$IMAGE_SHA" = "$SKYMART_BUILD_SHA" ] || { echo "FAIL: image revision label mismatch: expected $SKYMART_BUILD_SHA but image reports $IMAGE_SHA"; exit 1; }

$COMPOSE ps
echo "PASS: SkyMart rebuilt, healthy and running Git revision $RUNNING_SHA"

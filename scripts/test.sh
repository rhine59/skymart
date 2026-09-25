#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
export SKYMART_TEST_PORT="${SKYMART_TEST_PORT:-18080}"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.test.yml"

cleanup() { $COMPOSE down -v --remove-orphans >/dev/null 2>&1 || true; }
trap cleanup EXIT INT TERM

echo "==> Building isolated test stack"
cleanup
$COMPOSE build --pull
$COMPOSE up -d

echo "==> Waiting for application"
i=0
until curl -fsS "http://127.0.0.1:$SKYMART_TEST_PORT/health.php" >/dev/null; do
  i=$((i+1))
  if [ "$i" -ge 60 ]; then
    $COMPOSE ps
    $COMPOSE logs --tail=150
    exit 1
  fi
  sleep 2
done

echo "==> PHP syntax checks"
$COMPOSE exec -T web sh -c 'find /var/www/html -name "*.php" -type f -print0 | xargs -0 -n1 php -l'

echo "==> Database schema checks"
$COMPOSE exec -T db mariadb -uskymart -pskymart-dev-only skymart -e   "SELECT COUNT(*) AS users_table FROM information_schema.tables WHERE table_schema='skymart' AND table_name='users'; SELECT COUNT(*) AS categories FROM categories; SELECT COUNT(*) AS listings_table FROM information_schema.tables WHERE table_schema='skymart' AND table_name='listings';"

echo "==> HTTP/CSRF/auth smoke test"
BASE="http://127.0.0.1:$SKYMART_TEST_PORT"
COOKIE="$(mktemp)"
PAGE="$(mktemp)"
curl -fsS -c "$COOKIE" "$BASE/register.php" > "$PAGE"
TOKEN="$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$PAGE" | head -1)"
[ -n "$TOKEN" ] || { echo "FAIL: registration CSRF token missing"; exit 1; }

EMAIL="test@example.invalid"
curl -fsS -b "$COOKIE" -c "$COOKIE" -L   --data-urlencode "csrf_token=$TOKEN"   --data-urlencode "name=SkyMart Test User"   --data-urlencode "email=$EMAIL"   --data-urlencode "phone="   --data-urlencode "password=Test-password-12345"   --data-urlencode "password_confirm=Test-password-12345"   "$BASE/register.php" | grep -q "Secure account foundation is active"

COUNT="$($COMPOSE exec -T db mariadb -N -uskymart -pskymart-dev-only skymart -e "SELECT COUNT(*) FROM users WHERE email='$EMAIL';")"
[ "$COUNT" = "1" ] || { echo "FAIL: registration did not create user"; exit 1; }

PRIVATE_CODE="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/private.php")"
[ "$PRIVATE_CODE" = "302" ] || { echo "FAIL: unauthenticated private page returned $PRIVATE_CODE"; exit 1; }

rm -f "$COOKIE" "$PAGE"
echo "PASS: build, PHP syntax, migrations, registration and access-control smoke tests"

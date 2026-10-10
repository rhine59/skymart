#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
export SKYMART_TEST_PORT="${SKYMART_TEST_PORT:-18080}"
export SKYMART_BUILD_SHA="$(git rev-parse HEAD 2>/dev/null || echo test-unknown)"
COMPOSE="docker compose -p skymart-test -f docker-compose.yml -f docker-compose.test.yml"
cleanup(){ $COMPOSE down -v --remove-orphans >/dev/null 2>&1 || true; }
trap cleanup EXIT INT TERM
echo "==> Building isolated test stack";cleanup;$COMPOSE build --pull;$COMPOSE up -d
echo "==> Waiting for application";i=0
until curl -fsS "http://127.0.0.1:$SKYMART_TEST_PORT/health.php" >/dev/null; do
  i=$((i+1))
  if [ "$i" -ge 60 ]; then
    $COMPOSE ps
    $COMPOSE logs --tail=150
    exit 1
  fi
  sleep 2
done
echo "==> Build revision checks"
RUNNING_SHA="$($COMPOSE exec -T web printenv SKYMART_BUILD_SHA)"
[ "$RUNNING_SHA" = "$SKYMART_BUILD_SHA" ] || { echo "FAIL: running revision $RUNNING_SHA != expected $SKYMART_BUILD_SHA"; exit 1; }
IMAGE_SHA="$(docker inspect "$($COMPOSE ps -q web)" --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}')"
[ "$IMAGE_SHA" = "$SKYMART_BUILD_SHA" ] || { echo "FAIL: image revision $IMAGE_SHA != expected $SKYMART_BUILD_SHA"; exit 1; }
echo "PASS: build revision expected=$SKYMART_BUILD_SHA container=$RUNNING_SHA image=$IMAGE_SHA"
echo "==> PHP version and syntax checks"
$COMPOSE exec -T web php -r 'if (PHP_VERSION_ID < 80400) { fwrite(STDERR, "PHP 8.4+ required\n"); exit(1); }'
$COMPOSE exec -T web sh -c 'find /var/www/skymart -name "*.php" -type f -print0 | xargs -0 -n1 php -l'
echo "==> Public-root isolation checks"
for path in /bootstrap.php /database.php /migrations/001_initial.sql /docs/SECURITY.md;do code="$(curl -sS -o /dev/null -w '%{http_code}' "http://127.0.0.1:$SKYMART_TEST_PORT$path")";[ "$code" = "404" ]||{ echo "FAIL: internal path $path returned $code";exit 1;};done
echo "==> Compose exposure check"
PORTS="$($COMPOSE port web 80)"
echo "$PORTS" | grep -q "127.0.0.1:$SKYMART_TEST_PORT" || { echo "FAIL: unexpected test port binding: $PORTS"; exit 1; }
[ "$(echo "$PORTS" | wc -l | tr -d " ")" = "1" ] || { echo "FAIL: multiple test port bindings: $PORTS"; exit 1; }
echo "==> Database schema checks"
$COMPOSE exec -T db mariadb -uskymart -pskymart-dev-only skymart -e "SELECT COUNT(*) AS users_table FROM information_schema.tables WHERE table_schema='skymart' AND table_name='users'; SELECT COUNT(*) AS categories FROM categories; SELECT COUNT(*) AS listings_table FROM information_schema.tables WHERE table_schema='skymart' AND table_name='listings';"
echo "==> API smoke test"
BASE="http://127.0.0.1:$SKYMART_TEST_PORT"
curl -fsS "$BASE/api/v1/health" | grep -q '"api":"v1"'
curl -fsS "$BASE/api/v1/categories" | grep -q '"slug":"aircraft"'
curl -fsS "$BASE/api/v1/listings?per_page=51" | grep -q '"per_page":50'
BAD_PAGE="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/api/v1/listings?page=0")"
[ "$BAD_PAGE" = "422" ] || { echo "FAIL: invalid API pagination returned $BAD_PAGE"; exit 1; }
echo "==> Public browser catalogue smoke test"
curl -fsS "$BASE/browse.php" | grep -q "Latest adverts"
curl -fsS "$BASE/browse.php?q=missing-aviation-item" | grep -q "No adverts found"
curl -fsSL "$BASE/index.php" | grep -q "Latest adverts"
curl -fsS "$BASE/login.php" | grep -q "Login"
curl -fsS "$BASE/browse.php" | grep -q "All categories"
echo "PASS: browser catalogue, empty state and sign-in navigation"
echo "==> HTTP/CSRF/auth smoke test"
COOKIE="$(mktemp)";PAGE="$(mktemp)"
curl -fsS -c "$COOKIE" "$BASE/register.php" > "$PAGE";TOKEN="$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$PAGE"|head -1)";[ -n "$TOKEN" ]||{ echo "FAIL: registration CSRF token missing";exit 1;}
EMAIL="test@example.invalid"
curl -fsS -b "$COOKIE" -c "$COOKIE" -L --data-urlencode "csrf_token=$TOKEN" --data-urlencode "name=SkyMart Test User" --data-urlencode "email=$EMAIL" --data-urlencode "phone=" --data-urlencode "password=Test-password-12345" --data-urlencode "password_confirm=Test-password-12345" "$BASE/register.php"|grep -q "Secure account foundation is active"
COUNT="$($COMPOSE exec -T db mariadb -N -uskymart -pskymart-dev-only skymart -e "SELECT COUNT(*) FROM users WHERE email='$EMAIL';")";[ "$COUNT" = "1" ]||{ echo "FAIL: registration did not create user";exit 1;}
PRIVATE_CODE="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/private.php")";[ "$PRIVATE_CODE" = "302" ]||{ echo "FAIL: unauthenticated private page returned $PRIVATE_CODE";exit 1;}
echo "==> Shared advert visibility across API and browser"
CREATE_RESPONSE="$(curl -fsS -b "$COOKIE" -H 'Content-Type: application/json' -d '{"title":"Cross Client Test Propeller","description":"A test advert to verify shared marketplace visibility.","category":"parts","price_gbp":275,"location":"Skipton"}' "$BASE/api/v1/listings")"
echo "$CREATE_RESPONSE" | grep -q '"title":"Cross Client Test Propeller"' || { echo "FAIL: listing creation failed"; exit 1; }
curl -fsS "$BASE/api/v1/listings?q=Cross%20Client%20Test" | grep -q '"title":"Cross Client Test Propeller"' || { echo "FAIL: API catalogue did not show advert"; exit 1; }
curl -fsS "$BASE/browse.php?q=Cross%20Client%20Test" | grep -q 'Cross Client Test Propeller' || { echo "FAIL: web catalogue did not show advert"; exit 1; }
curl -fsS "$BASE/browse.php?category=aircraft&q=Cross%20Client%20Test" | grep -q 'No adverts found' || { echo "FAIL: category filter did not exclude advert"; exit 1; }
echo "PASS: created advert appears in shared API and browser, category filtering works"
echo "==> Personal marketplace lifecycle"
curl -fsS -b "$COOKIE" -X PUT "$BASE/api/v1/me/favourites/1" | grep -q '"favourited":true'
curl -fsS -b "$COOKIE" "$BASE/api/v1/me/favourites" | grep -q 'Cross Client Test Propeller'
curl -fsS -b "$COOKIE" -X DELETE "$BASE/api/v1/me/favourites/1" | grep -q '"favourited":false'
curl -fsS -b "$COOKIE" -H 'Content-Type: application/json' -d '{"name":"Test","query_text":"Cross Client","category_slug":"parts","enabled":true,"frequency":"daily"}' "$BASE/api/v1/me/saved-searches" | grep -q '"name":"Test"'
curl -fsS -b "$COOKIE" "$BASE/api/v1/me/saved-searches" | grep -q '"name":"Test"'
echo "PASS: authenticated favourites and saved search creation"
rm -f "$COOKIE" "$PAGE";echo "PASS: PHP 8.4, API, public-root isolation, migrations, registration and access-control smoke tests"

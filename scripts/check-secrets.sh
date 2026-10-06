#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
ENV_FILE="${SKYMART_ENV_FILE:-.env}"

echo "==> Secret-file safety checks"
TRACKED="$(git ls-files '.env' '.env.*' ':!.env.example' 2>/dev/null || true)"
[ -z "$TRACKED" ] || { echo "FAIL: secret env file is tracked by Git:"; printf '%s\n' "$TRACKED"; exit 1; }
git check-ignore -q "$ENV_FILE" 2>/dev/null || { [ ! -e "$ENV_FILE" ] || { echo "FAIL: $ENV_FILE is not ignored by Git"; exit 1; }; }

if [ -e "$ENV_FILE" ]; then
  [ -f "$ENV_FILE" ] || { echo "FAIL: $ENV_FILE is not a regular file"; exit 1; }
  MODE="$(stat -c '%a' "$ENV_FILE" 2>/dev/null || stat -f '%Lp' "$ENV_FILE")"
  case "$MODE" in 600|400) ;; *) echo "FAIL: $ENV_FILE permissions are $MODE; require 600 (or read-only 400)"; exit 1;; esac
  echo "PASS: $ENV_FILE is Git-ignored and permissions are $MODE"
else
  echo "INFO: $ENV_FILE is not present; Git tracking/ignore policy is safe"
fi

# Scan tracked text for high-risk private-key material. Never print secret values.
if git grep -I -l -E -- 'BEGIN (RSA |EC |OPENSSH |PGP )?PRIVATE KEY|BEGIN PGP PRIVATE KEY BLOCK' -- . ':!docs/*' ':!scripts/check-secrets.sh' >/tmp/skymart-secret-files.$$ 2>/dev/null; then
  echo "FAIL: tracked private-key material detected in:"
  cat /tmp/skymart-secret-files.$$
  rm -f /tmp/skymart-secret-files.$$
  exit 1
fi
rm -f /tmp/skymart-secret-files.$$
echo "PASS: no tracked private-key blocks detected"

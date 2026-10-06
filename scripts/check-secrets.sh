#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
ENV_FILE="${SKYMART_ENV_FILE:-.env}"
TMP="${TMPDIR:-/tmp}/skymart-secret-files.$$"
trap 'rm -f "$TMP"' EXIT INT TERM

echo "==> Secret-file safety checks"
TRACKED="$(git ls-files '.env' '.env.*' 2>/dev/null | grep -v '^.env.example$' || true)"
[ -z "$TRACKED" ] || { echo "FAIL: secret env file is tracked by Git:"; printf '%s\n' "$TRACKED"; exit 1; }

if [ -e "$ENV_FILE" ]; then
  git check-ignore -q "$ENV_FILE" 2>/dev/null || { echo "FAIL: $ENV_FILE is not ignored by Git"; exit 1; }
  [ -f "$ENV_FILE" ] || { echo "FAIL: $ENV_FILE is not a regular file"; exit 1; }
  MODE="$(stat -c '%a' "$ENV_FILE" 2>/dev/null || stat -f '%Lp' "$ENV_FILE")"
  case "$MODE" in 600|400) ;; *) echo "FAIL: $ENV_FILE permissions are $MODE; require 600 (or read-only 400)"; exit 1;; esac
  echo "PASS: $ENV_FILE is Git-ignored and permissions are $MODE"
else
  echo "INFO: $ENV_FILE is not present; Git tracking/ignore policy is safe"
fi

git grep -I -l -E -- 'BEGIN (RSA |EC |OPENSSH |PGP )?PRIVATE KEY|BEGIN PGP PRIVATE KEY BLOCK' -- . 2>/dev/null \
  | grep -v '^scripts/check-secrets.sh$' \
  | grep -v '^docs/' >"$TMP" || true
if [ -s "$TMP" ]; then
  echo "FAIL: tracked private-key material detected in:"
  cat "$TMP"
  exit 1
fi
echo "PASS: no tracked private-key blocks detected"

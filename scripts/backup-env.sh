#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
ENV_FILE="${SKYMART_ENV_FILE:-.env}"
BACKUP_DIR="${SKYMART_SECRET_BACKUP_DIR:-/volume1/backups/skymart/secrets}"
RECIPIENT="${SKYMART_GPG_RECIPIENT:-}"

[ -f "$ENV_FILE" ] || { echo "FAIL: environment file not found: $ENV_FILE"; exit 1; }
[ -n "$RECIPIENT" ] || { echo "FAIL: set SKYMART_GPG_RECIPIENT to the fingerprint of the OFF-HOST recovery public key"; exit 1; }
command -v gpg >/dev/null 2>&1 || { echo "FAIL: gpg is required"; exit 1; }

MODE="$(stat -c '%a' "$ENV_FILE" 2>/dev/null || stat -f '%Lp' "$ENV_FILE")"
case "$MODE" in 600|400) ;; *) echo "FAIL: $ENV_FILE permissions are $MODE; require 600 or 400"; exit 1;; esac

# Require an exact fingerprint match and an encryption-capable public key.
gpg --batch --with-colons --fingerprint "$RECIPIENT" 2>/dev/null | grep -q "^fpr:::::::::$RECIPIENT:" || { echo "FAIL: exact GPG recipient fingerprint not found"; exit 1; }
gpg --batch --list-options show-usage --list-keys "$RECIPIENT" >/dev/null 2>&1 || { echo "FAIL: GPG public key is unavailable"; exit 1; }

umask 077
mkdir -p "$BACKUP_DIR"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="$BACKUP_DIR/skymart-env-$STAMP.gpg"
TMP="$OUT.tmp"
trap 'rm -f "$TMP"' EXIT INT TERM
gpg --batch --yes --trust-model always --recipient "$RECIPIENT" --output "$TMP" --encrypt "$ENV_FILE"
chmod 600 "$TMP"
mv "$TMP" "$OUT"
trap - EXIT INT TERM

# Verify packet structure without decrypting or exposing contents.
gpg --batch --list-packets "$OUT" >/dev/null 2>&1 || { echo "FAIL: encrypted backup verification failed"; rm -f "$OUT"; exit 1; }
echo "PASS: encrypted SkyMart environment backup created: $OUT"
echo "IMPORTANT: recovery requires the matching private key, which should be stored off this server."

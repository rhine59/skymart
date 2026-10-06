#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
BACKUP="${1:-}"
TARGET="${SKYMART_ENV_FILE:-.env}"
[ -n "$BACKUP" ] && [ -f "$BACKUP" ] || { echo "Usage: $0 /path/to/skymart-env-YYYYMMDDTHHMMSSZ.gpg"; exit 1; }
command -v gpg >/dev/null 2>&1 || { echo "FAIL: gpg is required"; exit 1; }
[ ! -e "$TARGET" ] || { echo "FAIL: $TARGET already exists; refusing to overwrite it"; exit 1; }
umask 077
TMP="$TARGET.restore.$$"
trap 'rm -f "$TMP"' EXIT INT TERM
gpg --batch --output "$TMP" --decrypt "$BACKUP"
chmod 600 "$TMP"
mv "$TMP" "$TARGET"
trap - EXIT INT TERM
echo "PASS: restored $TARGET with permissions 600"
echo "Run scripts/check-secrets.sh before deployment."

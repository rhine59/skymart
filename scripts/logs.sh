#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ "$#" -gt 0 ]; then docker compose logs -f --tail=200 "$@"; else docker compose logs -f --tail=200; fi

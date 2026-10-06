#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
echo "WARNING: this deletes the local SkyMart Docker database volume."
docker compose down -v --remove-orphans
docker compose up -d --build
echo "Database reset; migrations will run on initial MariaDB startup."

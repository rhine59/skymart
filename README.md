# SkyMart

SkyMart is an aviation-focused marketplace for buying and selling aircraft, avionics, instruments, parts and related equipment.

## Technology baseline

- PHP 8.4 + Apache 2.4
- MariaDB 11.4 LTS, InnoDB and utf8mb4
- mysqli prepared statements
- Server-side PHP sessions and password_hash()/password_verify()
- Server-rendered HTML + Bootstrap with selective JavaScript
- Docker Compose for Mac development/test and Synology deployment
- Synology reverse proxy for production HTTPS

Apache exposes only the `public/` directory. Application internals, migrations and configuration remain outside the web document root.

## Phase 2

Phase 2 establishes environment-based configuration, server-side sessions, CSRF protection, prepared SQL, modern password hashing, registration/logout, versioned database migrations and a reproducible Docker environment.

## Configuration

Required application variables: `SKYMART_DB_HOST`, `SKYMART_DB_NAME`, `SKYMART_DB_USER`, `SKYMART_DB_PASSWORD`. Production Compose additionally requires `SKYMART_DB_ROOT_PASSWORD`. Never commit production secrets.

Legacy prototype password hashes are intentionally unsupported. Historical database credentials previously committed to Git must be rotated.

## Development and test

```sh
./scripts/rebuild.sh
./scripts/test.sh
```

Useful operations:

```sh
./scripts/status.sh
./scripts/logs.sh
./scripts/reset-db.sh
```

See `docs/DOCKER.md`, `docs/SECURITY.md`, `docs/ARCHITECTURE.md` and `docs/FEATURE-STATUS.md`.

## Repository layout

- `public/` — the only web-accessible document root
- `bootstrap.php` — session/application bootstrap
- `database.php` — environment-based DB connection
- `lib/` — shared application helpers
- `migrations/` — versioned schema
- `docker/` — Apache/container configuration
- `scripts/` — build, test and operations scripts
- `docs/` — architecture, security and operational documentation

Phase 3 will introduce `src/` application modules and implement advert creation/editing, aviation categories, search/browse, listing lifecycle and image handling.

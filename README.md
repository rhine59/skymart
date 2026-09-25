# SkyMart

SkyMart is an aviation-focused marketplace for buying and selling aircraft, avionics, instruments, parts and related equipment.

## Current status

Phase 2 establishes the secure application foundation: environment-based configuration, server-side sessions, CSRF protection, prepared SQL, modern password hashing, secure registration/logout and versioned database migrations.

The marketplace UI itself begins in Phase 3.

## Requirements

- PHP 8.1+ with mysqli and mbstring
- MySQL 8+ or a compatible MariaDB release
- HTTPS for production

## Configuration

Copy the variable names from `.env.example` into the environment used by PHP. Do not put production secrets in a web-accessible file or commit them.

Required variables: `SKYMART_DB_HOST`, `SKYMART_DB_NAME`, `SKYMART_DB_USER`, `SKYMART_DB_PASSWORD`.

## Database

Apply `migrations/001_initial.sql` to a new development database. Back up an existing database before attempting any migration.

Legacy prototype password hashes are intentionally not supported. Existing prototype users should reset/recreate their passwords.

## Security

The active application uses PHP `password_hash()` / `password_verify()`, prepared statements, server-side session identity and CSRF tokens. PHP documents `PASSWORD_DEFAULT` hashes as self-describing and recommends allowing a password column to grow to 255 bytes.

Credentials previously committed to Git history must be rotated even though they have been removed from the active tree.

See `docs/SECURITY.md` and `docs/ARCHITECTURE.md`.

## Repository layout

- `index.php` - login
- `register.php` - registration
- `private.php` - authenticated account landing page
- `logout.php` - secure logout endpoint
- `bootstrap.php` - common application/session bootstrap
- `database.php` - environment-based DB connection
- `lib/` - shared application helpers
- `migrations/` - versioned schema
- `docs/` - architecture, security and feature status

## Next

Phase 3 will implement advert creation/editing, aviation categories, search/browse, listing lifecycle and image handling.


## Docker development

Build/start the development stack:

```sh
./scripts/rebuild.sh
```

Run the isolated automated test harness:

```sh
./scripts/test.sh
```

Useful operations:

```sh
./scripts/status.sh
./scripts/logs.sh
./scripts/reset-db.sh
```

See `docs/DOCKER.md` for the container architecture, test behaviour and Synology notes.

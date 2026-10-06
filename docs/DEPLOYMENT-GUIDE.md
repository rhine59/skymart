# SkyMart Deployment and Operations Guide

## 1. Deployment model

The GitHub `main` branch is authoritative. A deployment should always be traceable to a specific `main` commit.

Runtime topology:

```text
Internet / client
      |
HTTPS :8082
      |
FRITZ!Box port forward
external 8082 -> Synology 8442
      |
Synology DSM HTTPS reverse proxy :8442
      |
HTTP 127.0.0.1:8082
      |
skymart-web (PHP 8.4 + Apache)
      |
private Docker network
      |
skymart-db (MariaDB 11.4)
```

MariaDB is not published to the host. Apache serves only `public/`. Listing image files live in the `skymart-uploads` Docker volume and are served through the controlled media endpoint.

## 2. Prerequisites

Development/test:

- Git
- Docker Engine
- Docker Compose v2
- curl

Synology:

- Container Manager / Docker Compose v2
- Git available for the deployment checkout
- reverse proxy with a valid HTTPS certificate for production
- persistent storage and a backup destination
- outbound mail solution before password reset is enabled publicly

On the current Synology environment Docker/Git may need their absolute package paths in non-interactive shells. Do not solve that by embedding credentials in scripts.

## 3. Obtain authoritative source

Clone once:

```sh
git clone https://github.com/rhine59/skymart.git
cd skymart
git checkout main
```

For an existing deployment:

```sh
git fetch origin
git checkout main
git pull --ff-only origin main
git status
git rev-parse HEAD
```

The working tree should be clean before deployment. Never deploy an uncommitted NAS edit as if it were authoritative.

## 4. Development deployment

The base Compose file defines the application/database. The development override publishes Apache on loopback only.

```sh
./scripts/rebuild.sh
```

Default endpoint is `127.0.0.1:8080`. Override it when required:

```sh
SKYMART_PORT=8082 ./scripts/rebuild.sh
```

The development Compose credentials are intentionally development-only and must never be reused in production.

## 5. Automated validation

Run before deployment and after meaningful infrastructure/database changes:

```sh
./scripts/test.sh
```

The test harness creates an isolated `skymart-test` stack and temporary database/upload volumes. It validates:

- PHP 8.4 baseline and PHP syntax;
- public-root isolation;
- expected loopback-only exposure;
- schema/category creation;
- API health/categories/listing pagination validation;
- registration and CSRF;
- unauthenticated access control.

The harness destroys its isolated stack after the run. A successful result ends with a PASS line.

## 6. Production configuration

Create production environment values outside Git. Required database values are:

```text
SKYMART_DB_NAME
SKYMART_DB_USER
SKYMART_DB_PASSWORD
SKYMART_DB_ROOT_PASSWORD
```

Application/account functionality also requires:

```text
SKYMART_PUBLIC_URL=https://<approved-public-host>
SKYMART_MAIL_FROM=<approved-sender>
```

Optional bind controls:

```text
SKYMART_BIND_ADDRESS=127.0.0.1
SKYMART_PORT=<chosen-local-port>
```

Do not commit the production environment file. Historical database credentials that appeared in old repository history must be treated as compromised and rotated.

## 7. Synology production start

From the deployment directory, with production environment variables loaded:

```sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

The production override:

- requires database secrets from the environment;
- binds the web service to loopback by default;
- enables `SKYMART_TRUST_PROXY=1`;
- leaves MariaDB without a host-published port;
- uses persistent database and upload volumes.

Check status and health before exposing traffic.

## 8. Synology reverse proxy and HTTPS

The current approved public origin is:

`https://skymart.granvillehouse.synology.me:8082`

The verified production-style path on 6 October 2026 is:

```text
Internet HTTPS :8082
  -> FRITZ!Box TCP external 8082 to Synology 8442
  -> DSM reverse proxy source HTTPS skymart.granvillehouse.synology.me:8442
  -> destination HTTP 127.0.0.1:8082
  -> skymart-web
```

SkyMart remains bound to loopback; port 8082 on the container/backend is not directly exposed to the Internet. The unusual external port is intentional so the existing public port 443 configuration is not changed.

DSM has a dedicated Let's Encrypt certificate whose CN/SAN matches `skymart.granvillehouse.synology.me`, assigned specifically to the SkyMart reverse-proxy service. TLS 1.3, certificate verification and `GET /health.php` returning HTTP 200 were verified externally on 6 October 2026.

Configure/retain the Synology reverse proxy so the approved public HTTPS hostname forwards to the SkyMart loopback HTTP port.

Requirements:

1. Public side is HTTPS with a valid certificate.
2. Backend target is loopback, not a public Docker port.
3. Forwarded HTTPS headers are preserved so SkyMart can create secure cookies.
4. `SKYMART_TRUST_PROXY=1` is used only behind the trusted proxy.
5. `SKYMART_PUBLIC_URL` exactly matches `https://skymart.granvillehouse.synology.me:8082` on the current deployment.

Do not enable trusted-proxy handling on an Internet-exposed container that can receive spoofed forwarding headers directly.

## 9. Database migrations

Migrations are ordered files in `migrations/`. The migration runner records applied filenames in `schema_migrations`.

Development:

```sh
./scripts/migrate.sh
```

Production should run the same script with `SKYMART_COMPOSE_FILES` set to the production Compose files and the production root/database variables available.

Before any destructive or non-backwards-compatible migration:

1. back up the database;
2. test the migration on a restored copy;
3. confirm application rollback strategy;
4. deploy;
5. verify schema and application health.

Migration `004_account_lifecycle.sql` uses ALTER TABLE and is not intrinsically idempotent outside the migration ledger; never manually rerun an already-recorded migration.

## 10. Routine operations

```sh
./scripts/status.sh
./scripts/logs.sh
```

Use container health plus the application endpoints:

- `/health.php` for web/container health;
- `/api/v1/health` for API routing/health.

Current operational status (6 October 2026): the public `/health.php` endpoint is verified HTTP 200 with a valid certificate. `/api/v1/health` currently returns HTTP 404 both at `http://127.0.0.1:8082` on the NAS and through the public proxy. Because the failure is also present on the loopback backend, it is an application/Apache routing regression rather than a DSM reverse-proxy fault. Do not mark the API healthy until this is fixed and the server harness is rerun.

The development reset command:

```sh
./scripts/reset-db.sh
```

destroys development database state. Do **not** use it against production.

## 11. Backup policy

A complete recoverable backup needs both:

1. MariaDB data, preferably a logical dump plus protected platform-level backup; and
2. the `skymart-uploads` volume containing listing display images/thumbnails.

Also retain:

- the deployed Git commit SHA;
- production environment/secrets in an appropriate secret/password manager;
- reverse-proxy/certificate configuration;
- restore instructions.

Do not rely on Git as a database or upload backup.

## 12. Restore procedure

Practice this before public launch.

1. Provision a clean Docker host/test project.
2. Check out the exact intended `main` commit.
3. Restore production-like environment configuration using non-production secrets.
4. Restore the MariaDB dump.
5. Restore listing uploads.
6. Start the stack.
7. Run health/API checks.
8. Verify representative accounts, listings and images.
9. Only then treat the backup as proven recoverable.

## 13. Upgrade procedure

Recommended release flow:

1. Develop and validate changes.
2. Merge approved changes to `main`.
3. Record the target commit SHA.
4. Run the isolated harness against that commit.
5. Back up production DB/uploads.
6. Pull `main` with `--ff-only`.
7. Build updated images.
8. Apply pending migrations.
9. Start/restart services.
10. Verify health, API, login and representative listing/image paths.
11. Monitor logs.

If verification fails, stop exposing the failed release, preserve evidence/logs and restore the prior application commit/database backup as required.

## 14. Production readiness checklist

Do not call the service public-production-ready until all are true:

- production DB/root credentials rotated and unique;
- HTTPS hostname/certificate configured;
- reverse proxy tested;
- `SKYMART_PUBLIC_URL` correct;
- production mail transport tested;
- database and upload backups scheduled;
- restore drill passed;
- login and password-reset rate limiting implemented;
- security headers/HSTS/CSP reviewed;
- full server harness passes;
- iPhone client has a real Xcode/device build and end-to-end API test;
- operational monitoring/log retention agreed;
- account/admin flows tested with non-production accounts.

## 15. Current Synology deployment

The current project uses containers named `skymart-web` and `skymart-db`. The web backend is loopback-bound at `127.0.0.1:8082` because host port 8080 is already occupied. Public access is only through the DSM reverse proxy topology documented in section 8. Treat backend port selection as deployment configuration rather than hard-coding a Synology-specific value into application source.

For this deployment set:

```text
SKYMART_PUBLIC_URL=https://skymart.granvillehouse.synology.me:8082
```

The FRITZ!Box rule is TCP only: external port 8082 to the Synology device port 8442. DSM owns TLS on 8442 and proxies to the loopback HTTP backend on 8082.

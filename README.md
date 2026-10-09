# SkyMart

SkyMart is an aviation-focused marketplace for buying and selling aircraft, engines, propellers, avionics, instruments, parts, pilot equipment and miscellaneous aeronautical items.

## Authoritative source

The GitHub `main` branch is the source of truth. Deployments must be built from a known `main` commit; local NAS/Mac edits and unmerged branches are not authoritative.

## Current platform

- PHP 8.4 + Apache 2.4
- MariaDB 11.4 LTS / InnoDB / utf8mb4
- Server-rendered web account UI plus versioned JSON API at `/api/v1`
- SwiftUI iPhone client source with a shared `MarketplaceService` boundary
- Docker Compose development, isolated test and Synology production profiles
- Listing image processing with PHP GD and storage outside the public document root
- Browser sessions plus opaque 30-day bearer tokens for native clients

Apache exposes only `public/`. Application code, migrations, configuration and uploaded originals are not directly web-accessible.

## Documentation

| Guide | Purpose |
|---|---|
| [User guide](docs/USER-GUIDE.md) | Account, browsing, selling, photographs, password and administrator workflows |
| [Deployment guide](docs/DEPLOYMENT-GUIDE.md) | Development, test, Synology deployment, migrations, reverse proxy, backup, restore and operations |
| [Architecture guide](docs/ARCHITECTURE.md) | Components, request/data flows, security boundaries, schema, API/mobile design and architectural decisions |
| [API reference](docs/API.md) | Current `/api/v1` contract |
| [Security guide](docs/SECURITY.md) | Security controls, risks and production requirements |
| [Feature status](docs/FEATURE-STATUS.md) | Implemented/tested/pending capability matrix |
| [Web/iPhone parity](docs/CLIENT-PARITY.md) | Shared-data contract, capability audit and cross-client acceptance checklist |
| [Changelog](CHANGELOG.md) | Repository change history |

## Development and validation

```sh
./scripts/rebuild.sh
./scripts/test.sh
```

Useful operations:

```sh
./scripts/status.sh
./scripts/logs.sh
./scripts/migrate.sh
./scripts/reset-db.sh
```

The merged Phase 3 checkpoint previously passed the complete server harness. On 6 October 2026 the live Synology deployment exposed a current API-routing regression: `/api/v1/health` returns HTTP 404 both directly on the loopback backend and through the public reverse proxy. The web health endpoint remains healthy. Treat API routing as unresolved until fixed and the harness is rerun.

## Repository layout

- `public/` — only HTTP document root; browser pages, API and controlled media endpoint
- `src/` — reusable domain/application services
- `lib/` — shared security helpers
- `migrations/` — ordered database schema changes
- `ios/SkyMartPrototype/` — SwiftUI client source
- `docker/` and `docker-compose*.yml` — container/deployment definitions
- `scripts/` — build, migration, test and operational commands
- `docs/` — maintained technical and user documentation

## Current public deployment

Canonical public web origin:

`https://skymart.granvillehouse.synology.me:8082`

The verified path is external TCP `8082` → FRITZ!Box port forwarding → Synology TCP `8442` → DSM HTTPS reverse proxy → `http://127.0.0.1:8082`. A dedicated Let's Encrypt certificate for `skymart.granvillehouse.synology.me` is assigned to the SkyMart reverse proxy; TLS 1.3 and `/health.php` HTTP 200 were verified on 6 October 2026. Do not expose the Docker backend directly.

## Production readiness

The server foundation and Phase 3 API passed the earlier automated harness, but the current live deployment has an unresolved API-routing 404 regression that must be fixed and retested. Public production launch still requires the deployment checklist in the Deployment Guide, including rotated secrets, HTTPS/reverse proxy, mail transport, backups/restore testing and rate limiting. The iPhone source also still requires a complete Xcode project/device build and end-to-end validation before release.

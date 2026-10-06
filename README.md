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

The complete server harness on merged Phase 3 validates PHP 8.4, API routing, public-root isolation, migrations, registration and access control.

## Repository layout

- `public/` — only HTTP document root; browser pages, API and controlled media endpoint
- `src/` — reusable domain/application services
- `lib/` — shared security helpers
- `migrations/` — ordered database schema changes
- `ios/SkyMartPrototype/` — SwiftUI client source
- `docker/` and `docker-compose*.yml` — container/deployment definitions
- `scripts/` — build, migration, test and operational commands
- `docs/` — maintained technical and user documentation

## Production readiness

The server foundation and Phase 3 API pass the automated harness. Public production launch still requires the deployment checklist in the Deployment Guide, including rotated secrets, HTTPS/reverse proxy, mail transport, backups/restore testing and rate limiting. The iPhone source also still requires a complete Xcode project/device build and end-to-end validation before release.

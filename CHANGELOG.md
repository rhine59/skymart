# Changelog

## Unreleased

### Phase 2
- Added hardened session bootstrap.
- Added CSRF helpers and authentication guard.
- Rebuilt registration using prepared SQL and password_hash().
- Added POST-only logout.
- Rebuilt private account page around server-side session identity.
- Added initial users/categories/listings migration.
- Added environment example and architecture/security/feature documentation.
- Removed legacy prototype and credential-bearing files from the active tree.

- Added PHP/Apache and MariaDB Docker Compose environment.
- Added isolated Docker test override and health endpoint.
- Added rebuild, reset, status, logs and automated test scripts.
- Added Docker/Synology operating documentation.

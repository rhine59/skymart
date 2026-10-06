# Changelog

## Unreleased

### Deployment checkpoint — 6 October 2026
- Established canonical public web origin `https://skymart.granvillehouse.synology.me:8082`.
- Verified DNS, external connectivity, TLS 1.3, dedicated Let's Encrypt hostname certificate and public `/health.php` HTTP 200.
- Documented network path: FRITZ!Box external TCP 8082 → Synology 8442 → DSM HTTPS reverse proxy → loopback SkyMart HTTP 8082.
- Kept the SkyMart Docker backend loopback-only rather than exposing it directly.
- Identified a current `/api/v1/health` HTTP 404 regression on both loopback and public paths; this is an application/Apache routing issue and remains open pending fix and harness rerun.

### Documentation
- Added comprehensive User Guide covering accounts, browsing, selling, images, password lifecycle and administration.
- Added comprehensive Deployment and Operations Guide covering authoritative-source workflow, Docker/Synology deployment, migrations, HTTPS proxying, backups, restores, upgrades and production readiness.
- Reworked Architecture Guide to document the merged Phase 3 modular-monolith, API/native authentication, data model, image pipeline, trust boundaries and scaling decisions.
- Updated README and feature-status documentation to reflect the actual merged `main` implementation rather than the earlier Phase 2/Phase 3 plan.

### Phase 3
- Added versioned `/api/v1` marketplace boundary and routing.
- Added reusable listing, image, authentication, account, administration and mail services.
- Added native opaque bearer tokens and account lifecycle/password reset schema.
- Added listing-image storage, validation, resizing and controlled media delivery.
- Added SwiftUI marketplace service/API boundary, Keychain authentication, account workflows and Sell/photo-upload source.
- Integrated Phase 3 with Phase 2 and fixed API front-controller routing recursion.
- Merged Phase 3 to `main`; full server harness passed on the merged commit.

### Phase 2
- Hardened sessions, CSRF, authentication and modern password hashing.
- Added prepared SQL, secure registration/logout and initial users/categories/listings migration.
- Removed legacy prototype and credential-bearing files from the active tree.
- Added Docker Compose development and isolated test environments.
- Standardised the application container on PHP 8.4 + Apache.
- Retained MariaDB 11.4 LTS as the database baseline.
- Restricted Apache's document root to `public/`.
- Added explicit trusted-proxy handling for production HTTPS.
- Added a Synology production Compose override with environment-supplied secrets.
- Expanded automated tests to verify PHP baseline and public-root isolation.

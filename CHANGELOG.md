# Changelog

## Unreleased

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
- Updated architecture, Docker and repository documentation.

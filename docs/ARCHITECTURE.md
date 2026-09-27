# SkyMart architecture

## Supported baseline

SkyMart is a PHP 8.4 application on Apache 2.4, backed by MariaDB 11.4 LTS/InnoDB/utf8mb4 and deployed with Docker Compose.

Browser / SwiftUI app -> HTTPS Synology reverse proxy -> PHP 8.4/Apache container -> private Docker network -> MariaDB 11.4.

Apache exposes only `/var/www/skymart/public`. Application code, configuration, migrations and documentation live outside the document root and cannot be requested directly.

`bootstrap.php` owns session startup and common dependencies. `database.php` owns database connectivity. `lib/security.php` owns escaping, CSRF and authentication guards. Forwarded HTTPS is trusted only when `SKYMART_TRUST_PROXY=1`, intended for the production Synology reverse proxy.

## Application boundary

The browser UI and native iPhone application share the same marketplace domain and database. Native/mobile access is through the versioned JSON boundary at `/api/v1`; its contract is documented in `docs/API.md`.

Public API entry points remain thin. Marketplace logic belongs in reusable `src/` services so browser controllers and API controllers cannot develop different business rules.

The SwiftUI client depends on `MarketplaceService`, not directly on URLSession. Simulator builds can use `MockMarketplaceService`; production uses `APIMarketplaceService`. This preserves rapid UI development and makes the backend replaceable without coupling screens to transport details.

## Security

Browser authentication uses server-side PHP sessions, strict cookie mode, HttpOnly and SameSite=Lax. Secure cookies are enabled for direct HTTPS or the explicitly trusted HTTPS proxy. Successful login regenerates the session ID. Passwords use `password_hash(PASSWORD_DEFAULT)` and `password_verify()`.

API authentication will reuse server-side identity and authorization rules but must expose an explicit mobile-safe authentication mechanism before the production iPhone client is enabled. Credentials are never stored in source or mock data.

## Evolution

Schema changes live in `migrations/`. Phase 3 introduces reusable marketplace services for listings, images, profiles, favourites and moderation.

A SPA, Python rewrite, microservices, Redis, message queues and a dedicated search service are deliberately deferred. They add operational cost without solving a current SkyMart requirement. MariaDB indexes and well-shaped SQL should be exhausted before adding search infrastructure.

## Performance principles

- paginate all collection endpoints;
- select only required columns;
- index filter/sort/foreign-key columns;
- avoid N+1 queries;
- serve resized listing images rather than originals;
- use immutable caching for public static assets;
- keep API payloads stable and compact;
- measure before adding infrastructure.

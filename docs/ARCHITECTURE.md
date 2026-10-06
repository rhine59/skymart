# SkyMart Architecture Guide

## 1. Architectural goals

SkyMart is intentionally a small, maintainable marketplace architecture rather than a microservice platform. The design optimises for secure deployment on a Synology-class host, shared business rules for web/native clients, simple recovery and a path to scale only when measured demand requires it.

Core principles:

- GitHub `main` is the authoritative source;
- one relational source of truth;
- one versioned native API;
- business logic in reusable services, not duplicated in controllers;
- only `public/` is web-accessible;
- secrets remain outside Git;
- uploads remain outside the document root;
- server is authoritative for validation, authentication and ownership;
- measure before adding infrastructure.

## 2. Logical topology

```text
                    +----------------------+
Browser ------------|                      |
                    | Synology HTTPS       |---- loopback ----+
iPhone --------------| reverse proxy        |                  |
                    +----------------------+                  v
                                                   +--------------------+
                                                   | skymart-web        |
                                                   | PHP 8.4 / Apache   |
                                                   |                    |
                                                   | public/            |
                                                   | bootstrap.php      |
                                                   | lib/security.php   |
                                                   | src/*Service.php   |
                                                   +----+----------+----+
                                                        |          |
                                               SQL      |          | filesystem
                                                        v          v
                                                +-------------+  +----------------+
                                                | skymart-db  |  | upload volume  |
                                                | MariaDB     |  | listing JPEGs  |
                                                +-------------+  +----------------+
```

The database and upload store are not public network services.

## 3. Repository/component map

### HTTP boundary — `public/`

Contains browser controllers, health endpoint, `api/v1/index.php`, admin UI and controlled listing-media endpoint. Apache's DocumentRoot is `/var/www/skymart/public`.

### Bootstrap/security

`bootstrap.php` starts the PHP session and common dependencies. `database.php` builds the mysqli connection from environment variables. `lib/security.php` provides escaping, CSRF and browser authentication guards.

### Application services — `src/`

- `ListingService` — category lookup, public search/detail, seller listings, create/update/withdraw and response shaping.
- `ImageService` — listing ownership check, JPEG validation/re-encoding/resizing, metadata and deletion.
- `AuthService` — native login, bearer-token authentication, token revocation.
- `AccountService` — profile, password change/reset, deactivation and token revocation.
- `AdminAccountService` — administrative account listing/status/device controls.
- `MailService` — password-reset mail boundary.

Controllers should remain thin and delegate domain behaviour here.

## 4. Browser request flow

```text
Browser
  -> HTTPS reverse proxy
  -> Apache public/*.php
  -> bootstrap.php
  -> session / CSRF / auth guard
  -> application/database operation
  -> escaped server-rendered HTML
```

Browser authentication uses a server-side PHP session. Login regenerates the session identifier. Cookies are HttpOnly, SameSite=Lax and Secure when HTTPS is direct or explicitly trusted through the production proxy.

## 5. Native/API request flow

```text
SwiftUI View
  -> MarketplaceService / AuthenticationService
  -> SkyMartAPI (URLSession)
  -> HTTPS /api/v1/*
  -> API front controller
  -> AuthService / AccountService / ListingService / ImageService
  -> MariaDB or upload store
  -> JSON
  -> Codable model
  -> SwiftUI state
```

The iPhone UI depends on protocols rather than URLSession directly. `MockMarketplaceService` supports UI development; `SkyMartAPI` is the real transport implementation.

## 6. API routing

Apache rewrites `/api/v1/... ` to the API front controller while explicitly excluding an already-routed `api/v1/index.php` request to prevent internal redirect recursion.

The API front controller derives the route from `PATH_INFO`, validates input and returns a stable JSON error shape.

See [API reference](API.md).

## 7. Authentication and authorization

### Browser

- email/password verified server-side;
- PHP `password_hash(PASSWORD_DEFAULT)` / `password_verify()`;
- server-side session identity;
- CSRF token on state-changing browser forms;
- `require_login()` for protected pages.

### Native

On successful `POST /api/v1/auth/login`:

1. password is verified;
2. 32 random bytes are generated and hex encoded (64 characters);
3. only the SHA-256 binary hash is stored in `api_tokens`;
4. the raw token is returned once;
5. iOS stores the raw token in Keychain;
6. authenticated calls send `Authorization: Bearer <token>`.

Tokens expire after 30 days. Logout revokes the current token. Password change/reset, deactivation and admin device revocation revoke applicable tokens.

Authorization is always rechecked on the server. Listing write operations require ownership. Admin operations require an active `admin` role.

## 8. Account lifecycle

`users.status` values:

- `active`
- `disabled`
- `deactivated`

`users.role` values:

- `user`
- `admin`

Password reset uses a random token whose SHA-256 hash is stored in `password_reset_tokens`. It is single-use and expires after 30 minutes. Reset request responses are deliberately indistinguishable for known/unknown email addresses.

## 9. Marketplace data model

Core relationships:

```text
users 1 ---- * listings * ---- 1 categories
  |
  +---- * api_tokens
  |
  +---- * password_reset_tokens

listings 1 ---- * listing_images
```

### users

Identity, password hash, contact data, role/status and deactivation timestamp.

### categories

Stable marketplace category IDs/names/slugs. Eight categories are seeded by migration 001.

### listings

Seller/category foreign keys, title, description, decimal GBP price, location, lifecycle status, timestamps and optional expiry.

### listing_images

Storage key, dimensions and sort order. Files themselves live in the upload volume rather than as database BLOBs.

### api_tokens

Hashed native bearer tokens, device label, expiry, revocation and last-use metadata.

### password_reset_tokens

Hashed single-use reset tokens with expiry/use timestamps.

## 10. Listing lifecycle and visibility

Public search/detail returns only listings where:

- status is `active`; and
- `expires_at` is null or later than the current UTC time.

Seller operations can address their own private/non-public states. Current creation writes an advert directly as active. Architecturally, a draft → image upload → publish transition is preferred before public launch so a failed image sequence cannot expose a partially prepared advert.

## 11. Search

Search is deliberately MariaDB-based. Current public search matches title, description and location with category filtering and pagination. Migration 002 adds listing search indexes.

A separate search service is not justified until measured data volume/latency demonstrates a need. Before adding one, improve SQL shape/indexing and remove known N+1 patterns.

## 12. Image architecture

Upload flow:

```text
iPhone JPEG
 -> authenticated POST
 -> ownership check
 -> byte/dimension validation
 -> GD decode
 -> display resize <= 2000 px
 -> thumbnail resize <= 480 px
 -> JPEG re-encode
 -> random storage key
 -> upload volume
 -> listing_images metadata
```

Benefits include metadata stripping, bounded display dimensions and no direct exposure of the upload filesystem.

Known improvement: file writes and DB metadata insertion are not currently a single transactional unit; failures can theoretically orphan filesystem data. A cleanup/transaction strategy should be added.

## 13. Deployment boundaries

Base Compose creates:

- `skymart-db` on the private Compose network;
- `skymart-web` depending on DB health;
- persistent `skymart-db` and `skymart-uploads` volumes.

Development/test/production behaviour is expressed through Compose overrides rather than separate application forks.

Production binds Apache to loopback and relies on the Synology reverse proxy for public HTTPS.

## 14. Database migrations

Schema evolution is append-only through ordered files in `migrations/`. `scripts/migrate.sh` maintains a `schema_migrations` ledger. The ledger, not SQL-level idempotence, is the protection against rerunning ALTER migrations.

New schema changes should be new migration files; never edit an already-deployed migration to represent a new production change.

## 15. Security boundaries

Trust boundaries are:

1. Internet/client → reverse proxy;
2. reverse proxy → web container;
3. HTTP controller → application service;
4. application → MariaDB;
5. application → upload filesystem;
6. native app → Keychain.

Important controls:

- prepared SQL for user-controlled values;
- HTML escaping;
- CSRF for browser state changes;
- opaque native tokens stored hashed server-side;
- public document-root isolation;
- upload re-encoding and controlled serving;
- no DB host port;
- environment-supplied production secrets.

Known production gaps are tracked in [Security](SECURITY.md) and [Feature Status](FEATURE-STATUS.md).

## 16. Performance/scaling

Current priorities:

- paginate collection endpoints;
- select only required columns;
- index filter/sort/foreign-key columns;
- remove the current listing-image N+1 query pattern;
- serve generated display/thumbnail images;
- use cache headers for immutable media/static assets;
- measure query and endpoint latency.

Do not add Redis, queues, microservices, Kubernetes or a dedicated search engine merely for architectural fashion. Add them only for a measured operational requirement.

## 17. Availability and recovery

The current design is a single application/database deployment, so host/database availability remains a single-site concern. Recovery therefore depends on:

- reproducible application source from `main`;
- MariaDB backup;
- upload-volume backup;
- external secret/config retention;
- tested restore procedure.

Horizontal scale would require shared upload storage/object storage and careful session strategy before adding multiple web replicas.

## 18. iPhone architecture status

The repository contains SwiftUI source and a real `SkyMartAPI` implementation with Keychain token persistence, browse/search, login/account and Sell/photo upload flows.

It does **not** yet contain a completed, validated Xcode project suitable to describe as a released application. This is an important architectural/status distinction: server API implementation is tested by the Docker harness; iOS source integration still requires compile/device/end-to-end validation.

## 19. Architectural decisions and next priorities

Retain:

- PHP/Apache + MariaDB;
- modular monolith;
- versioned REST/JSON boundary;
- Swift service abstraction;
- Synology reverse-proxy deployment;
- filesystem images outside public root.

Prioritise next:

1. complete Xcode project and integration tests;
2. draft → images → publish workflow;
3. remove listing-image N+1;
4. robust mail transport;
5. login/reset rate limiting;
6. backup/restore drill;
7. favourites/moderation only after core selling flow is production-safe.

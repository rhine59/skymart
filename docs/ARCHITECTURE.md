# SkyMart architecture

## Phase 2 baseline

SkyMart remains a small server-rendered PHP application backed by MySQL/MariaDB. This keeps deployment simple on the Synology while the marketplace domain is established.

### Request flow

Browser -> HTTPS reverse proxy/web server -> PHP -> MySQL/MariaDB.

`bootstrap.php` owns session startup and common dependencies. `database.php` owns database connectivity. `lib/security.php` owns escaping, CSRF and authentication guards.

### Authentication

Identity is held server-side in the PHP session. Session identifiers use cookies only, strict mode, HttpOnly and SameSite=Lax. Secure cookies are enabled automatically when HTTPS is detected. Successful login regenerates the session ID.

Passwords are stored using `password_hash(PASSWORD_DEFAULT)` and checked using `password_verify()`.

### Database

Schema changes live in `migrations/`. Phase 2 introduces users, categories and the initial listings table. Production schema changes must be applied explicitly and backed up first.

### Next boundary

Phase 3 should separate marketplace concerns into small application modules for listings, images, profiles, favourites and moderation rather than returning to one-file prototypes.

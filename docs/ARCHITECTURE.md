# SkyMart architecture

## Supported baseline

SkyMart is a server-rendered PHP 8.4 application on Apache 2.4, backed by MariaDB 11.4 LTS/InnoDB/utf8mb4 and deployed with Docker Compose.

Browser -> HTTPS Synology reverse proxy -> PHP 8.4/Apache container -> private Docker network -> MariaDB 11.4.

Apache exposes only `/var/www/skymart/public`. Application code, configuration, migrations and documentation live outside the document root and cannot be requested directly.

`bootstrap.php` owns session startup and common dependencies. `database.php` owns database connectivity. `lib/security.php` owns escaping, CSRF and authentication guards. Forwarded HTTPS is trusted only when `SKYMART_TRUST_PROXY=1`, which is intended for the production Synology reverse proxy.

Authentication uses server-side PHP sessions, strict cookie mode, HttpOnly and SameSite=Lax. Secure cookies are enabled for direct HTTPS or the explicitly trusted HTTPS proxy. Successful login regenerates the session ID. Passwords use `password_hash(PASSWORD_DEFAULT)` and `password_verify()`.

Schema changes live in `migrations/`. Phase 3 should place marketplace concerns in `src/` modules for listings, images, profiles, favourites and moderation while keeping public entry points thin. A SPA, microservices, Redis and dedicated search service are deliberately deferred until requirements justify them.

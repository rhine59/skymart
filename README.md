# SkyMart

SkyMart is an aviation-focused marketplace project for buying and selling aircraft, avionics, instruments, parts and related equipment.

## Status

The repository is being converted from an early PHP/MySQL prototype into a maintainable application. The current code must be treated as development software, not production-ready.

## Security baseline

- Database credentials are supplied through environment variables and must never be committed.
- Login uses parameterised SQL and PHP password verification.
- Authentication state should use PHP sessions rather than client-controlled identity cookies.
- Production must use HTTPS.
- Errors must be logged server-side without exposing database details to users.
- All database operations must use prepared statements.
- Passwords for new/reset accounts must use `password_hash()`.

Required environment variables:

```
SKYMART_DB_HOST=localhost
SKYMART_DB_NAME=skymart
SKYMART_DB_USER=...
SKYMART_DB_PASSWORD=...
```

## Important credential-rotation notice

Credentials previously existed in repository history. Any database passwords that have ever been committed must be considered compromised and rotated. Removing them from the current branch does not remove them from Git history.

## Current cleanup

The repository still contains legacy prototype pages. These are retained temporarily for reference while the application is rebuilt. They must not be exposed by a production web server.

## Next development stages

1. Complete secure account registration and password migration/reset.
2. Add a single authentication/session layer and logout.
3. Establish database migrations/schema.
4. Build listings, categories, images and seller profiles.
5. Add search/filtering and advert management.
6. Add automated security and application tests.
7. Containerise deployment for development and Synology hosting.

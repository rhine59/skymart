# Security

## Current baseline

- No database credentials in current source files.
- Secrets are supplied through environment variables.
- Prepared statements are required for all SQL containing user input.
- Passwords use PHP password hashing APIs.
- Authentication uses server-side sessions.
- Login regenerates the session ID.
- State-changing forms carry CSRF tokens.
- HTML output derived from users must be escaped.
- Production must be HTTPS-only.

## Mandatory operational action

Credentials that appeared in earlier Git history must be considered exposed and rotated. Cleaning the current tree does not revoke those credentials or erase historical commits.

## Legacy accounts

Old MySQL `PASSWORD()` hashes are not retained as an authentication fallback. Existing prototype accounts should receive a password reset or be recreated.

## Before production

Add rate limiting for login/registration, email verification, password reset, security headers/CSP, upload validation and storage isolation, audit logging, dependency scanning, automated tests, backup/restore tests and production HTTPS/HSTS validation.

## Mobile API tokens

Native authentication uses opaque 256-bit random bearer tokens. Only SHA-256 hashes are persisted in `api_tokens`; raw tokens are returned once at login and stored by iOS in Keychain. Tokens expire after 30 days and are individually revocable on logout. Invalid/expired tokens return HTTP 401. Token last-used timestamps are updated at most hourly to avoid a database write on every request.

Bearer tokens must only be accepted over production HTTPS. Production should add login rate limiting before public release.

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
- Current public HTTPS origin is `https://skymart.granvillehouse.synology.me:8082`; DSM terminates TLS using a dedicated Let's Encrypt certificate for that hostname and proxies to the loopback-only backend.
- The FRITZ!Box exposes only the DSM reverse-proxy listener for SkyMart (external TCP 8082 to NAS 8442); the application backend at `127.0.0.1:8082` remains non-public.

## Mandatory operational action

Credentials that appeared in earlier Git history must be considered exposed and rotated. Cleaning the current tree does not revoke those credentials or erase historical commits.

## Legacy accounts

Old MySQL `PASSWORD()` hashes are not retained as an authentication fallback. Existing prototype accounts should receive a password reset or be recreated.

## Before production

Add rate limiting for login/registration, email verification, password reset, security headers/CSP, upload validation and storage isolation, audit logging, dependency scanning, automated tests, backup/restore tests and production HTTPS/HSTS validation.

## Mobile API tokens

Native authentication uses opaque 256-bit random bearer tokens. Only SHA-256 hashes are persisted in `api_tokens`; raw tokens are returned once at login and stored by iOS in Keychain. Tokens expire after 30 days and are individually revocable on logout. Invalid/expired tokens return HTTP 401. Token last-used timestamps are updated at most hourly to avoid a database write on every request.

Bearer tokens must only be accepted over production HTTPS. Production should add login rate limiting before public release.

## Account lifecycle and administration

Users can update their profile, change their password and deactivate their account. Password changes, password resets, account disabling and deactivation revoke API tokens. Password-reset tokens are random, stored only as SHA-256 hashes, single-use and expire after 30 minutes. Reset-request responses do not reveal whether an email address is registered.

Administrative account operations require an active user with `role=admin` on every request. Administrators can search accounts, disable/re-enable non-deactivated accounts and revoke device tokens. The API prevents an administrator disabling their own account. Existing passwords are never exposed.

Password-reset mail uses `SKYMART_PUBLIC_URL` and `SKYMART_MAIL_FROM`; mail transport must be configured on the production PHP host/container. Production deployment must use an HTTPS public URL and should add login/reset-request rate limiting before public launch.

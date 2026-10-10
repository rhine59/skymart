# Conversational search and saved searches — implementation contract

Status: design checkpoint; NOT yet implemented.

## Existing integration points
PHP 8.4 / MariaDB 11.4, ListingService, public GET /api/v1/listings, browser PHP sessions, 30-day native bearer tokens, SwiftUI MarketplaceService. Preserve anonymous basic search.

## Behaviour
- Advanced natural-language search, conversational refinements, and saving require a logged-in account.
- Anonymous advanced requests return a machine-readable authentication-required response, with pending query stored in short-lived server session (browser) or app state (native); after sign-in/registration, resume exactly once.
- First query creates a search session. Each follow-up appends an immutable step with its natural-language utterance, parsed delta and resulting validated filters.
- Explicit intents: ADD_FILTER, REPLACE_FILTER, REMOVE_FILTER, SORT, RESET, UNDO, NEW_SEARCH, SAVE_SEARCH. Ambiguous requests require clarification, not silent changes.
- Results always come from the existing ListingService / prepared SQL. Never execute model-generated SQL.
- Search scope is current live listings, not a frozen result-ID set, unless a future snapshot mode is explicitly introduced.
- Persist saved search name, owner, original query, canonical filter JSON, parser version and optional history. CRUD scoped to owner; rerun against current listings.
- Natural-language parse failure falls back to clarification; never broaden the query silently.
- Geographical radius searches must be enabled only when reliable geocoded listing coordinates exist; location text alone cannot provide accurate distance.

## Proposed append-only migration
search_sessions(id, user_id, created_at, updated_at, expires_at, current_step_id)
search_steps(id, session_id, step_number, utterance, intent, delta_json, filters_json, created_at)
saved_searches(id, user_id, name, query_text, filters_json, parser_version, history_json, notify_enabled, created_at, updated_at)
Indexes: (user_id, updated_at), (session_id, step_number), (user_id, name).
Use DB-native JSON checks where supported; validate schemas in PHP.

## Canonical filter allowlist
q, category, min_price_gbp, max_price_gbp, location_text, posted_after, sort, page, per_page. Extend schema only alongside ListingService support. Bounds and allowed sort keys validated server-side. Do not infer engine hours, airworthiness or repair status unless adverts contain reliable structured fields; otherwise use conservative text matching and explain limits.

## Proposed API (authenticated)
POST /api/v1/search/sessions {query}
POST /api/v1/search/sessions/{id}/refine {query}
GET /api/v1/search/sessions/{id}
POST /api/v1/search/sessions/{id}/undo
GET /api/v1/saved-searches
POST /api/v1/saved-searches {name, session_id}
GET /api/v1/saved-searches/{id}
PATCH /api/v1/saved-searches/{id}
DELETE /api/v1/saved-searches/{id}
POST /api/v1/saved-searches/{id}/run

## Auth decisions
Browser: existing secure HttpOnly SameSite cookie and CSRF protection, never localStorage token.
iOS: persist a device-scoped revocable login credential in Keychain with no fixed calendar expiry; validate it with /api/v1/me. Restore authentication silently while valid. If revoked or invalidated, request login and resume the pending operation. Existing 30-day bearer-token implementation must be migrated before claiming this behaviour. Prefer short-lived access credentials plus a rotating, non-calendar-expiring persistent device credential where supported, with replay detection and per-device revocation.
Authentication credentials are independent of paid advert-duration entitlements: payment determines listing visibility through an explicit paid-until timestamp, never account-login lifetime. Expired paid adverts are hidden from public search but retained for seller renewal. Registration should not bypass any existing email-verification requirements.
Ensure 401/403 responses distinguish unauthenticated and unauthorized; never expose another user's search.

## Acceptance tests
1. Anonymous basic search works; anonymous advanced search redirects to login and resumes after successful login/registration.
2. Native valid persistent device credential survives long inactivity; revoked/invalidated credential requests login and resumes.
3. Refinement add/replace/remove, undo, reset, sorting, pagination and clarification preserve expected state.
4. Saved search CRUD and rerun work across logout/login and across browser/iOS.
5. Cross-account access to session or saved-search IDs is denied.
6. SQL injection, malicious prompt instructions, invalid JSON, oversized queries, CSRF and request rate limits are covered.
7. No false geospatial precision; no unsupported field inference.
8. Docker test harness passes, live API health verified, iOS compiled/device-tested before release.

## Delivery sequence
1. Verify current routes, auth, ListingService and migration numbering.
2. Add migration and PHP search/session services with deterministic parser baseline.
3. Add API routes and browser UI.
4. Add SwiftUI client flow and Keychain reauthentication.
5. Add test harness, docs, changelog; deploy from tested main.
6. Optional notification scheduler only after explicit opt-in, rate limiting and unsubscribe controls.

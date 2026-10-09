# Cross-platform functional parity — mandatory delivery policy

Status: ACCEPTED PRODUCT REQUIREMENT (2026-10-09). Applies to web, iOS and Android.

## Definition of done

Every user-facing feature must have equivalent capabilities on all three clients (web, iOS, Android), backed by one authenticated API and shared database. UI presentation can be native; behaviour, permissions, validation, state and error handling must be equivalent. Feature delivery must include migrations, API contract, server tests, web UI tests, iOS tests, Android tests, documentation and release compatibility checks. A missing client is a tracked blocker, not 'complete'. Existing features must be audited against this policy.

## Favourites

Authenticated user can favourite/unfavourite an advert and list favourites, consistently across all clients. Removing or withdrawing a listing must not expose it publicly; favourite list should handle unavailable listings gracefully.

API contract (proposed; implement before enabling client controls):
- GET /api/v1/me/favourites -> {data:[listing summaries]}
- PUT /api/v1/me/favourites/{listing_id} -> idempotent favourite
- DELETE /api/v1/me/favourites/{listing_id} -> idempotent unfavourite
- Unique (user_id, listing_id); ownership enforced through authenticated user context, never supplied user_id.

## Saved searches

User can create, rename, edit, run and delete saved searches, with filters (query, category, later price range, location and listing attributes as supported by server search). Each search has a name, filters, enabled state, frequency and delivery channels.

API contract (proposed):
- GET /api/v1/me/saved-searches
- POST /api/v1/me/saved-searches
- GET /api/v1/me/saved-searches/{id}
- PATCH /api/v1/me/saved-searches/{id}
- DELETE /api/v1/me/saved-searches/{id}
- GET /api/v1/me/saved-searches/{id}/results (current matching listings)
- GET/PATCH /api/v1/me/notification-preferences

Server-side matching must use exactly the same query/filter engine as interactive browsing. Search definitions and notification state are per-user. Add limits on number of saved searches and request rate.

## Notifications

Per-search channel preferences: email, SMS, WhatsApp, any combination or none. User chooses immediate, daily digest or weekly digest. Only newly matched active adverts since last successful delivery are included. Record (user_id, saved_search_id, listing_id, channel) delivery keys to prevent duplicate alerts; retry with bounded backoff, provider idempotency where possible, and dead-letter handling. A new match must not trigger on every polling cycle.

Email: verified account address or verified alternate email. SMS: verified E.164 mobile number with explicit opt-in. WhatsApp: separately opted-in WhatsApp-capable E.164 number and approved template messages in compliance with WhatsApp Business Platform policy and applicable law. Maintain channel-specific consent, consent timestamp/source, unsubscribe/STOP processing and suppression lists. Never send to unverified destinations. Opt-out and account deletion must stop queued sends. Avoid sensitive listing or account information in SMS/WhatsApp preview content.

Delivery architecture: scheduler/worker -> shared match evaluator -> dedupe/outbox -> provider adapters (email, SMS, WhatsApp). Provider credentials live in server secrets, never in client code or Git. Expose delivery history/status to the user. Record errors without leaking destination details or provider tokens.

## Schema design (proposed)

- favourites(user_id, listing_id, created_at), unique(user_id,listing_id)
- saved_searches(id,user_id,name,filters_json,enabled,frequency,created_at,updated_at)
- saved_search_channels(saved_search_id,channel,enabled)
- notification_destinations(id,user_id,channel,destination_encrypted,verified_at,consented_at,consent_source,revoked_at)
- saved_search_matches(saved_search_id,listing_id,first_seen_at), unique(saved_search_id,listing_id)
- notification_outbox(id,user_id,saved_search_id,listing_id,channel,status,attempts,next_attempt_at,provider_message_id,created_at,sent_at), unique(saved_search_id,listing_id,channel)
- notification_suppressions(user_id,channel,created_at,reason)

Use appropriate FK deletion behaviour and transactional outbox writes; never store plaintext provider credentials.

## Client parity matrix

| Capability | Web | iOS | Android |
|---|---|---|---|
| View/add/remove favourites | REQUIRED | REQUIRED | REQUIRED |
| Create/edit/delete/run saved search | REQUIRED | REQUIRED | REQUIRED |
| Select notification channels and frequency | REQUIRED | REQUIRED | REQUIRED |
| Verify/manage contact destinations and consent | REQUIRED | REQUIRED | REQUIRED |
| View delivery history and disable alerts | REQUIRED | REQUIRED | REQUIRED |
| Sign in, sign out and restore session | REQUIRED | REQUIRED | REQUIRED |

## Rollout and acceptance

1. Audit existing web/iOS/Android clients and record gaps. Do not imply Android exists unless verified.
2. Implement schema + API with authentication, validation, ownership, migrations and regression tests.
3. Implement web, iOS and Android UIs against the same API; no local-only favourites or searches.
4. Implement match evaluator and email delivery first; use isolated provider fakes in tests.
5. Integrate SMS and WhatsApp providers after provider credentials, approved templates, consent and opt-out workflows are configured.
6. Verify create/edit/delete on one client appears on the other two; verify deduplication, unsubscribe, retry, authorization and deleted-account handling.
7. Release only after three-client acceptance evidence; if a provider is unconfigured, label its delivery channel unavailable rather than claiming it works.

This policy persists across subsequent work and must be checked at every checkpoint and release.

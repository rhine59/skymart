# Web and iPhone client parity contract

## Non-negotiable principle

The PHP API, MariaDB database and server-side services are the single source of truth for SkyMart marketplace data. The web browser and iPhone app are two presentations of the same user account and marketplace, not separate stores. GitHub main is the source of truth for implementation.

## Current audit (9 October 2026)

| Capability | Web | iPhone | Parity gap |
|---|---|---|---|
| Public advert browsing | API exists; browser landing page is currently login | SwiftUI catalogue uses sample data | Build public browser catalogue and switch phone default to live API |
| Search/categories | API supports search and category | SwiftUI local filter | Both use API filters and identical category IDs/slugs |
| Advert detail/photos | API exists | Detail/gallery exists | Verify shared image URLs and fallback UI |
| Registration | Browser registration | No equivalent native registration | Add API registration and phone screen |
| Login/account | Browser session | Bearer/Keychain API client | Validate same user across devices |
| Sell advert | API supports creation; browser UI incomplete | Sell form/API upload exists | Complete web form; use draft/upload/publish workflow |
| Edit/withdraw/delete advert | API supports some operations; web UI incomplete | Native management UI incomplete | Add full lifecycle UI to both |
| Favourites | No server-backed favourites | Local-only in-memory state | Create per-user favourites API and migrate phone state |
| Account management | Basic browser pages | Native API client | Align profile, password, logout and deletion flows |
| Contact seller | Not complete | Button disabled | Design a safe shared contact flow |
| Changes on another device | Server data is shared, but no client refresh contract | Sample data persists until local changes | Fetch on launch, foreground, pull-to-refresh and after mutations |

## Behavioural contract

1. The API is authoritative for account data, advert status, prices, categories, photographs, favourites and ownership. Sample adverts may be used only in an explicit development/demo mode, visually labelled.
2. All writes use the API; browser sessions and native bearer tokens are authentication mechanisms only. Authorisation and validation remain server-side.
3. Both clients use the same listing identifiers and canonical API response shapes. Prices must use exact decimal/pence representations; timestamps use a documented UTC ISO 8601 format.
4. Refresh on launch, foreground return, explicit refresh and successful mutation. Optimistic state must be reconciled with server responses. A push/websocket layer is **not** needed for the first parity milestone; changes may appear after refresh.
5. Both clients show equivalent loading, empty, offline, validation and error states, with accessible controls and consistent terminology.
6. A feature is not considered **done** until it is supported in the API, both clients, and automated tests or documented manual cross-client verification. Exceptions must be marked explicitly.
7. Never deploy a new API contract that breaks an older supported phone version; evolve versioned endpoints compatibly.

## Implementation sequence

1. **Shared catalogue:** make the browser home a public marketplace; wire iPhone browse/search/detail to the live API with explicit demo-mode toggle. Add server and client smoke tests.
2. **Seller workflow:** draft → images → publish; implement create/edit/withdraw/delete and image management on both clients. Ensure failed uploads do not publish partial adverts.
3. **Account and favourites:** server-backed favourites, account registration, profile, password flows and session/token invalidation; complete both interfaces.
4. **Parity release gate:** automate API contract tests and perform a two-client acceptance matrix: create on phone, see on web; edit on web, see on phone; favourite on either, see on both; withdraw on either, disappears from public listings after refresh.

## Definition of sync

**Synced** means both interfaces read the same server-side state and show the same data after refresh. It does not imply instantaneous real-time push notifications. If real-time updates become a requirement, assess server-sent events or push notifications after basic parity works.

## Release checklist

- [ ] Shared public catalogue on web and phone, no unlabelled sample data
- [ ] Search/category parity
- [ ] Registration and sign-in on both
- [ ] Create/edit/withdraw/delete parity
- [ ] Photograph upload, ordering and removal parity
- [ ] Server-backed favourites parity
- [ ] Profile/password/account parity
- [ ] Cross-client refresh acceptance tests
- [ ] iPhone simulator/device build and tests
- [ ] Browser regression tests

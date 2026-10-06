# SkyMart API v1

SkyMart uses one versioned JSON API for native clients and future web integrations.

Base path: `/api/v1`

Current public origin: `https://skymart.granvillehouse.synology.me:8082`

> Operational status (6 October 2026): a live `/api/v1/health` 404 was traced to deployment drift—the running `skymart-web` container had an older Apache vhost without the current rewrite rules. The isolated harness passed on current `main`; rebuilding the live web container restored the loopback API health endpoint to HTTP 200 with `{"status":"ok","api":"v1"}`.

## Principles
- JSON request/response bodies.
- Stable resource IDs.
- Versioned URLs.
- Server remains authoritative for authentication, validation and permissions.
- List endpoints support filtering/pagination without changing the resource shape.
- No database details are exposed to clients.

## Public endpoints

### GET /api/v1/health
Returns service status.

### GET /api/v1/categories
Returns marketplace categories.

### GET /api/v1/listings
Query parameters:
- `q` search text
- `category` category slug
- `page` positive integer
- `per_page` 1-50

Response:
```json
{
  "data": [
    {
      "id": 42,
      "title": "Trig TT21 Mode S Transponder",
      "category": {"id": 4, "name": "Avionics", "slug": "avionics"},
      "price_gbp": 975,
      "location": "Lancashire",
      "description": "Compact Mode S transponder...",
      "status": "active",
      "seller": {"id": 8, "name": "Aero Seller"},
      "images": [],
      "created_at": "2026-09-27T12:00:00Z",
      "expires_at": null
    }
  ],
  "meta": {"page": 1, "per_page": 20, "total": 1}
}
```

### GET /api/v1/listings/{id}
Returns one active listing.

## Authenticated listing endpoints
These currently reuse the server-side SkyMart session. Unauthenticated requests return HTTP 401.

- `GET /api/v1/me/listings` — seller's adverts, including non-public states.
- `POST /api/v1/listings` — create and activate an advert.
- `PATCH /api/v1/listings/{id}` — replace editable fields on an advert owned by the authenticated seller.
- `DELETE /api/v1/listings/{id}` — withdraw an owned advert; the database row is retained.

Create/update JSON fields:
- `title` 3–160 characters
- `description` 10–10,000 characters
- `category` category slug
- `price_gbp` non-negative numeric price
- `location` 1–160 characters

Ownership failures deliberately return 404 rather than revealing another seller's private advert state.

## Mobile authentication
- `POST /api/v1/auth/login` accepts email, password and optional `device_name`; returns an opaque bearer token, expiry and user.
- `GET /api/v1/me` validates the bearer token and returns the user.
- `POST /api/v1/auth/logout` revokes the current bearer token.

Tokens are 256-bit random values, expire after 30 days, and only SHA-256 token hashes are stored in MariaDB. The iOS client stores the raw token in Keychain using a device-only accessibility class.

## Marketplace endpoints still planned
- `POST /api/v1/listings/{id}/favourite`
- `DELETE /api/v1/listings/{id}/favourite`
- `GET /api/v1/favourites`
- `POST /api/v1/listings/{id}/images`

Before the production iPhone app uses write endpoints, SkyMart will add a mobile-safe authentication mechanism. The current session-backed write API is suitable for same-origin integration and backend testing.

## Error shape
```json
{"error":{"code":"validation_error","message":"The request could not be validated.","fields":{}}}
```

## iOS boundary
The SwiftUI app consumes protocol `MarketplaceService`. `MockMarketplaceService` is used for simulator development; `APIMarketplaceService` implements this contract with URLSession.

## Listing photographs

Authenticated listing owners can manage photographs:

- `POST /api/v1/listings/{id}/images` with `Content-Type: image/jpeg` and the JPEG bytes as the request body.
- `DELETE /api/v1/listings/{id}/images/{image_id}`.

Each advert supports up to 10 images. Incoming files are limited to 12 MB and sensible dimensions, decoded and re-encoded by GD so uploaded metadata and original container content are not retained. SkyMart generates a maximum 2000 px display JPEG and 480 px thumbnail. Files use random storage keys outside the public document root and are served through controlled immutable media URLs.

Listing responses now populate `images` with ordered objects containing `id`, `url`, `thumbnail_url`, `width`, `height`, and `sort_order`.

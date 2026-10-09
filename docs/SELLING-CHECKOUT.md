# SkyMart listing workflow

1. Seller signs in, creates a draft and enters title, description, category, price, location, contact name, contact email, optional telephone, and 30/60/90-day duration.
2. Seller uploads up to 10 photographs (JPEG; server normalises and resizes), rearranges their order and can delete them. The first image is the cover photo.
3. Draft remains unpublished. Seller reviews the advert and chooses the duration/fee. Fees are configured by an administrator in `listing_fee_options`, not hardcoded in the client. Until fees and gateway are configured, the payment action must remain unavailable.
4. Server creates a checkout session with a supported provider (proposed Stripe Checkout). A verified signed webhook, **not** the browser redirect, records payment and activates the advert, setting `published_at` and `expires_at` to payment time plus chosen duration. Use provider event ID uniqueness and idempotent processing. Never collect card data on SkyMart.
5. Seller can edit active adverts. Renewal/relisting is a new payment or explicitly configured free renewal; it must never extend a listing merely because the browser says payment succeeded. An expired listing is excluded from browsing automatically by existing search filters.
6. Buyer sees public listing details and contacts seller via SkyMart's internal enquiry flow. Seller contact information must not be exposed publicly without explicit consent. Personal contact details should be used only for account notifications and authorised conversations.

## Release gates

- No fee amounts, Stripe keys or webhook secret are configured. **Do not activate fee-based listings** until all are supplied and payment/webhook tests pass.
- Existing live adverts predate the fee system; a rollout must explicitly grandfather or migrate them. Never retroactively charge or silently withdraw.
- Validate image ownership, file types, ordering and deletion, expiry and renewal, checkout replay protection, webhook signature, refund/dispute handling, and cross-platform API parity.

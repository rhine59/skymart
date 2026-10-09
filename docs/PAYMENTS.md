# SkyMart payment capabilities

## Launch configuration

Listings are free at launch. No checkout is shown or required for free listings. Payment credentials are not required to publish free adverts. Never collect or store card details in SkyMart.

## Supported payment methods when fees are enabled

- Stripe-hosted Checkout: credit/debit cards and Apple Pay when eligible. Register the relevant merchant payment domain where required. Use GBP, integer pence, and server-calculated listing fees.
- PayPal Checkout: separate PayPal account checkout using server-side Orders API and capture verification. Use GBP and server-calculated listing fees.
- Apple Pay is a wallet payment method through Stripe, not a third independent gateway. Display only on supported devices.

## Payment model

- Fees configured centrally per duration (30, 60, 90 days). A zero fee skips payment entirely and publishes or relists at no charge.
- Paid listing flow: draft -> checkout pending -> verified provider webhook or server-verified PayPal capture -> paid -> published. Never publish based solely on a client redirect or unverified browser callback.
- Record provider, provider checkout/order ID, amount in pence, currency, status, timestamps and unique provider event IDs. Idempotently process retries, duplicates, expired sessions and refunds.
- Validate ownership and fee amounts on the server. Never trust price or listing ID from a browser checkout response.
- Payment secrets only in NAS environment variables/secrets, never in Git or mobile apps. Separate sandbox and production credentials.
- Do not charge sellers or expose live checkout buttons until merchant accounts, keys, webhook verification and end-to-end payment tests are configured.

## Cross-platform parity

Web, iOS and Android use the same server-side listing fee and payment state API. Native apps may open a provider-hosted checkout. Review current Apple and Google platform policies before enabling in-app payments for listing services.

## Sources

- https://stripe.com/gb/payments/checkout
- https://support.stripe.com/questions/register-domains-for-payment-methods
- https://developer.paypal.com/checkout/integrate

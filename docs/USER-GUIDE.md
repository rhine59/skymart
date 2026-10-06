# SkyMart User Guide

## 1. Purpose and current scope

SkyMart is a marketplace for aeronautical equipment. Current categories are Aircraft, Engines, Propellers, Avionics, Instruments, Parts, Pilot Equipment and Miscellaneous.

This guide describes functions present in the current `main` source. Some functions are exposed through the JSON API/iPhone source before the browser UI has equivalent screens; those distinctions are called out explicitly.

## 2. Accounts

### Create an account on the web

Open SkyMart and choose **Create an account** from the login page. Supply the requested name, email and password details. Passwords are stored using PHP's current password hashing mechanism; SkyMart never needs to display or recover an existing password.

After registration, sign in with the email address and password.

### Sign in on the web

Enter email and password on the SkyMart login page. A successful login creates a server-side session and takes you to **My account**. Use **Log out** to end the browser session.

### Sign in on iPhone

The current SwiftUI client contains an Account screen. Enter the SkyMart email and password and tap **Sign in**. The server returns a device token after successful authentication. The app stores that token in iOS Keychain rather than storing the password.

Native tokens expire after 30 days. Logging out revokes the current token. Password changes/reset and account deactivation revoke all native device tokens.

> The iPhone source is implemented but is not yet documented as a released App Store/TestFlight build. A complete Xcode/device validation remains required.

## 3. Browsing and searching adverts

The marketplace API supports:

- browsing active, non-expired adverts;
- text search across title, description and location;
- filtering by category;
- paginated results;
- viewing a single active advert;
- seller name, location and photograph gallery data.

The SwiftUI marketplace source provides browse/search, category filtering, listing details and image display. Only adverts with status `active` and which have not expired are returned publicly.

## 4. Selling an item

The current iPhone Sell workflow requires the user to be signed in.

1. Open **Sell**.
2. Optionally select up to 10 photographs.
3. Enter an advert title.
4. Choose a category.
5. Enter the price in pounds.
6. Enter the location.
7. Add a description of at least 10 characters.
8. Tap **Publish advert**.

The server validates the advert independently of the phone. Current limits include a title of 3–160 characters, description of 10–10,000 characters, required location up to 160 characters, known category and non-negative price.

A newly created advert currently becomes `active` immediately, after which selected photographs are uploaded sequentially. This means a failed photo upload can leave an active advert with only some or none of its intended photographs. A future draft/upload/publish transaction is recommended before public production use.

## 5. Photographs

Each advert supports up to 10 JPEG images.

The server:

- limits each incoming JPEG to 12 MB;
- requires dimensions between 200×200 and 12000×12000 pixels;
- decodes and re-encodes the image;
- generates a display image with a maximum dimension of 2000 px;
- generates a thumbnail with a maximum dimension of 480 px;
- stores files outside the web document root;
- exposes them only through controlled media URLs.

The current iPhone source supports selecting, previewing and removing photos before publishing. Image reordering and per-image upload retry/resume are not yet implemented.

## 6. Managing your account

The native account API supports changing the user's name/phone, changing password and deactivating the account. The current iPhone Account screen exposes sign-in, sign-out, forgotten-password request and password change.

### Change password

Choose **Change password**, enter the current password, then a new password of at least 12 characters twice. A successful change revokes native tokens and requires authentication again.

### Forgotten password

Choose **Forgot password?**, enter the email address and request a reset. SkyMart deliberately returns the same acknowledgement whether or not the email exists. Reset links are valid for 30 minutes and are single-use.

Password-reset email depends on production mail transport and `SKYMART_PUBLIC_URL`/`SKYMART_MAIL_FROM` being correctly configured.

### Deactivate account

The API supports authenticated account deactivation after password confirmation. Deactivation changes account status and revokes native device tokens. The current iPhone UI does not yet expose this operation.

## 7. Advert lifecycle

Database states are:

- `draft` — retained for workflow support;
- `active` — publicly visible if not expired;
- `sold` — reserved lifecycle state;
- `withdrawn` — seller removed it from public sale;
- `expired` — reserved lifecycle state.

The API supports creating, editing and withdrawing adverts owned by the authenticated seller and listing all of that seller's adverts. Ownership failures are deliberately returned as not-found responses so another seller's private advert state is not disclosed.

The current iPhone UI does not yet expose every lifecycle operation.

## 8. Administrator guide

Users with `role=admin` and `status=active` can access the web administrator user console at `/admin/users.php`.

An administrator can:

- search users;
- view role/status;
- disable an active account;
- re-enable a disabled account;
- revoke all native device sessions for an account.

An administrator cannot disable their own account through the current console. Deactivated accounts are distinct from disabled accounts.

The API also exposes equivalent administrator user-list/status/device-revocation operations.

## 9. Current limitations

Before treating SkyMart as a public production marketplace, note:

- the iPhone source still needs a complete Xcode project/device build and end-to-end validation;
- browser marketplace listing management is not yet a complete user experience;
- favourites are not yet implemented server-side;
- listing image reordering/retry is pending;
- mail delivery must be configured for password resets;
- login/reset rate limiting is still required;
- listing creation should move to draft → image upload → publish to avoid partial publication;
- moderation/reporting and full audit logging are future work.

See [Feature Status](FEATURE-STATUS.md) for the maintained capability matrix.

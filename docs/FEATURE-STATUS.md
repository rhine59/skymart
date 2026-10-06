# Feature Status

Status terms:
- **Implemented + tested** — present on `main` and covered by the server harness or directly validated.
- **Implemented, integration pending** — source exists but full release/device/production integration is not yet proven.
- **Pending** — not implemented as a complete capability.

| Area | Status | Notes |
|---|---|---|
| PHP 8.4 / Apache container | Implemented + tested | Public document root only |
| MariaDB 11.4 | Implemented + tested | Private Compose network |
| Environment DB configuration | Implemented + tested | Production secrets remain external |
| Browser sessions / CSRF | Implemented + tested | Login, registration, logout/access checks |
| Password hashing | Implemented + tested | PHP password APIs |
| Versioned migrations | Implemented + tested | 001–005 currently present |
| API routing / health | Implemented + tested | `/api/v1` front controller |
| Categories / public listing search | Implemented + tested | API smoke covers categories/pagination validation |
| Listing create/update/withdraw | Implemented | API/service source present; broader E2E coverage should be added |
| Native bearer authentication | Implemented | 30-day opaque tokens, hashed at rest |
| Account lifecycle | Implemented | Profile/password/reset/deactivation API |
| Admin account management | Implemented | Web console + API |
| Listing photographs | Implemented | JPEG processing, max 10, display/thumb variants |
| Synology production override | Implemented, deployment pending | Requires real secrets/reverse proxy/mail/backup readiness |
| SwiftUI browse/search/detail | Implemented, integration pending | Source present |
| SwiftUI account/Keychain | Implemented, integration pending | Requires Xcode/device E2E validation |
| SwiftUI Sell + photo upload | Implemented, integration pending | Partial-publish risk remains |
| Complete Xcode project/release build | Pending | Next major client task |
| Browser marketplace management UI | Pending | Current browser UI is account foundation/admin |
| Favourites | Pending | API contract not implemented |
| Image reorder/retry/resume | Pending | |
| Rate limiting | Pending | Required before public launch |
| Moderation/reporting/audit | Pending | |
| Production mail transport | Pending | Required for password reset |
| Backup/restore drill | Pending | Required before public launch |

See [User Guide](USER-GUIDE.md), [Deployment Guide](DEPLOYMENT-GUIDE.md) and [Architecture Guide](ARCHITECTURE.md).

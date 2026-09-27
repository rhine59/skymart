# Feature status

| Area | Status | Notes |
|---|---|---|
| Environment-based DB configuration | Implemented | Phase 1 |
| Prepared login query | Implemented | Phase 1 |
| Server-side session authentication | Implemented | Phase 2 |
| CSRF protection | Implemented | Login, registration and logout |
| Secure registration | Implemented | Prepared insert + password_hash |
| Logout | Implemented | POST + CSRF |
| Database migrations | Implemented | Initial users/categories/listings schema |
| PHP/Apache container | Implemented — execution pending | PHP 8.4, Apache 2.4, public-only document root |
| MariaDB container | Implemented — execution pending | MariaDB 11.4 LTS |
| Synology production override | Implemented — execution pending | Environment secrets + trusted reverse proxy |
| Automated test harness | Implemented — execution pending | Dedicated skymart-test Compose project; PHP, isolation, schema, registration and auth |
| Marketplace listings UI | Not started | Phase 3 |
| Listing images | Not started | Phase 3 |
| Search/filter | Not started | Phase 3 |
| Favourites | Not started | Later |
| Moderation/reporting | Not started | Later |

## Account management UI

- iOS account screen: implemented on Phase 3 branch (login, logout, forgotten-password request, change password, Keychain session restore).
- Web administrator users console: implemented at `/admin/users.php`, guarded by active admin role and CSRF for state changes.
- Production iOS API origin: configuration pending; placeholder origin must be replaced before deployment.
- Password-reset mail transport: deployment configuration pending.

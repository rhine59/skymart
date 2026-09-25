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

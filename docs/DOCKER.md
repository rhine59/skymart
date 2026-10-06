# Docker development, test and production

The baseline is PHP 8.4 + Apache and MariaDB 11.4 LTS. Apache serves only `public/`; PHP application internals remain outside the web document root.

## Development

```sh
./scripts/rebuild.sh
```

The development site uses port 8080 by default. Set `SKYMART_PORT` to override it.

## Automated test

```sh
./scripts/test.sh
```

The isolated test override binds to localhost on port 18080 and uses temporary MariaDB storage. It verifies PHP 8.4+, PHP syntax, migrations, seed data, public-root isolation, registration and unauthenticated access control, then destroys the test stack.

## Operations

```sh
./scripts/status.sh
./scripts/logs.sh
./scripts/reset-db.sh
```

The reset command destroys the development database volume.

## Synology production

Use the production override:

```sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Set `SKYMART_DB_NAME`, `SKYMART_DB_USER`, `SKYMART_DB_PASSWORD` and `SKYMART_DB_ROOT_PASSWORD` in the Synology deployment environment. Do not commit them.

The production override binds the web service to loopback by default and enables trusted-proxy HTTPS detection for the Synology reverse proxy. MariaDB has no published host port. Configure the Synology reverse proxy for HTTPS, back up the database volume, and test image upgrades before production deployment.

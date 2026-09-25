# Docker development and test environment

## Services

The stack contains two containers:

- `web`: PHP 8.3 + Apache with mysqli.
- `db`: MariaDB 11.4 with a persistent Docker volume.

The normal development site is exposed on port 8080 by default. Set `SKYMART_PORT` to override it.

## Build and start

```sh
docker compose up -d --build
```

or use:

```sh
./scripts/rebuild.sh
```

The rebuild script stops the existing stack, pulls/rebuilds images without build cache, starts the services and waits for the health endpoint.

## Tests

```sh
./scripts/test.sh
```

Tests use the Compose test override, bind only to localhost on port 18080 and use a temporary MariaDB filesystem. The test stack is destroyed automatically.

The harness checks container startup/health, PHP syntax, database migrations and seed categories, secure registration, database persistence of the test user, and rejection of unauthenticated access to the private page.

## Reset development database

```sh
./scripts/reset-db.sh
```

This is destructive and removes the local Docker database volume.

## Operations

```sh
./scripts/status.sh
./scripts/logs.sh
```

## Synology

The same Compose stack is suitable as the starting point for Synology Container Manager. Production deployment should use separate strong secrets, no direct database port exposure, HTTPS through the Synology reverse proxy, backups for the MariaDB volume, and a pinned/tested image update process.

# GRG Docker Setup

This project is set up to run with Docker and PostgreSQL.

## Start

```bash
docker compose up --build
```

The Laravel app will be available at `http://localhost:8000`.

## What happens on boot

- The `app` container builds the frontend assets with Vite.
- The `postgres` container starts PostgreSQL 16.
- The Laravel container waits for PostgreSQL to be healthy.
- Migrations run automatically before the app server starts.

## Environment

The Docker setup expects these database settings:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=grg
DB_USERNAME=grg
DB_PASSWORD=grg_password
```

These defaults are already reflected in [`.env.example`](/Users/billy/dev/grg/.env.example) and [`.env`](/Users/billy/dev/grg/.env).

## Stop

```bash
docker compose down
```

To remove the PostgreSQL data volume too:

```bash
docker compose down -v
```

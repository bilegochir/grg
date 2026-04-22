# Gereg

Gereg is a multi-tenant visa agency workspace for managing applicants, visa cases, tasks, notes, attachments, and activity timelines.

## Current Scope

- Agency-scoped CRM data model
- Clients, visa cases, and tasks
- Notes, private attachments, and timelines
- Vue/Inertia workspace with Tailwind UI

## Run Locally

```bash
composer install
npm install
php artisan migrate
composer run dev
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Helpful Commands

```bash
php artisan test --compact
npm run build
vendor/bin/pint --dirty --format agent
```

## Product Directions

- Rich client profiles and intake workflows
- Case management by destination, status, and assignee
- Notes, documents, and audit-friendly timelines
- Team task coordination and follow-ups

## Tech Notes

- PHP backend with SQLite in local development
- Vue 3 + Inertia for the application shell
- Tailwind-based component styling

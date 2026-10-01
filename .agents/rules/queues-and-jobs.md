---
title: Queues, Jobs & Docker Deployment
globs: 'app/**,config/**,routes/**,tests/**'
---

# Queues, Jobs & Docker Deployment (ADR 0012)

Synchro processes slow or external work on Redis queues supervised by Laravel Horizon, and ships to production as a Docker Compose stack.

## 1. What Must Be Queued

- Emails and notifications (implement `ShouldQueue`, route channels with `viaQueues()`).
- Spreadsheet imports and any other long-running processing.
- PDF generation, SMS/WhatsApp alerts, and calls to third-party services.

## 2. How Jobs Are Written

- Jobs in `app/Jobs/` stay thin: they call a Single-Action class (`app/Actions/`) and record failures in `failed()`.
- Pick a named queue: `notifications` (short, retried with backoff), `imports` (long, `$tries = 1`), or `default`. Every queue must be listed under a supervisor in `config/horizon.php`.
- Respect the timeout chain: job `$timeout` < supervisor `timeout` < `REDIS_QUEUE_RETRY_AFTER`.
- Make jobs idempotent: claim work with a conditional update (e.g. `pending` → `processing`) so a duplicate delivery is a no-op.
- Dispatch side effects after commit (`DB::afterCommit`, `->afterCommit()`) so a rolled-back transaction sends nothing.

## 3. Operations

- `/horizon` is gated by `Permission::MonitorQueues` (never by role).
- The scheduler runs `horizon:snapshot`, `queue:prune-failed`, and `model:prune` (models use `MassPrunable`).
- Production: `docker-compose.yml` — `app` (FrankenPHP/Caddy on `127.0.0.1:${APP_PORT}`), `horizon`, `scheduler`, and optional `mysql`/`redis`/`phpmyadmin` profiles; the host's nginx (Hestia templates in `docker/hestia/`) owns the domain and HTTPS. Env template `.env.docker.example`. Locally, `composer dev` starts Horizon against a local Redis.

## 4. Testing

- Tests use `QUEUE_CONNECTION=sync`. Use `Queue::fake()` + `Queue::assertPushedOn()` for dispatching, and call `handle()` or the action directly for job behaviour.

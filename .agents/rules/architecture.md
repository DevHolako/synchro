---
title: Domain-Driven Architecture & CPS Pipeline Rules
globs: 'app/**'
---

# Domain-Driven Architecture & CPS Pipeline Rules

We Creatif utilizes a Domain-Driven Design (DDD) layout under `app/Domains/` organized around the Continuous Prototyping System (CPS) pipeline.

## 1. Domain Organization

Code is partitioned into distinct domain namespaces under `app/Domains/`:

- `Ai`: Multi-provider routing, dynamic pricing grid, prompt manager, live testing.
- `Brief`: Industry schemas, brief completeness scoring, AI refinement agent.
- `Sitemap`: Architecture builder, blueprint presets, tree hierarchy, AI sitemap agent.
- `Studio`: Code editing, preview canvas, cascading components, proposal staging, revisions.
- `Quality`: Automated audit rule runner, scoring metrics, auto-fix actions, export reports.
- `Export`: Static site ZIP compiler, public demo access, client preview tokens.
- `Audit`: Activity telemetry, payload diffing, security & AI execution tracking.
- `Projects`: Project lifecycle, pipeline progression, locks, and step states.
- `Security`: IP firewall, brute-force protection, audit logs, and 2FA authentication.

## 2. Action Pattern & State Transitions

- Business logic that mutates state belongs in dedicated Single-Action classes (e.g. `RunQualityAuditAction`, `GenerateIndexPageAction`).
- Controllers must remain thin, orchestrating Form Requests, Action execution, and Inertia/JSON responses.
- Enforce step writability using `EnsureStepWritable` middleware and model lock checks before allowing destructive writes.

## 3. Persistent Neutrality & Audit Trail

- Never store localized UI strings directly in database tables. Store standard keys/slugs and resolve localized labels at presentation time.
- All significant model mutations must trigger audit logging via the `LogsActivity` trait.

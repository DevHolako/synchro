---
title: Prohibit Raw fetch() in Favor of Inertia v3 HTTP & Wayfinder
globs: "resources/js/**"
# `paths` scopes this rule for Claude Code; `globs` for Antigravity.
paths:
  - "resources/js/**"
---

# Prohibit Raw `fetch()` in Favor of Inertia v3 HTTP & Wayfinder

Inertia.js v3 provides first-class standalone HTTP tools and Laravel Wayfinder generates typed route actions. Never write manual `fetch()` or `window.fetch()` calls in the frontend codebase.

## 1. Why Raw `fetch()` is Prohibited
- **Manual CSRF Plumbing**: Raw `fetch()` forces error-prone `document.querySelector('meta[name="csrf-token"]')` lookups.
- **Brittle Headers**: Requires manual `'Content-Type': 'application/json'` and `Accept` header assembly.
- **Untyped URLs**: Hardcoding strings like `'/rooms/store'` breaks route refactoring and lacks type safety.
- **Inertia v3 Native Support**: Inertia v3 removes the old v1/v2 restriction where all requests had to return full page visits.

## 2. Standard Replacement: `useHttp` + Wayfinder

For standalone requests (with reactive `processing`, `errors`, `cancel`, `progress` state), use Inertia v3's `useHttp` hook with a Wayfinder action URL:

```tsx
import { useHttp } from '@inertiajs/react';
import RoomStoreController from '@/actions/App/Http/Controllers/Web/Rooms/RoomStoreController';

const request = useHttp({ name: '' });

await request.post(RoomStoreController.url(), {
    onSuccess: (response) => { ... },
    onError: (errors) => { ... },
});
```

For page data, prefer Inertia visits and partial reloads (`router.reload({ only: [...] })`) over standalone requests.

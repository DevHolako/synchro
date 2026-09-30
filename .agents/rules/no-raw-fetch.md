---
title: Prohibit Raw fetch() in Favor of Inertia v3 HTTP & Wayfinder
globs: "resources/js/**"
---

# Prohibit Raw `fetch()` in Favor of Inertia v3 HTTP & Wayfinder

Inertia.js v3 provides first-class standalone HTTP tools and Laravel Wayfinder generates typed route actions. Never write manual `fetch()` or `window.fetch()` calls in the frontend codebase.

## 1. Why Raw `fetch()` is Prohibited
- **Manual CSRF Plumbing**: Raw `fetch()` forces error-prone `document.querySelector('meta[name="csrf-token"]')` lookups.
- **Brittle Headers**: Requires manual `'Content-Type': 'application/json'` and `Accept` header assembly.
- **Untyped URLs**: Hardcoding strings like `'/projects/brief/parse-raw'` breaks route refactoring and lacks type safety.
- **Inertia v3 Native Support**: Inertia v3 removes the old v1/v2 restriction where all requests had to return full page visits.

## 2. Standard Replacements

### Pattern A: Event Handlers & Imperative Async Requests (`@/lib/http`)
Use `@/lib/http` (backed by Inertia v3's XHR client with automatic CSRF management) combined with Wayfinder actions:

```tsx
import { clientHttp } from '@/lib/http';
import { parseRaw } from '@/actions/App/Http/Controllers/ProjectBriefController';

// Automatic CSRF token, automatic JSON headers, typed Wayfinder URL:
const response = await clientHttp.post<{ success: boolean; data: ParsedData }>(
    parseRaw.url(),
    { raw_input: text, model },
);
```

### Pattern B: Form-Style Reactive State (`useHttp` hook)
For requests that need reactive form-like state (`processing`, `errors`, `cancel`, `progress`):

```tsx
import { useHttp } from '@inertiajs/react';
import { parseRaw } from '@/actions/App/Http/Controllers/ProjectBriefController';

const form = useHttp({ raw_input: '', model: '' });

await form.post(parseRaw.url(), {
    onSuccess: (res) => { ... },
    onError: (errors) => { ... },
});
```

## 3. Enforcement
- Enforced by the custom ESLint rule: `local/no-raw-fetch`.
- Any PR or commit containing raw `fetch()` calls in `resources/js/**` will fail `npm run test`.

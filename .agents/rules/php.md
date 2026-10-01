---
title: PHP & Laravel Backend Coding Standards
globs: '**/*.php'
# `paths` scopes this rule for Claude Code; `globs` for Antigravity.
paths:
  - "**/*.php"
---

# PHP & Laravel Backend Coding Standards

All PHP code in Synchro runs on PHP 8.5 and Laravel 13. Follow these conventions across all models, actions, services, and controllers:

## 1. Syntax & Typing

- Always use curly braces for all control structures, even single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(private readonly ImportReferentialsAction $importReferentials) {}`.
- Never leave empty zero-parameter `__construct()` methods unless the constructor is `private`.
- Explicitly declare parameter types and return type hints on all methods and functions.
- Use `TitleCase` for enum cases: `Pending`, `Processing`, `Succeeded`, `Failed`.

## 2. Documentation & Type Shapes

- Prefer PHPDoc blocks over inline comments. Only use inline comments for complex domain algorithms.
- Use PHPStan-compatible array shape definitions in PHPDoc blocks:
    ```php
    /**
     * @param array{model: string, max_tokens: int, temperature: float} $options
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int}}
     */
    ```

## 3. Architecture & Conventions

- Use `php artisan make:` commands with `--no-interaction` when creating new files.
- Favor Eloquent API Resources and API versioning for REST endpoints.
- When generating URLs in PHP, use named routes and the `route()` helper.

## 4. Code Formatting

- Always run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure code style compliance.
- Do not run Pint in `--test` mode; let Pint format dirty files automatically.

# Rule: No Raw HTML in PHP Classes

**Glob**: `app/**/*.php`

## Rule Statement
Never embed raw HTML markup, inline HTML strings, or heredoc blocks (e.g. `<<<HTML`) directly inside PHP controllers, actions, services, or models.

## Rationale & Guidance
1. **Separation of Concerns**: HTML structure, scripts, and layouts belong in `resources/views/`.
2. **Safety**: Inline heredocs in PHP cause accidental variable interpolation bugs (e.g. `${var}` in JavaScript gets parsed as PHP `$var`), breaks syntax highlighting, and hinders linter analysis.
3. **Convention**: Always extract HTML into dedicated Blade templates (`resources/views/...`) and render via `view('view.name', [...])` or `response()->view('view.name', [...])`.

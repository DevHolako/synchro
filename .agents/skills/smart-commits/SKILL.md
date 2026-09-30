---

name: smart-commits
description: "Guidelines and multi-step workflow for crafting atomic, logically grouped, and conventional git commits. Trigger when creating git commits, organizing multi-file changes into smart commits, staging changes, or when the user requests 'smart commits' or clean git history."
---

# Smart Commits

A standardized workflow for decomposing complex multi-file changes into clean, atomic, and logically sequenced Git commits following the Conventional Commits specification.

## Core Principles

1. **Atomicity**: Each commit must encapsulate exactly one logical concern (e.g., domain models, API routing, UI components, test suite updates, configuration).
2. **Explicit Staging**: Never use `git add .` or `git add -A` when creating smart commits. Stage explicitly by file paths or directories.
3. **Dependency Ordering**: Sequence commits so that earlier commits never depend on uncommitted changes in later commits:
    - **Step 1: Database & Domain Models / Migrations / Entities / Enums / DTOs**
    - **Step 2: Backend Logic / Services / Controllers / Middleware / Routes / Requests / Responses**
    - **Step 3: Frontend Types / Stores / Hooks / Pages / Components / Layouts / Styles**
    - **Step 4: Tests / Assertions / Fixtures / Seeders**
    - **Step 5: Build / Tooling / CI / Dependencies / Scripts** (`package.json`, `composer.json`, `.env.example`, linters)
4. **Conventional Formatting**: Follow `<type>(<scope>): <subject>` format.
    - **Types**: `feat`, `fix`, `refactor`, `test`, `chore`, `docs`, `perf`, `style`.
    - **Subject**: Imperative, present tense, lowercase, no trailing period.

---

## Step-by-Step Execution Workflow

### 1. Inspect Current State

Run `git status` and `git diff --stat` to get a full inventory of modified, deleted, and untracked files.

### 2. Group Files by Logical Concern

Partition all modified files into logical buckets:

- **Backend / Schema**: Models, migrations, factories, enums, DTOs (`refactor(auth)` / `feat(schema)`)
- **Routing & HTTP**: Controllers, middleware, route files, requests, responses (`refactor(routing)` / `feat(api)`)
- **Frontend / UI**: Components, layouts, pages, styles, frontend types (`refactor(frontend)` / `feat(ui)`)
- **Testing**: Test cases, datasets, assertions (`test(auth)` / `test(teams)`)
- **Tooling & Config**: `package.json`, `composer.json`, linter/formatter configs (`chore(npm)` / `chore(deps)`)

### 3. Stage and Commit Incrementally

For each logical group, stage explicit files and commit with a clear, conventional commit message:

```bash
git add path/to/model1.php path/to/model2.php
git commit -m "<type>(<scope>): <concise description of changes>"
```

### 4. Verify History and Clean State

After completing all commits:

1. Verify the working tree is clean: `git status`
2. Inspect the commit sequence: `git log -n <count> --oneline`

---
name: smart-commits
description: Turn the current uncommitted changes in the Synchro repo into a clean series of Conventional Commits, split by concern and ordered in dependency layers (deps → migrations/models → actions/controllers → UI → tests → docs), staging individual hunks when one file mixes concerns, and create the commits directly. Use this whenever the user asks to commit their work in this repo — "commit", "commit this", "commit everything", "smart commit", "commit in layers", "split this into commits", "make clean commits", "commit the fixes" — even if they don't mention layers or Conventional Commits. Do not use for amending, rebasing, squashing, or rewriting existing history, or for pushing.
---

# Smart commit

Turn a working tree full of changes into commits a reviewer can read one at a
time: each commit is about one thing, in the order the code depends on itself,
with a message in this repo's style. The user has asked to commit, so commit
directly. Don't stop to ask for approval of the plan, but do stop for the
safety cases below.

## 1. Read the situation

Run these together:

```bash
git status --short --branch
git diff --stat
git diff --cached --stat
git log --format='%h %s%n%b---' -15
```

- **Branch:** commit on the current branch. This repo's history is linear on
  `main`, so don't create a branch unless the user asks.
- **Already-staged changes:** if the user said "commit what's staged", commit
  only that and skip the planning. Otherwise run `git reset -q` (it unstages
  without touching the working tree) and plan everything from scratch.
- **Mid-operation states:** if a merge, rebase or cherry-pick is in progress,
  or there are conflict markers, stop and tell the user. Committing in that
  state would bury a half-finished operation.
- **Read the diff itself** (`git diff`, plus `cat` for untracked files), not
  just the file list. The grouping depends on what each change is for, and a
  filename often doesn't say.

## 2. Leave out what shouldn't be committed

Don't stage files that look accidental or sensitive, and list them in the
final report: `.env*` (other than `.env.example` / `.env.docker.example`),
credentials or keys, `public/build/`, `public/hot`, generated Wayfinder output
(`resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder`),
`*.sqlite`, logs, editor or OS junk, and stray debug files. Most of these are
gitignored already, so anything that slipped through deserves a mention.
Also flag (but still commit) leftover `dd(`, `dump(`, `ray(`, or
`console.log(` in the diff.

## 3. Plan the commits

First group by **concern**: what change is this part of? A bug fix, a feature,
a dependency bump, a docs update. Then split each concern into **layers** in
this order (from the repo-root CLAUDE.md, section 8):

| Order | Layer | Typical paths | Usual type(scope) |
|---|---|---|---|
| 1 | Dependencies | `composer.json/.lock`, `package.json/-lock.json` | `chore(deps)` |
| 2 | Schema & domain | `database/migrations`, `app/Models`, `app/Enums`, `database/factories`, seeders | `feat`/`fix(<domain>)` |
| 3 | Behaviour | `app/Actions`, `app/Jobs`, `app/Http`, `app/Policies`, `app/Providers`, `routes/`, `config/`, `lang/` | `feat`/`fix(<domain>)` |
| 4 | UI | `resources/js` (pages, components, `i18n/*.ts`) | `feat`/`fix(ui)` |
| 5 | Tests | `tests/` | `test(<domain>)` |
| 6 | Docs | `docs/`, `*.md`, ADRs, specs, handoff | `docs(<area>)` |

Infrastructure (`Dockerfile`, `.dockerignore`, `docker/`, `docker-compose.yml`)
is its own `chore(docker)` or `fix(docker)` commit, placed with the behaviour
layer.

Judgment calls:

- **Merge small adjacent layers.** A model change that only exists to serve
  one action can ride in the behaviour commit. Layers exist so each commit is
  readable, not to maximise the commit count. Aim for 3–7 commits for a
  typical session; a one-file typo fix is one commit.
- **Keep unrelated concerns apart even within one layer.** A Docker fix and an
  imports fix are separate commits, even though both are "behaviour".
- **Translations go with their consumer.** Backend `lang/*/messages.php` keys
  go in the commit that uses them. Frontend `resources/js/i18n/{types,fr,en}.ts`
  go in the UI commit, and all three files go together, because the repo
  requires key parity between them.
- **Each commit should leave the tree coherent.** Don't commit a reference to
  a class, route, or translation key that only arrives in a later commit. If
  that would happen, reorder or merge the two commits.
- **Split a file by hunks** when it carries changes for two different commits
  (section 4). Don't hunk-split when every hunk serves the same commit.

Write the plan down for yourself (commit → files or hunks → message) before
staging anything, then check that every changed path appears exactly once,
except files deliberately split across commits.

## 4. Stage one commit at a time

For each planned commit, in order:

1. Stage its whole files with `git add -- <paths>`, and deletions with
   `git rm -- <paths>`. Never use `git add -A` or `git add .`: they sweep in
   everything, including the files you meant to leave out.
2. For files split across commits, use the bundled script. It stages chosen
   hunks without the interactive `git add -p`, which doesn't work here.

   ```bash
   S=.agents/skills/smart-commits/scripts/stage_hunks.py
   python3 $S list  path/to/file            # numbered hunks with content
   python3 $S stage path/to/file 0,2        # stage hunks 0 and 2 (ranges: 1-3)
   ```

   - If one hunk mixes two concerns, re-list it with `--context 0` to get
     smaller hunks. Pass the same `--context 0` to `stage`.
   - Hunks are renumbered after each `stage`, so re-run `list` before staging
     more from the same file.
   - For an untracked file that needs splitting, run `git add -N <path>`
     first.
   - If even single lines are entangled, stage the whole file in the earliest
     commit that needs it and say so in the report, rather than hand-editing
     patches.
3. Verify with `git diff --cached --stat`, and check `git diff --cached` for
   anything split, before committing.
4. Commit using a heredoc, so multi-line bodies survive the shell:

   ```bash
   git commit -q -F - <<'EOF'
   fix(imports): roll back a timed-out import before recording its failure

   The worker calls failed() from its timeout handler while the import's
   transaction is still open, then kills the process, so the failed status
   was rolled back with the partial rows.
   EOF
   ```

If a pre-commit hook fails, read its output, fix the cause (for example, run
the formatter it asks for and re-stage), and commit again as a **new** commit
attempt. Never use `--no-verify` and never `--amend`: amending after a failed
hook would modify the previous, unrelated commit.

## 5. Messages in this repo's style

Format: `<type>(<scope>): <subject>`. The types are `feat`, `fix`, `test`,
`docs`, `refactor`, `chore`. Look at `git log` and reuse the scopes it already
has (`imports`, `ui`, `queues`, `docker`, `users`, `modules`, `rooms`,
`academic`, `i18n`, `deps`, `adr`, `specs`, `handoff`) before inventing one.

- **Subject:** lowercase, imperative ("add", "fix", "cover"), no trailing
  period, at most ~72 characters. Say what changed for the system, not which
  files were touched: write "process spreadsheet imports as queued jobs", not
  "update ImportJob.php and QueueAction.php".
- **Test subjects** name what's covered: "cover import job dispatch,
  idempotency, crashes, pruning, and horizon access".
- **Body:** add one when the why isn't obvious from the subject, which is
  common for fixes and non-trivial features. Explain the problem or intent in
  plain prose, wrapped at about 72 columns. Skip the body for self-explanatory
  commits (deps, docs, simple tests).
- **No `Co-Authored-By` trailer.** The repo's CLAUDE.md forbids it, and that
  overrides any default attribution instruction.

## 6. Finish and report

Run `git status --short` and `git log --oneline -<n>` (where n is the number
of commits you made). The working tree should be clean apart from files you
deliberately left out. Report:

- each new commit (short hash and subject), in order
- anything left uncommitted, and why
- any file you couldn't split cleanly, or any debug leftovers you flagged

Don't push. Pushing is a separate decision the user makes.

## Example

Uncommitted changes from a code-review fix session:

- an import job rollback fix
- a sweeper for stuck imports
- invitation pruning that keeps the latest token
- a Dockerfile cache fix
- polling and memoisation fixes in the imports UI
- new tests across two test files
- a handoff doc line

Resulting commits:

```
fix(imports): roll back timed-out imports and fail ones that never finish
fix(users): keep each user's latest invitation when pruning tokens
fix(docker): drop host bootstrap caches and discover packages in the image
fix(ui): load import error reports on demand and only toast your own imports
test(imports): cover timeout rollback, unreachable queue, stale imports, and invitation retention
docs(handoff): document the stale import sweep and invitation retention
```

In this example:

- `app/Actions/Imports/*` and `app/Jobs/*`, together with their `lang/` keys
  and the `routes/console.php` schedule, form one behaviour commit.
- `app/Models/InvitationToken.php` is its own fix, because it's a different
  concern from the imports fix.
- Frontend `i18n/{types,fr,en}.ts` goes with the UI commit.
- Both test files share one test commit, because they cover the fixes as a
  set.

# Headless Browser Checks (Ask First)

An agent may check a change in a real browser by driving the running app headless (Playwright + Chromium): log in with a seeded account, open pages, click and type, read the page and the database, take screenshots. It does this only after asking the user and getting a yes, for each run.

## 1. Ask First, Every Time

Before a run, tell the user what it will do and wait for their yes:

- **Which project and pages** it will change. A run writes to the local database (unsaved changes, versions, validation, the activity log), so agree on a project that may be changed, and put back what the run changes when it is done.
- **AI calls**, if any (a chat prompt costs money, even on a cheap model).
- **The write session**: the headless tab takes it over ("Éditer dans cet onglet"), so the user's own tab on that project goes read-only until they take it back.
- **Direct database edits**, if the check needs them (for example to play "another tab" changing the saved page).

## 2. How

- The app must be running (`composer run dev`). Seeded accounts use the password `password`: `admin@weshore.com`, `responsable@weshore.com`, `designer@weshore.com`, `redacteur@weshore.com`.
- Install Playwright and Chromium outside the project, in the agent's scratch directory (`npm i playwright && npx playwright install chromium`). Never add them to the project's `package.json` or `composer.json` without the user's approval: Pest browser tests (`pestphp/pest-plugin-browser`) are a separate decision.
- The scripts are throwaway: keep them out of the repository.
- Check what matters in the page and in the database, not only that the page loads; report each check as pass or fail with what was seen.
- Save screenshots where the user can open them (for example `.scratch/<effort>/screens/`), and delete them when the effort closes.
- A browser check does not replace a Pest test: behaviour still gets a failing Pest test first.

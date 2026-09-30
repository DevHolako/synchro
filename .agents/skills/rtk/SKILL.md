---
name: rtk
description: Use RTK (Rust Token Killer) CLI proxy to filter and compress shell command outputs, reducing context tokens by 60-90% on git, cargo, npm, test, and shell commands. Invoke when optimizing token usage, inspecting token savings (rtk gain), querying command history (rtk discover), or running commands through the RTK proxy.
---

# RTK (Rust Token Killer)

RTK is a high-performance CLI proxy that intercepts shell commands and compresses their output before reaching the AI context window, saving 60-90% tokens while preserving all critical signals, errors, and exit codes.

## How It Works

Prefix shell commands with `rtk`:
```bash
rtk git status
rtk git diff
rtk git log -n 10
rtk npm test
rtk composer test
rtk cargo test
rtk ls -la
```

Keep the prefix inside command chains:
```bash
rtk git add . && rtk git commit -m "commit message"
```

Commands RTK has no filter for run through as-is, so the prefix is always safe.

## Output Condensation

Command output is condensed to save tokens, dropping costly noise while preserving all essential signals and errors. Treat it as the complete result: run commands normally, and batch related commands into one call to avoid extra turns.

Truncated results state their recovery path in their own output. Re-run a command as `rtk proxy <cmd>` only when its result is unusable: empty when output was clearly expected, contradicting its exit code, or garbled.

## Common RTK Commands

- `rtk gain`: Show token savings and analytics across past commands
- `rtk gain --history`: View detailed savings history per command
- `rtk proxy <cmd>`: Run a command unfiltered while still tracking it
- `RTK_DISABLED=1 <cmd>`: Bypass RTK completely for a single command
- `rtk discover`: Analyze shell history for commands that could have been compressed
- `rtk init --agent antigravity`: Reinstall Antigravity rules and configuration
- `rtk init -g --gemini`: Reinstall Gemini CLI hook & instructions

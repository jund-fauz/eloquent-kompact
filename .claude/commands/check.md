---
description: Check the current state read-only — report findings, no changes, no release
---

Check the current state of the code and report findings. Read-only: do NOT modify `src/`, `tests/`, or docs, and do NOT commit, tag, or release.

## Rules

- Re-read ALL involved files directly from disk — including line numbers. Do not use reads or memory from a previous turn; findings must come from the fresh file contents.
- A trailing newline at the end of a file does not need to be checked.
- Do not modify `src/`; report findings and wait for the user's decision before implementing.

## Steps

1. `git status -sb` and `git log --oneline -10` — understand the current changes.
2. Re-read the involved source files from disk and analyze them.
3. Run tests when useful (e.g. `composer test`, or a temporary probe).
4. Report: what is correct, what is wrong or risky, with a `call → actual → expected` table where helpful.

Report findings only. If there is a critical bug, say so clearly and stop.

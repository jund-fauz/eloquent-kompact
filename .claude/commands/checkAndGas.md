---
description: Review the requested change, implement it, then run the project release procedure
argument-hint: "[patch|minor|major]"
---

Review and implement the change requested in this conversation, then ship it using the project release procedure. Bump: `$ARGUMENTS` (default: determine it from the change).

## 1. Check the current state

- Follow the checking rules in `CLAUDE.md`: re-read all involved files directly from disk, including line numbers. Do not rely on reads or memory from a previous turn.
- Run `git status -sb` and `git log --oneline -10` to understand the current worktree and recent history.
- Inspect the requested change and relevant source, tests, and documentation. Keep unrelated existing changes out of scope and out of commits.
- A trailing newline at the end of a file does not need to be checked.

## 2. Review before implementing

- Analyze the current behavior and report relevant findings internally before changing code. Use a `call → actual → expected` table when it clarifies a problem.
- If the request has a clear, agreed implementation scope, proceed to implement that scope after the review. Do not expand it to unrelated findings.
- If the requested implementation is ambiguous or requires a decision the user has not made, stop after reporting the review findings and ask for that decision.
- **STOP on a critical bug** (such as a fatal error, data corruption, or a regression that breaks a core feature). Report it and wait for the user's decision; do not implement further or begin release steps.

## 3. Implement and verify

- Apply the agreed change to `src/` and update or add tests in `tests/`.
- For new functions or methods, add PHPDoc (params and return) and a return type, usually `: mixed` when unclear. Do this without confirmation.
- Run `composer test`; fix relevant failures and rerun until green.
- Update `README.md` (EN) and `README.id.md` (ID) if behavior changed.

## 4. Release

Follow `.claude/commands/release.md` from its versioning step onward. Determine the semver bump (breaking → major, feature → minor, fix → patch), update `CHANGELOG.md` and compare links, commit each change separately with Conventional Commits (CHANGELOG last), tag the release, push the branch then tag separately, create the GitHub release from the changelog notes, and verify the version appears on Packagist.

Do not include unrelated pre-existing worktree changes in commits, tags, or the release.

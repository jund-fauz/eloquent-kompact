---
description: Implement the agreed change and ship it (tests, CHANGELOG, README, tag, release)
argument-hint: "[patch|minor|major]"
---

Implement the change we just agreed on, then run the full release procedure. Bump: `$ARGUMENTS` (default: determine it from the change).

## 1. Implement

- Apply the agreed code change to `src/`.
- For new functions/methods, add PHPDoc (params + return) and a return type (usually `: mixed` when unclear). Do this without confirmation.

## 2. Tests

- Add or adjust tests in `tests/`, then run `composer test` until green.

## 3. Release

Follow `.claude/commands/release.md` from step 3 onward:

- Determine the new version per semver (breaking → major; feature → minor; fix → patch).
- Update `CHANGELOG.md`: move `[Unreleased]` to the new version heading, correct sections (`Added`/`Changed`/`Deprecated`/`Removed`/`Fixed`/`Documentation`/`Tests`), and update the compare links in the footer.
- Update `README.md` (EN) **and** `README.id.md` (ID) if behavior changed.
- Commit per Conventional Commits (one commit per change, CHANGELOG last), `git tag vX.Y.Z`, push branch then tag separately.
- `gh release create vX.Y.Z --title vX.Y.Z --notes-file notes.md` (notes from the CHANGELOG section).
- Verify Packagist picks up the new version.

> **STOP on a critical bug.** If a critical bug appears, stop before tagging/releasing and report it to the user.

---
description: Release a new eloquent-kompact version per semver (adjust tests, update CHANGELOG & README, tag, release)
argument-hint: "[patch|minor|major]"
---

Run the release procedure for `jund-fauz/eloquent-kompact`. Bump: `$ARGUMENTS` (default: determine it yourself from the changes since the last release).

## 1. Check the state

- Follow the checking rules in `CLAUDE.md`: re-read all involved files directly from disk (not from a previous turn's memory), and do not modify `src/` before the user decides.
- `git status -sb` and `git log --oneline -10` — understand the unreleased changes.
- `git fetch origin` then `git log v<last-version>..HEAD --oneline` to see changes since the last tag (read the last tag from `git tag --sort=-v:refname | head` or `gh release list --repo jund-fauz/eloquent-kompact`).

## 2. Tests

- Run `composer test`.
- If any test fails or needs adjusting (new behavior, new cases), fix the tests in `tests/` and re-run until green.
- Make sure every new behavior has a test (see the `### Tests` section pattern in CHANGELOG).
- For new functions/methods in `src/` that lack a PHPDoc, add their PHPDoc (params + return). Do this without confirmation.
- If a new function is missing a return type, add it (usually `: mixed` when unclear). Do this without confirmation.

> **STOP on a critical bug.** If you find a critical bug (e.g. fatal error, data corruption, fundamentally wrong behavior, a regression that breaks a core feature), STOP the release procedure here. Do not continue to version bump, CHANGELOG edit, tag, or release. Report the bug to the user and wait for a decision — the release may continue only after the bug is fixed (or the user explicitly decides to proceed).

## 3. Determine the new version (semver)

- Breaking change → major; new feature → minor; fix only → patch.
- Write the version without the `v` prefix for CHANGELOG (e.g. `1.20.0`), and with the `v` prefix for the git tag (`v1.20.0`).

## 4. Update CHANGELOG.md

- Move the contents of `## [Unreleased]` (at the very top) to a new heading `## [X.Y.Z] - YYYY-MM-DD` (today's date).
- Make sure the sections are correct: `Added` / `Changed` / `Deprecated` / `Removed` / `Fixed` / `Documentation`, then `Tests`.
- Leave `## [Unreleased]` in place (empty) above the new version heading.

**Compare links in the footer** (bottom of CHANGELOG, pattern `[version]: https://github.com/jund-fauz/eloquent-kompact/compare/...`):

- Update `[Unreleased]` to point from the new version: `compare/v1.19.0...HEAD` → `compare/v1.20.0...HEAD`.
- Add a new link line for the release, right below `[Unreleased]`: `[1.20.0]: https://github.com/jund-fauz/eloquent-kompact/compare/v1.19.0...v1.20.0`.
- Leave the older version links in place.

## 5. Update README if needed

- If a feature/API changed behavior, sync `README.md` (EN) **and** `README.id.md` (ID).
- If nothing needs changing, skip.

## 6. Commit, tag, push

Use [Conventional Commits](https://www.conventionalcommits.org/), one commit per change, not a single `Release` commit. Order: commit code/feature/fix first, then docs, and commit CHANGELOG **last** — the tag points to that CHANGELOG commit.

Commit message patterns:

- `feat: <description>` — new feature (e.g. `feat: grep() on AdditionalQueries`)
- `fix: <description>` — bug fix
- `chore: <description>` — non-functional change (e.g. `chore: mark if_not_null() as experimental`)
- `docs(readme): <description>` — change to README.md / README.id.md
- `docs(changelog): add 1.20.0 entry` — the last commit that adds the new version entry in CHANGELOG.md

Example commit order for the `v1.19.0` release:

```
32dc96c feat: grep() on AdditionalQueries
5b7d2ee feat: ALL_NULLABLE constant, deprecate Payload
065986a chore: mark if_not_null() as experimental
56be58c docs(readme): …
c6b6988 docs(changelog): add 1.19.0 entry   ← tag v1.19.0 points here
```

Steps:

- Commit each change separately with the messages above (`git add <file>` then `git commit -m "…"`), not a single `git add -A`.
- After the last CHANGELOG commit: `git tag v1.20.0` on that HEAD.
- Push the branch first, then the tag, separately: `git push origin main && git push origin v1.20.0` (not `--tags` in one go).

## 7. Release on GitHub

- Extract the new version's section from CHANGELOG (from `## [1.20.0]` up to, but not including, `## [1.19.0]`, without its heading) as the release notes, e.g.:

  ```bash
  awk '/^## \[1.20.0\]/{f=1;next} /^## \[1.19.0\]/{f=0} f' CHANGELOG.md | sed '1{/^$/d}' > notes.md
  ```

- `gh release create v1.20.0 --title v1.20.0 --notes-file notes.md` (use the CHANGELOG notes, not `--generate-notes`).

## 8. Verify Packagist (auto-update from the tag)

- Check the version appears on Packagist: `Invoke-RestMethod https://repo.packagist.org/p2/jund-fauz/eloquent-kompact.json` — make sure the new version is there.
- If it hasn't appeared after a few minutes, check the GitHub → Packagist webhook and redeliver the failed delivery if needed.

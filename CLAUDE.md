# CLAUDE.md

Working guide for the `jund-fauz/eloquent-kompact` project (a Laravel/Eloquent package, PHP 8.3+, Laravel 12|13).

## Versioning (semver)

- Stable since `1.0.0`. Breaking change → major (`2.0.0`), new feature → minor (`1.x.0`), fix → patch (`1.x.y`).
- Classes/methods marked `@internal` or `@experimental` may change in any release (not breaking).
- Extending classes or overriding methods marked `@final` is not covered by the backward-compatibility promise (callers remain covered, overrides are not).
- The version is NOT written in `composer.json` — Packagist reads it from the git tag. So never bump the version in composer.json.

## Coding conventions

- Every method in `src/` must have a PHPDoc docblock (`@param` and `@return`, except `__construct` which omits `@return`).

## CHANGELOG conventions

- [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format, heading `## [X.Y.Z] - YYYY-MM-DD` (no `v` prefix).
- Sections used: `### Added`, `### Changed`, `### Deprecated`, `### Removed`, `### Fixed`, `### Documentation`, plus `### Tests` (a section specific to this project).
- Unreleased changes go under `## [Unreleased]` at the very top.

## Key locations

- Version & changelog: `CHANGELOG.md` (the `[Unreleased]` part at the top)
- Package config: `composer.json` (no `version` field)
- Tests: `composer test` (runs PHPUnit); CI in `.github/workflows/tests.yml`
- Docs: `README.md` (EN) and `README.id.md` (ID) — keep both in sync
- Source: `src/`, tests: `tests/`

## Release

To release a new version, use the `/release` slash command (see `.claude/commands/release.md`). Do not re-derive the release procedure from `CHANGELOG.md`.

## When checking

- A trailing newline at the end of a file does not need to be checked.
- Re-read ALL involved files directly from disk on every check — including line numbers. Do not use reads or memory from a previous turn; findings must come from the fresh file contents.
- During a "check", do not modify the logic in `src/` yet; report findings and wait for the user's decision before implementing.
- Exceptions, which are Claude's job and are done directly, even during a check (list them in the report):
  - Docblocks: write or update the PHPDoc of new or changed functions/methods. Return types: add a missing one or fix a wrong one (`: mixed` when unclear).
  - Tests in `tests/` for new or changed behavior, `README.md` + `README.id.md`, and the `## [Unreleased]` entry in `CHANGELOG.md`. Do not report these as "not done yet" for the user. A test that fails because of a bug in `src/` stays failing; report the bug.

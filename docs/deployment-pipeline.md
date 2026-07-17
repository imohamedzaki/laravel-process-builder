# Deployment pipeline: releasing to Packagist

This project is distributed via [Packagist](https://packagist.org/packages/mohamedzaki/laravel-process-builder) as `mohamedzaki/laravel-process-builder`. Packagist does not host code itself — it tracks the GitHub repository and re-reads it whenever a webhook tells it something changed. There is no separate "upload" or "publish" step: **pushing a git tag to GitHub *is* the release.**

This document is the exact, repeatable sequence for cutting a release. Follow it in order.

## Table of contents

1. [One-time setup: linking the GitHub webhook](#one-time-setup-linking-the-github-webhook)
2. [Versioning policy](#versioning-policy)
3. [Release checklist](#release-checklist)
4. [Step-by-step release](#step-by-step-release)
5. [Verifying the release landed on Packagist](#verifying-the-release-landed-on-packagist)
6. [Hotfix releases](#hotfix-releases)
7. [Rolling back a bad release](#rolling-back-a-bad-release)
8. [Troubleshooting](#troubleshooting)

## One-time setup: linking the GitHub webhook

This only needs to be done once per package (already done for this repo, kept here for reference if the package is ever re-created or transferred):

1. On [packagist.org](https://packagist.org), submit the repository URL (`https://github.com/imohamedzaki/laravel-process-builder`) under **Submit**, authenticating with GitHub OAuth.
2. Packagist offers to auto-install the webhook during that flow — accept it. This creates a webhook under the GitHub repo's **Settings → Webhooks** pointing at `https://packagist.org/api/github?username=<packagist-username>`, firing on `push`.
3. Confirm the webhook exists: GitHub repo → **Settings → Webhooks** → there should be one entry for `packagist.org` with a green checkmark on its most recent delivery.

If the webhook is ever missing or broken, releases can still be triggered manually — see [Troubleshooting](#troubleshooting).

## Versioning policy

This package follows [Semantic Versioning](https://semver.org/):

- **MAJOR** (`1.0.0` → `2.0.0`): breaking changes — removed/renamed public classes or methods, changed `ProcessDefinition` schema in a way that isn't auto-migrated, removed config keys, changed HTTP response shapes consumers may depend on.
- **MINOR** (`0.2.0` → `0.3.0`): backwards-compatible new features — new node types, new API endpoints, new config options with safe defaults, schema additions that old process definitions auto-migrate into.
- **PATCH** (`0.2.0` → `0.2.1`): bug fixes and internal changes with no public API or schema impact — CI fixes, docs, dependency constraint widening, `dist/` rebuilds.

While the package is pre-1.0 (`0.x.y`), treat MINOR bumps as the practical breaking-change boundary and PATCH as safe/additive, per SemVer's pre-1.0 convention — this repo has been doing that since `0.1.0`.

Git tags are the source of truth for the version. `composer.json` intentionally has **no** `version` field — Packagist derives the installable version from tags. `package.json`'s `version` field is cosmetic (frontend build metadata only) but should be kept in sync with the tag for consistency.

## Release checklist

Before tagging, confirm all of the following:

- [ ] All intended changes are committed on `main` (or merged via PR) and pushed to `origin`.
- [ ] `composer test` passes (PHPUnit).
- [ ] `composer analyse` passes (PHPStan/Larastan level 8).
- [ ] `composer format` has been run and any diffs committed (Pint).
- [ ] `npm run test` passes (Vitest).
- [ ] `npm run typecheck` passes (TypeScript strict).
- [ ] `npm run build` has been run and the resulting `dist/` output is committed. `dist/` is intentionally tracked (not gitignored) — see the `[0.1.2]` entry in [CHANGELOG.md](../CHANGELOG.md) for why: without it, every fresh `composer require`/`vendor:publish` from Packagist has no dashboard assets to publish.
- [ ] [CHANGELOG.md](../CHANGELOG.md) has an entry for the release under a new version heading (move content out of `[Unreleased]`), dated with today's date.
- [ ] `package.json`'s `"version"` matches the tag you're about to create (without the `v` prefix).
- [ ] If the change touches `ProcessDefinition`'s schema, `ProcessDefinition::SCHEMA_VERSION` is bumped and a legacy-migration path exists for old payloads (see `ProcessDefinition::migrateLegacyLanes()` for the pattern used in `0.2.0`).

## Step-by-step release

```bash
# 1. Make sure main is up to date and clean
git checkout main
git pull origin main
git status   # should be clean; stash/commit anything outstanding

# 2. Run the full quality gate
composer test
composer analyse
composer format   # commit if this produces changes
npm run test
npm run typecheck

# 3. Build production frontend assets and commit dist/
npm run build
git add dist
git status --short   # confirm only dist/ changed, as expected

# 4. Update CHANGELOG.md: rename [Unreleased] section to the new version + date,
#    add a fresh empty [Unreleased] above it. Update package.json's "version".
#    (edit both files, then:)
git add CHANGELOG.md package.json

# 5. Commit the release prep (skip if step 3/4 produced nothing to commit)
git commit -m "Release vX.Y.Z"

# 6. Push main
git push origin main

# 7. Tag and push the tag — THIS is what triggers Packagist
git tag -a vX.Y.Z -m "vX.Y.Z: <one-line summary>"
git push origin vX.Y.Z
```

Use an **annotated** tag (`-a`) with a message, not a lightweight tag — it's the convention this repo already follows (`v0.1.0` … `v0.2.0`) and gives the tag a real author/date/message in `git show`.

## Verifying the release landed on Packagist

1. GitHub repo → **Settings → Webhooks** → click the `packagist.org` webhook → **Recent Deliveries** → confirm a delivery fired for your tag push with a `200` response.
2. Visit `https://packagist.org/packages/mohamedzaki/laravel-process-builder` and confirm the new version is listed (Packagist processes webhooks within seconds to a couple minutes).
3. Optionally, in a scratch directory, confirm the new version is actually installable:
   ```bash
   composer require mohamedzaki/laravel-process-builder:^X.Y --dry-run
   ```

## Hotfix releases

For an urgent fix on top of an already-released version:

1. Branch from the affected tag if `main` has since diverged with unrelated unreleased work: `git checkout -b hotfix/X.Y.Z vX.Y.Z-1`.
2. Otherwise, just fix forward on `main` if `main` only contains the fix plus already-released code.
3. Follow the [Step-by-step release](#step-by-step-release) as normal, bumping PATCH.
4. If branched from a tag rather than `main`, merge the hotfix branch back into `main` afterward so the fix isn't lost on the next release.

## Rolling back a bad release

Packagist has no "unpublish" for a single version that keeps the package installable — deleting a tag from GitHub removes it from Packagist too, but anyone who already resolved that version in a `composer.lock` keeps using it until they update. For a bad release:

1. **Prefer forward-fixing.** Cut a new PATCH release with the fix rather than deleting tags — this is safer for anyone who already installed the bad version and avoids Composer cache/mirror inconsistencies.
2. If the release is actively harmful (e.g. leaked credentials, destructive bug) and must be pulled:
   ```bash
   git push --delete origin vX.Y.Z   # removes the tag from GitHub
   git tag -d vX.Y.Z                 # removes it locally
   ```
   Then trigger a manual Packagist update (see [Troubleshooting](#troubleshooting)) since tag deletion doesn't always fire the webhook the same way a push does. Confirm on the Packagist package page that the version is gone.
3. Post a note in [CHANGELOG.md](../CHANGELOG.md) explaining what was pulled and why, so the history isn't silently confusing later.

Never force-push or rewrite history on `main` to "undo" a release — the tag is what Packagist read; rewriting `main` afterward doesn't change what was already published.

## Troubleshooting

**Webhook delivery failed / new tag isn't showing up on Packagist:**
- Go to the package page while logged in as an owner/maintainer and click **Update** — this forces Packagist to re-fetch the repo immediately without waiting on the webhook.
- Check GitHub → **Settings → Webhooks** → the `packagist.org` entry → **Recent Deliveries** for the actual HTTP error Packagist returned.

**Tag pushed but version constraints in a consuming app can't resolve it:**
- Confirm the tag name is a valid SemVer version (`vX.Y.Z` or `X.Y.Z` — Packagist strips a leading `v`). Non-SemVer tags (e.g. `release-2026-07-17`) are ignored.
- Run `composer show mohamedzaki/laravel-process-builder --all` in the consuming app to see what Composer's local cache knows, and `composer clear-cache` if it's stale.

**Accidentally pushed a tag pointing at the wrong commit:**
- Delete and recreate it (`git push --delete origin vX.Y.Z`, fix, then re-tag and re-push) **only if no one could plausibly have installed it yet.** Once a tag has been live for more than a few minutes, prefer cutting a corrected PATCH release instead of moving the tag, since Composer's metadata caches and mirrors may already have the original resolved.

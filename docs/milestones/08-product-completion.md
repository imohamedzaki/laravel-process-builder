# Milestone 8: Product Completion — complete

Spec reference: `docs/idea.md` sections 22 (Artisan Commands), 29 (Audit Logging), 33 (README), 34 (Demo Processes), 38 (Implementation Order), 39 (Required Deliverables).

## Done

- [x] `AuditLogger` service (`src/Audit/AuditLogger.php`) — JSON-lines log file, records `{timestamp, userId, processId, processVersion, action, result, changedFiles, correlationId}`, best-effort current-user resolution (never throws if no auth guard is configured), wired into `ProcessController` (create/update/delete), `ProcessValidationController`, `ProcessPreviewController`, `ProcessGenerateController` (started/completed/failed + backup_created), `ProcessRollbackController` (started/completed/failed + backup_created)
- [x] `AuditAction` enum (`src/Enums/AuditAction.php`) — all 12 actions from spec section 29
- [x] `AuditEntry` DTO (`src/DTO/AuditEntry.php`)
- [x] All 10 Artisan commands (`src/Console/*.php`), registered in `LaravelProcessBuilderServiceProvider::boot()` inside the `runningInConsole()` guard:
  - `process-builder:install` — publishes config (asks before overwriting), creates definitions/manifests/backups/audit-log directories, creates the managed route file stub if missing, creates output directories, warns if generation is enabled in production, prints env var guidance
  - `process-builder:doctor` — checks PHP/Laravel version, required extensions, dashboard/generation enabled state, authorization gate, writable directories, managed route file, process JSON validity, frontend asset presence, route cache state, stale generation locks; pass/warning/failure summary
  - `process-builder:scan` — read-only route/controller table via `ProjectScanner`
  - `process-builder:list` — table of saved processes
  - `process-builder:show {process}` — node/edge detail for one process
  - `process-builder:validate {process?}` — validates one or all processes, audit-logs each
  - `process-builder:preview {process}` — compiles in memory, lists files, prints a signed preview token
  - `process-builder:generate {process} [--preview] [--force] [--no-backup]` — confirmation required unless `--no-interaction` in a `local`/`testing`/`development` environment (per spec section 22); audit-logs started/completed/failed + backup_created
  - `process-builder:backups {process}` — table of backups via `BackupService`
  - `process-builder:rollback {process} {backup}` — confirms, backs up current state first, restores, audit-logs started/completed/failed
  - `process-builder:demo [--force]` — installs the two bundled demo process definitions (see below)
- [x] Demo processes: "Create Order" and "Approve Leave Request" (spec section 34) — canonical JSON fixtures bundled at `resources/process-builder-demos/{create-order,approve-leave-request}.json` (tracked in git, ships with the package). `process-builder:demo` reads them, hydrates via `ProcessDefinition::fromArray()` (which stamps a fresh id/timestamps since the fixtures omit them), and saves through the bound `ProcessRepository` — same path a real user save takes. `--force` overwrites an existing same-slug process; without it, existing processes are left alone and skipped with a warning.
  - "Create Order": linear `route → form_request → controller → action → model_create → event → api_resource → response → end`. Demonstrates the base linear-generation path.
  - "Approve Leave Request": `route → form_request → controller → action → condition` with a two-way branch — `success` continues through a second `action → model_update → job → response`, `failure` short-circuits straight to a `response` (422). Demonstrates conditions, branching, jobs, and multi-outcome responses.
  - Both fixtures were verified two ways beyond the standard validation pipeline: (1) `tests/Feature/Console/DemoCommandTest.php` installs them through the real command and asserts `process-builder:validate` passes with exit code 0; (2) manually compiled both through `ProcessCompiler` and ran real `php -l` against every generated file to confirm the output is syntactically valid Laravel code, not just a graph that passes structural validation.
  - Note: the `condition` node's `success` handle only permits connecting to `Action | Event | Job | Response` per `ConnectionMap` — it does **not** allow `ModelUpdate`/`ModelCreate` directly. The leave-request approval branch routes through an intermediate `action` node (`RecordLeaveApprovalAction`) before the `model_update`, mirroring how the main path already goes `action → model_create`. Worth remembering if more demo processes are added later.
- [x] `GenerationService::generate()` gained an optional `?bool $createBackups = null` parameter (defaults to the constructor-configured value) so the CLI's `--no-backup` flag can override per-call without breaking existing callers
- [x] Command tests: `tests/Feature/Console/ProcessBuilderCommandsTest.php` (17 tests covering list/show/validate/preview/generate/backups/rollback/scan/doctor), `tests/Feature/Console/InstallCommandTest.php` (3 tests, carefully isolated from the real workbench `config_path()` — see "Environment note" below), `tests/Unit/Audit/AuditLoggerTest.php` (3 tests)
- [x] Full suite: **167/167 backend tests passing** (163 + 4 new `DemoCommandTest` cases), PHPStan level 8 clean, Pint clean (confirmed stable across repeated runs)
- [x] Final quality-gate pass run back-to-back as the exact `composer.json`/`npm` scripts: `composer test` (167/167), `composer analyse` (clean), `composer format` (no-op, already clean), `npm run test` (33/33), `npm run typecheck` (clean), `npm run build` (clean)

- [x] README completeness pass against spec section 33's 25-item checklist — structure already matched the checklist; added `process-builder:demo` to the Artisan commands list and feature list, updated the status line from "early MVP" to "feature-complete MVP, pre-1.0"
- [x] CHANGELOG entry for this release — `CHANGELOG.md`'s `[Unreleased]` section now documents every milestone (2 through 8), not just Milestone 1, plus a consolidated "Fixed" section for the real bugs found along the way
- [x] `IMPLEMENTATION_STATUS.md` updated to mark Milestone 8 complete

## Not started yet

Nothing outstanding from this milestone's original scope. See the repository root for release-readiness items beyond the milestone spec itself (e.g. committing this session's work — the last commit predates Milestones 4–8 — and cutting a version tag).

## Environment note: the flaky-test-suite root cause (read this first if tests seem to fail non-deterministically)

Earlier in this session, `workbench/config/` was accidentally deleted (mistaken for leaked debris from a stray `vendor:publish` write) and the suite became non-deterministically broken — different tests would fail depending on execution order, all with symptoms like `session.driver`/`app.debug`/`view.paths` resolving to `null`, causing `InvalidArgumentException: Unable to resolve NULL driver for [SessionManager]` or `TypeError` in `FileViewFinder`/`HtmlErrorRenderer`.

**Root cause:** `Orchestra\Testbench\Bootstrap\LoadConfigurationWithWorkbench` (which extends `Orchestra\Testbench\Bootstrap\LoadConfiguration`) has two places where `workbench/config/`'s existence changes behavior:

1. `LoadConfiguration::loadConfigurationFiles()` populates a **static, process-wide cache** (`static::$cachedFrameworkConfigurations`) with an empty array whenever `uses_default_skeleton($app->basePath())` is true (which it always is here, because of the `.testbench-default-skeleton` marker required to avoid the Milestone 1 infinite-recursion bug) — the assumption being "the default skeleton's own config already has everything, don't bother merging framework defaults."
2. `LoadConfiguration::getConfigurationPath()` only falls back to Testbench's bundled default-skeleton config path (`vendor/orchestra/testbench-core/laravel/config/`) when `is_dir($app->basePath('config'))` is **false**. The moment `workbench/config/` exists as a directory — even empty — this fallback is skipped and Testbench loads config from our (empty) directory instead.

Both mechanisms assume `workbench/config/` is either genuinely absent, or genuinely populated with the full default skeleton. An empty-but-present directory hits the worst case of both: no fallback per-file config from the default skeleton, AND no framework-defaults merge.

**Fix applied:** `workbench/config/` now contains real copies of all 15 files from `vendor/orchestra/testbench-core/laravel/config/` (`app.php`, `auth.php`, `broadcasting.php`, `cache.php`, `concurrency.php`, `cors.php`, `database.php`, `filesystems.php`, `hashing.php`, `logging.php`, `mail.php`, `queue.php`, `services.php`, `session.php`, `view.php`). `pint.json` was updated with `"exclude": ["workbench/config"]` since these are vendor-sourced skeleton files, not package source, and shouldn't be reformatted.

**If the suite ever goes non-deterministic again:** check `workbench/config/` first. It must exist and be non-empty, or must not exist at all — never exist-but-empty.

**Separate, real bug found and fixed along the way (unrelated to the above):** `AuditLogger::currentUserId()` called `$this->auth->guard()` with no default guard configured in the workbench app, throwing `InvalidArgumentException: Auth guard [] is not defined.` — this broke every command/controller that used `AuditLogger` in a context without an `auth.defaults.guard`. Fixed by wrapping the guard resolution in a try/catch that returns `null` on `InvalidArgumentException` (audit entries are still written, just without a resolvable user id).

**`InstallCommandTest` isolation note:** `InstallCommand::publishConfiguration()` calls the real `vendor:publish` Artisan command, which always writes to `config_path()` as resolved by the app's `ApplicationBuilder`-registered `publishes()` mapping — `$this->app->useConfigPath(...)` cannot redirect it after boot, because the mapping captures the path at `boot()` time. The test avoids ever touching the real `workbench/config/process-builder.php` by pre-seeding a placeholder there in `setUp()` (so the command always takes its "already exists, ask to overwrite" branch, which every test declines) and restoring whatever was there before in `tearDown()`.

## Next step for a fresh session

All quality gates and the demo processes are done. Pick up the "Not started yet" list above: README pass against spec section 33's checklist, then CHANGELOG, then mark this file's header `— complete` and update `IMPLEMENTATION_STATUS.md`.

If you want to re-confirm nothing has regressed first:

```bash
composer test      # expect 167/167
composer analyse    # expect no errors
composer format     # Pint, should be a no-op (already clean)
npm run test        # expect 33/33
npm run typecheck
npm run build
```

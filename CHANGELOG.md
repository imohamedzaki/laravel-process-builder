# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.1.1] - 2026-07-15

### Fixed

- CI/repo reproducibility: `package-lock.json` was gitignored, causing `npm ci` to fail on every fresh checkout; now committed (`composer.lock` stays ignored, as is standard for a library).
- `workbench/bootstrap/cache/` and the entire `workbench/storage/` tree were untracked with no placeholders, so a clean clone was missing directories Laravel/Testbench require at boot (`view.compiled`, package manifest cache), breaking `composer install`, PHPStan/Larastan, and the PHPUnit suite on CI and for new contributors. Added `.gitkeep` placeholders to the required subdirectories.
- `orchestra/testbench` was constrained to `^9.0 || ^10.0`, but no release in that range supports `laravel/framework ^13.0`, making the CI's PHP 8.3 / Laravel 13 matrix cell permanently unsatisfiable. Widened to `^9.0 || ^10.0 || ^11.0`.

No installable package code changed in this release — these fixes only affect repository/CI reproducibility for contributors building from a fresh clone.

## [0.1.0] - 2026-07-15

### Added

- **Package foundation** — composer.json, service provider, configuration, Testbench/Workbench wiring, dashboard route + Blade shell with a minimal React/TypeScript app, environment- and gate-based dashboard/API authorization middleware, core domain enums (`NodeType`, `ProcessStatus`, `ValidationSeverity`) and exception hierarchy.
- **Read-only project scanner** — `RouteScanner` and `ControllerScanner` inspect the live Laravel route collection and controller classes (Reflection-based, never crashes on missing classes/methods); `ProjectScanner` aggregates counts, duplicate-route-name and missing-controller detection; read-only scanner API endpoints; frontend Project Explorer tab with stats, warnings, and a route table.
- **Process definition domain** — immutable domain value objects (`ProcessNode`, `ProcessEdge`, `ProcessMetadata`, `ProcessDefinition`) with structural validation at hydration time; `FileProcessRepository` with atomic writes, file locking, and path-traversal-safe slug validation; full process CRUD API with a consistent `{data, meta, errors}` envelope.
- **Visual editor** — React Flow canvas with a typed, categorized node palette, drag-to-create, connection validation against the allowed graph, a property inspector with typed per-node-type forms, undo/redo history, and a process list/browser UI.
- **Validation engine** — a composed pipeline of rule classes (graph structure, cycle detection, allowed connections, route rules, cross-process route-name collisions, class-name rules, form-request rules) with a shared `ProcessGraph` reachability/cycle-detection helper; `POST /api/processes/{process}/validate` endpoint and a `ValidationPanel` UI.
- **Code preview / compilation** — a multi-stage compiler (route file, controller, form request, action, event, job, API resource, feature test) producing clean, PSR-12, strictly-typed Laravel code from PSR-based stub templates; graph-reachability-aware multi-hop resolution (e.g. `route → form_request → controller`); HMAC-signed, tamper-evident, TTL-bound preview confirmation tokens; `POST /api/processes/{process}/preview` compiles in memory and never touches disk.
- **Safe generation** — `GenerationService` orchestrator enforcing generation-enabled + allowed-environment checks, preview-token and definition-checksum matching, managed-file ownership checks (refuses untracked or hand-edited files unless forced), automatic pre-write backups with retention enforcement, atomic file writes with real `php -l` syntax validation before every write, and a per-process generation lock; `POST /api/processes/{process}/generate`, backup listing, and one-click rollback endpoints.
- **Audit logging** — `AuditLogger` records a JSON-lines trail of process create/update/delete, validation, preview, generation, backup, and rollback events with best-effort current-user resolution.
- **Artisan commands** — `process-builder:install`, `process-builder:doctor` (12-point environment health check), `process-builder:scan`, `process-builder:list`, `process-builder:show`, `process-builder:validate`, `process-builder:preview`, `process-builder:generate`, `process-builder:backups`, `process-builder:rollback`, and `process-builder:demo` (installs two bundled demo processes: "Create Order" and "Approve Leave Request", demonstrating linear generation, branching conditions, jobs, and multi-outcome responses).
- Full backend and frontend test suites covering every milestone above (167 PHPUnit tests, 33 Vitest tests), PHPStan level 8, and Laravel Pint code style, all enforced in CI across the PHP 8.2/8.3 × Laravel 12/13 matrix.

### Fixed

- `SafePath::resolveWithin` rejected path traversal on the full identifier but validated only the basename, allowing `'../../etc/passwd.json'`-style traversal to pass; now rejects any `/`/`\` in the filename outright.
- `RouteRule` and the compiler's controller-resolution logic checked only a *direct* edge from a route to its controller, incorrectly rejecting the valid `Route → Form Request → Controller` multi-hop pattern; both now use graph reachability instead of one-hop edge lookups.
- `ProcessRollbackController` reconstructed each managed file's path via `base_path($entry->relativePath)`, which breaks when `process-builder.output.*` config points outside `base_path()`; the manifest now persists the exact `absolutePath` used at generation time.
- `BackupService`'s relative-path-to-filename encoding didn't escape `:`, silently truncating backup filenames for Windows absolute paths with a drive letter; fixed by escaping `:` alongside `/` and `\`.
- `AuditLogger` threw when no default auth guard was configured (common in isolated/test apps); guard resolution failures are now treated as an anonymous audit entry instead of a hard failure.

[Unreleased]: https://github.com/imohamedzaki/laravel-process-builder/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/imohamedzaki/laravel-process-builder/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/imohamedzaki/laravel-process-builder/releases/tag/v0.1.0

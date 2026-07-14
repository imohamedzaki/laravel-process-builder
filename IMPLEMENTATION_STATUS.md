# Implementation Status

Tracking progress against `docs/idea.md`, milestone order per section 38.

## Milestone 1: Package Foundation — complete

- [x] composer.json (PHP ^8.2, Laravel ^12|^13, Testbench/Pint/PHPStan/Larastan dev deps)
- [x] package.json / vite.config.ts / tsconfig.json
- [x] Service provider (`LaravelProcessBuilderServiceProvider`): config merge, route registration gated by enabled+environment, view loading, managed route file loading, publishing
- [x] `config/process-builder.php`
- [x] `routes/web.php`, `routes/api.php` (dashboard + health endpoint)
- [x] Dashboard Blade view with manifest-based / Vite-dev asset loading
- [x] Minimal React/TypeScript app shell (App.tsx, api client, types)
- [x] Middleware: `EnsureProcessBuilderIsEnabled`, `AuthorizeProcessBuilder`
- [x] Core domain exceptions
- [x] `NodeType`, `ProcessStatus`, `ValidationSeverity` enums
- [x] Testbench (`testbench.yaml`) + Workbench service provider + `workbench/.env` + `workbench/bootstrap/.testbench-default-skeleton` marker + `workbench/public/index.php` (custom entry point booting via `Orchestra\Testbench\Foundation\Application::create()` with an explicit `chdir()` so it resolves correctly regardless of the PHP built-in server's docroot)
- [x] phpunit.xml, pint.json, phpstan.neon
- [x] Basic tests: service provider boot, dashboard access (enabled/disabled/environment), health endpoint — 8/8 passing
- [x] Basic frontend test (App smoke test) — 3/3 passing, Vitest + TypeScript strict + production build all clean
- [x] README skeleton
- [x] Verified: `composer install`, `vendor/bin/testbench workbench:install`, `vendor/bin/testbench serve`, and a plain `php -S -t workbench/public` all boot the dashboard successfully at `/process-builder` (200, correct HTML) and `/process-builder/api/health` (200, JSON)

**Environment note:** `vendor/bin/testbench serve` / `php -S -t workbench/public` requires `workbench/public/index.php` to exist as a real file (not testbench-core's internal skeleton copy, which recurses infinitely if placed at `workbench/bootstrap/app.php` — that file must NOT exist once the `.testbench-default-skeleton` marker is present). See the file's contents for the correct boot sequence.

## Milestone 2: Read-Only Scanner — complete

- [x] DTOs: `RouteInfo`, `ParameterInfo`, `MethodInfo`, `ControllerInfo`, `ProjectSummary`
- [x] `RouteScanner` (reads the live Laravel route collection; supports closures, controller actions, invokable controllers, middleware, parameters, domains, configurable URI-prefix and namespace exclusions)
- [x] `ControllerScanner` (Reflection-based: public declared methods, constructor dependencies, form-request-style parameter detection, invokable detection, safe handling of missing classes/methods — never crashes)
- [x] `ProjectScanner` facade: aggregate counts, named/unnamed split, routes-by-method, duplicate route name detection, routes-with-missing-controllers detection
- [x] Scanner API endpoints: `GET /process-builder/api/project`, `/project/routes`, `/project/controllers` — all read-only, all under the same auth/environment gate as the dashboard
- [x] Container bindings for `RouteScannerContract` / `ControllerScannerContract` / `ProjectScanner` in the service provider, config-driven exclusions
- [x] Scanner tests: 21 new PHPUnit tests (unit: RouteScanner, ControllerScanner, ProjectScanner; feature: scanner endpoints) — 29/29 passing, PHPStan level 8 clean, Pint clean
- [x] Frontend Project Explorer: types (`RouteInfo`, `ControllerInfo`, `ProjectSummary`), `api/project.ts` client, `useProjectStore` (Zustand), `ProjectExplorer` component (stats, duplicate-name/missing-controller warnings, route table, rescan button) wired into `App.tsx` — 6/6 Vitest tests passing, typecheck clean, production build clean
- [x] Verified live: scanned real Workbench + package routes/controllers via `/process-builder/api/project*` over an actual HTTP server

**Test fixture note:** host-app fixture controllers for scanner tests live under `Workbench\App\Http\Controllers` (autoloaded via the existing `workbench/app` mapping), not under the package's own test namespace — the package's default `scanner.exclude_namespaces` config excludes anything starting with `MohamedZaki\LaravelProcessBuilder`, which would otherwise silently exclude fixtures placed in `...\Tests\Fixtures`.

## Milestone 3: Process Definition Domain — backend complete (frontend editor deferred to Milestone 4)

- [x] Domain value objects: `NodePosition`, `ProcessNode`, `ProcessEdge`, `ProcessMetadata`, `ProcessDefinition`, `ProcessCollection` — all immutable, `fromArray()`/`toArray()` hydration, matching the JSON schema in spec section 12
- [x] Structural validation at hydration time: required name/kebab-case slug, unknown node types rejected, duplicate node/edge ids rejected, edges referencing missing nodes rejected — all via `InvalidProcessDefinitionException`
- [x] `ProcessRepository` contract + `FileProcessRepository`: atomic writes (temp file + rename), file locking, slug- and id-based lookup, safe slug validation (`SafePath`) that rejects path traversal without ever touching the filesystem for unsafe identifiers, readable pretty-printed JSON
- [x] Process CRUD API: `GET/POST /api/processes`, `GET/PUT/DELETE /api/processes/{process}`, `POST /api/processes/{process}/duplicate` — slug-collision 409, validation 422, not-found 404, all through Laravel Form Requests and a consistent `{data, meta, errors}` envelope
- [x] Domain exception → HTTP response mapping via `ProcessBuilderException::render()` (`ProcessNotFoundException` → 404, `InvalidProcessDefinitionException` → 422, `UnsafeOutputPathException` → 422)
- [x] 31 new tests (domain hydration/validation, `SafePath`, `FileProcessRepository`, CRUD endpoints incl. CSRF-protected writes) — 60/60 total passing, PHPStan level 8 clean, Pint clean
- [x] Verified live over real HTTP: create, list, and show all confirmed working end-to-end including CSRF enforcement on state-changing requests

**Deferred to Milestone 4:** the visual editor UI for creating/editing processes (React Flow canvas, palette, inspector) — this milestone covered the backend domain + API only, per the spec's own implementation order.

## Milestone 4: Visual Editor — not started

## Milestone 5: Validation Engine — not started

## Milestone 6: Code Preview — not started

## Milestone 7: Safe Generation — not started

## Milestone 8: Product Completion — not started

---

### Deliberate deferrals (Non-Goals per spec section 36)

BPMN engine, persistent workflow execution, human task inboxes, timers, multi-tenant execution, arbitrary PHP execution, visual DB schema designer, form UI builder, SaaS sync, production monitoring.

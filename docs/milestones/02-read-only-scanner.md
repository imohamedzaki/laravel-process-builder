# Milestone 2: Read-Only Scanner — complete

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

# Milestone 1: Package Foundation — complete

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

**Critical environment requirement (found during Milestone 8, applies retroactively):** `workbench/config/` must contain real copies of Laravel's base config files (`app.php`, `auth.php`, `session.php`, `view.php`, etc. — copy from `vendor/orchestra/testbench-core/laravel/config/`). If this directory is missing or empty, Testbench's `LoadConfigurationWithWorkbench` bootstrapper silently skips merging framework default configuration (`uses_default_skeleton()` short-circuits the merge, and a separate `is_dir($app->basePath('config'))` check disables the fallback to Testbench's own default skeleton path). Symptoms: `session.driver`/`app.debug`/`view.paths` resolve to `null`, causing `InvalidArgumentException: Unable to resolve NULL driver` or `TypeError` in `FileViewFinder`/`HtmlErrorRenderer`, non-deterministically depending on which test runs first in a given PHPUnit process. `pint.json` excludes `workbench/config` since those are vendor-sourced skeleton files, not package source.

# Laravel Process Builder

**Design Laravel processes visually. Generate clean Laravel code.**

A visual process builder for designing Laravel routes, controllers, actions, validations, services, and application workflows — conceptually similar to the Joget Process Builder, but generating clean, native, maintainable Laravel code instead of running inside a proprietary engine.

> Status: early MVP under active development. See [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) for current progress.

---

## Table of contents

1. [Overview](#overview)
2. [Features](#features)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Dashboard access](#dashboard-access)
7. [Authorization](#authorization)
8. [Enabling code generation](#enabling-code-generation)
9. [Creating a process](#creating-a-process)
10. [Scanning existing routes](#scanning-existing-routes)
11. [Previewing generated code](#previewing-generated-code)
12. [Generating code](#generating-code)
13. [Rollback](#rollback)
14. [Artisan commands](#artisan-commands)
15. [Process definition format](#process-definition-format)
16. [Managed-file behavior](#managed-file-behavior)
17. [Security warnings](#security-warnings)
18. [Development setup](#development-setup)
19. [Running Workbench](#running-workbench)
20. [Running tests](#running-tests)
21. [Building frontend assets](#building-frontend-assets)
22. [Contributing](#contributing)
23. [Roadmap](#roadmap)
24. [License](#license)

---

## Overview

Laravel Process Builder lets you design an application flow — route → middleware → form request → controller → action → model operation → event → job → response — on a visual canvas, then generate real, idiomatic Laravel files from that design. Existing application code is always treated as read-only; only files explicitly managed by the package may ever be written or overwritten.

## Features

- Read-only scanner for existing routes and controllers (Project Explorer).
- Visual React Flow canvas with a typed node palette and property inspector.
- File-based, Git-friendly JSON process definitions.
- Multi-stage compiler producing clean, PSR-12, strictly typed Laravel code.
- Two-step preview → generate workflow with signed confirmation tokens.
- Managed-file ownership rules, checksums, and conflict detection.
- Automatic backups and one-click rollback.
- Artisan commands for install, doctor, scan, validate, preview, generate, backups, and rollback.

## Requirements

```text
PHP: ^8.2
Laravel: ^12.0 | ^13.0
```

Laravel 13 installations will naturally require PHP 8.3+ through Laravel's own constraints.

## Installation

Because this package exposes application architecture and code-generation capability, install it as a **development dependency**:

```bash
composer require mohamedzaki/laravel-process-builder --dev

php artisan process-builder:install
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=process-builder-config
```

Key environment variables:

```dotenv
PROCESS_BUILDER_ENABLED=true
PROCESS_BUILDER_GENERATION_ENABLED=false
PROCESS_BUILDER_PATH=process-builder
```

See [config/process-builder.php](config/process-builder.php) for the full set of options (environments whitelist, middleware, authorization gate, output directories, backup retention, scanner exclusions).

## Dashboard access

Once enabled and in an authorized environment, visit:

```text
/process-builder
```

The dashboard is disabled by default outside of `local`, `development`, and `testing` environments, regardless of the `enabled` flag.

## Authorization

Define the `manage-process-builder` gate in your application (e.g. in `AuthServiceProvider`) to control who may access the dashboard and API:

```php
Gate::define('manage-process-builder', fn ($user) => $user->isAdmin());
```

## Enabling code generation

Generation is disabled by default. Enable it explicitly per environment:

```dotenv
PROCESS_BUILDER_GENERATION_ENABLED=true
```

Every write additionally requires a valid preview confirmation token, an authorized environment, and (if the target file is already managed) a checksum match against the last known generated state.

## Creating a process

Open the dashboard, use **New Process**, drag nodes from the palette onto the canvas, connect them, and fill in each node's properties in the right-hand inspector. Save persists a JSON definition under `process-builder/definitions/`.

## Scanning existing routes

Use the **Project Explorer** tab, or:

```bash
php artisan process-builder:scan
```

This is entirely read-only and never modifies your application.

## Previewing generated code

Use the **Preview** action in the toolbar, or:

```bash
php artisan process-builder:preview {process}
```

This compiles the process in memory and returns generated code and diffs without writing anything to disk.

## Generating code

```bash
php artisan process-builder:generate {process}
```

Requires generation to be enabled, a valid preview, and an authorized environment. Existing non-managed files are never overwritten.

## Rollback

```bash
php artisan process-builder:backups {process}
php artisan process-builder:rollback {process} {backup}
```

## Artisan commands

```bash
php artisan process-builder:install
php artisan process-builder:doctor
php artisan process-builder:scan
php artisan process-builder:list
php artisan process-builder:show {process}
php artisan process-builder:validate {process?}
php artisan process-builder:preview {process}
php artisan process-builder:generate {process}
php artisan process-builder:backups {process}
php artisan process-builder:rollback {process} {backup}
```

## Process definition format

See [docs/process-definition-schema.md](docs/process-definition-schema.md).

## Managed-file behavior

See [docs/code-generation.md](docs/code-generation.md) and [docs/security.md](docs/security.md).

## Security warnings

The dashboard is a highly privileged developer tool: it can read application architecture and write PHP files. Keep it disabled in production, behind authentication and an authorization gate, and never expose it publicly. See [docs/security.md](docs/security.md) for the full threat model.

## Development setup

```bash
git clone <repo>
cd laravel-process-builder
composer install
npm install
```

## Running Workbench

```bash
composer run serve
```

Then visit `http://127.0.0.1:8000/process-builder`.

For frontend hot-reload during development, run Vite alongside it:

```bash
npm run dev
```

## Running tests

```bash
composer test        # PHPUnit
composer analyse      # PHPStan / Larastan
composer format        # Laravel Pint
npm run test           # Vitest
npm run typecheck      # TypeScript
npm run build           # Production frontend build
```

## Building frontend assets

```bash
npm run build
```

Compiled assets are emitted to `dist/` with a Vite manifest, and are published to the host application via `php artisan vendor:publish --tag=process-builder-assets`.

## Contributing

Issues and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for setup instructions, coding standards, and the required quality gate. Please also review our [Code of Conduct](CODE_OF_CONDUCT.md) and [Security Policy](SECURITY.md).

## Roadmap

See [docs/roadmap.md](docs/roadmap.md) for planned Phase 2–4 features (deeper AST analysis, listener/notification builders, a runtime workflow engine, BPMN import/export, and more).

## License

MIT. See [LICENSE](LICENSE).

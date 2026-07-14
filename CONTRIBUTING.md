# Contributing to Laravel Process Builder

Thanks for your interest in contributing. This document covers how to set up the project, the standards your change is expected to meet, and how to submit it.

## Table of contents

1. [Code of conduct](#code-of-conduct)
2. [Before you start](#before-you-start)
3. [Development setup](#development-setup)
4. [Project structure](#project-structure)
5. [Making changes](#making-changes)
6. [Quality gate](#quality-gate)
7. [Commit messages](#commit-messages)
8. [Submitting a pull request](#submitting-a-pull-request)
9. [Reporting bugs](#reporting-bugs)
10. [Reporting security issues](#reporting-security-issues)
11. [Proposing features](#proposing-features)

## Code of conduct

This project follows a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

## Before you start

- For anything beyond a small, obvious fix (typos, docs, small bug fixes), please open an issue first to discuss the change before writing code. This avoids wasted effort on approaches that don't fit the project's direction.
- Check [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) and [docs/roadmap.md](docs/roadmap.md) to see what's already planned or in progress.
- Read [docs/architecture.md](docs/architecture.md) and [docs/idea.md](docs/idea.md) to understand the design intent, especially the "existing application code is read-only" principle — this is a hard constraint, not a suggestion.

## Development setup

Requirements: PHP ^8.2, Composer, Node.js (for the frontend), and either Laravel ^12 or ^13 pulled in via Testbench.

```bash
git clone https://github.com/imohamedzaki/laravel-process-builder.git
cd laravel-process-builder

composer install
npm install
```

Run the Workbench test application (a minimal Laravel app used for local development and integration tests):

```bash
composer run serve
```

Then visit `http://127.0.0.1:8000/process-builder`. For frontend hot-reload during development, run Vite alongside it:

```bash
npm run dev
```

## Project structure

```text
src/            Package source (PHP) — services, HTTP layer, domain, scanning, generation
resources/js/   React/TypeScript frontend (dashboard SPA)
resources/views Blade shell for the dashboard
config/         Publishable package configuration
routes/         Package-managed routes (web + API)
tests/          PHPUnit tests (Unit + Feature)
workbench/      Minimal Laravel app used by Testbench for local dev/testing
docs/           Design and reference documentation
```

Package code lives under the `MohamedZaki\LaravelProcessBuilder` namespace (PSR-4, `src/`).

## Making changes

- Follow the conventions in the root [CLAUDE.md](CLAUDE.md) if present, and match the existing code style in the file you're editing.
- Controllers stay thin; put logic in services/actions. Validation belongs in Form Requests.
- Use Eloquent relationships properly and avoid N+1 queries where the package touches host-app models.
- Favor explicit, strictly typed code (PHP `declare(strict_types=1)` + return types; TypeScript strict mode is enabled — don't weaken it).
- Never write to files the package doesn't explicitly manage. If your change touches the generator or file-writing paths, re-read [docs/security.md](docs/security.md) and [docs/code-generation.md](docs/code-generation.md) first.
- Add or update tests for any behavior change. New PHP classes need PHPUnit coverage; new frontend components/stores need Vitest coverage.
- Update [CHANGELOG.md](CHANGELOG.md) under `[Unreleased]` for any user-facing change.
- Update relevant docs (`README.md`, `docs/*.md`) when behavior, config, or commands change.

## Quality gate

Run all of the following before opening a PR — CI runs the same checks:

```bash
composer test        # PHPUnit
composer analyse      # PHPStan / Larastan (level 8)
composer format        # Laravel Pint (auto-fixes style)
npm run test           # Vitest
npm run typecheck      # TypeScript strict
npm run build           # Production frontend build
```

A PR with a red check will not be merged. If `composer format` changes files, commit those changes too.

## Commit messages

Use short, imperative-mood messages (`Add scanner exclusion for vendor namespaces`, not `Added` or `Adding`). Reference an issue number when applicable (`Fixes #12`). Keep unrelated changes out of a single commit.

## Submitting a pull request

1. Fork the repo and create a branch off `main` (`git checkout -b fix/short-description`).
2. Make your change, following the guidance above.
3. Run the full quality gate locally.
4. Push and open a PR against `main` using the PR template. Fill in every section — especially what you tested and how.
5. Link the issue it resolves, if any.
6. Be responsive to review feedback. Small, focused PRs get reviewed faster than large ones.

## Reporting bugs

Open a [bug report issue](../../issues/new/choose) with:

- Package version, PHP version, Laravel version.
- Steps to reproduce, expected vs. actual behavior.
- Relevant config (`config/process-builder.php` values that differ from defaults) and, if applicable, the process definition JSON that triggers the bug.

## Reporting security issues

Do **not** open a public issue for security vulnerabilities (e.g. anything that could lead to unauthorized file writes, code execution, or bypassing the environment/authorization gates). See [SECURITY.md](SECURITY.md) for the private disclosure process.

## Proposing features

Open a [feature request issue](../../issues/new/choose) describing the problem you're trying to solve (not just the solution) and how it fits the project's stated [Non-Goals](docs/roadmap.md). Features that reintroduce arbitrary code execution, persistent workflow execution, or writes to non-managed files are out of scope by design.

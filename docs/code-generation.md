# Code generation

## Two-step workflow

1. **Preview** (`POST /process-builder/api/processes/{process}/preview`): validates the process, compiles all files in memory, returns generated code + diffs + conflicts + a short-lived signed confirmation token. Writes nothing.
2. **Generate** (`POST /process-builder/api/processes/{process}/generate`): requires that token, re-checks the definition and on-disk files haven't changed, then acquires a lock, backs up affected files, writes to temp files, validates PHP syntax, atomically replaces destinations, and writes the generation manifest.

## Managed files

A file is "managed" only when all three are true:

1. It lives inside a directory listed in `config('process-builder.output.*')`.
2. It contains the exact marker:

```php
/**
 * This file is managed by Laravel Process Builder.
 * Manual changes may be overwritten.
 */
```

3. It is listed in the generation manifest for that process (`process-builder/manifests/{slug}.json`), which records each generated file's logical type, relative path, and SHA-256 checksum.

Before overwriting a managed file, its current checksum is compared against the manifest. A mismatch means it was hand-edited outside the tool — generation refuses by default (`GenerationConflictException`) and requires an explicit `force` flag, which still backs up the current content first.

Non-managed files are never overwritten, full stop — no force flag can bypass that.

## Determinism

Given the same process definition, the compiler produces byte-identical output every time. No timestamps are embedded in generated PHP bodies (only in the JSON manifest), so re-running generation on an unchanged definition produces no Git diff.

## Stubs

Base stubs live in `resources/stubs/` (`controller.stub`, `form-request.stub`, `action.stub`, `service.stub`, `event.stub`, `job.stub`, `notification.stub`, `resource.stub`, `feature-test.stub`). Compilers render structured DTOs into these — never string-interpolate raw user input directly into PHP source.

# Security

Laravel Process Builder is a highly privileged developer tool: it can read application architecture (routes, controller signatures) and write PHP files to disk. Treat it accordingly.

## Defaults

- `PROCESS_BUILDER_ENABLED` defaults to `true`, but the provider **also** requires the current environment to be in `process-builder.environments` (default: `local`, `development`, `testing`). Production is never reachable by default.
- `PROCESS_BUILDER_GENERATION_ENABLED` defaults to **false**. Enabling the dashboard does not enable writes.
- Every dashboard/API route passes through `EnsureProcessBuilderIsEnabled` and `AuthorizeProcessBuilder` middleware, in addition to whatever middleware stack (`process-builder.middleware`, default `web`) you configure.
- `AuthorizeProcessBuilder` checks the `manage-process-builder` gate (configurable) when it is defined; undefined gates fail open only if you never define one — define it in production-adjacent environments.

## Write-path guarantees

1. **No arbitrary paths from the browser.** The frontend/API only ever sends a logical `type` (e.g. `"controller"`); the backend resolves the physical path exclusively from `config('process-builder.output.*')`.
2. **No path traversal.** All resolved paths are canonicalized and checked to be inside the configured managed directory before any read or write.
3. **No writes into `vendor` or outside managed directories.** Enforced by `UnsafeOutputPathException`.
4. **No overwriting non-managed files.** A file is only considered managed if it carries the exact marker comment AND is listed in the last generation manifest for that process. See `docs/code-generation.md`.
5. **Two-step preview/generate with signed tokens.** `POST .../preview` compiles in memory and returns a short-lived signed token. `POST .../generate` requires that token and re-validates that neither the definition nor the target files changed since preview.
6. **Atomic writes + file locking + backups.** Every generation acquires a lock, writes to temp files, validates PHP syntax, then atomically replaces destinations — after first copying current file contents to `storage/app/process-builder/backups/`.
7. **No `eval`, no shell-out with user input, no regex-based PHP rewriting.** Code generation always goes through structured DTOs rendered into templates/AST, never through interpolating raw user strings into PHP.
8. **Rate limiting** applies to all write endpoints (preview/generate/rollback).

## Reporting a vulnerability

Please open a private security advisory on the repository rather than a public issue.

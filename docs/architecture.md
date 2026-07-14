# Architecture

## Guiding principles

See `docs/idea.md` section 35 for the full list. The two that shape every other decision:

1. **Existing application code is read-only.** The scanner (`src/Scanning`) uses Laravel's route collection and PHP Reflection/AST — it never writes.
2. **Generated code belongs to explicit managed directories**, identified by an exact marker comment and tracked in a generation manifest (`process-builder/manifests/`). Nothing outside `config('process-builder.output.*')` is ever touched.

## Layers

```text
Http/Controllers      → thin, delegate to services/repositories
Domain/Processes      → ProcessDefinition, ProcessNode, ProcessEdge (DTOs, immutable)
Repositories          → ProcessRepository contract + FileProcessRepository implementation
Scanning              → read-only inspection of the host Laravel app
Parsing               → nikic/php-parser AST helpers used by the scanner
Graph                 → graph traversal, cycle detection, connection-map enforcement
Validation             → rule classes composed into a validation pipeline
Compilation            → multi-stage compiler: DTO -> IR -> generated file set
Generation              → orchestrates preview/generate, manifests, atomic writes
Filesystem              → atomic writer, path safety, locking primitives
Backup                  → backup creation and rollback
Diff                    → existing-vs-generated diff computation
Security                → preview token signing/verification
```

## Service provider responsibilities

`LaravelProcessBuilderServiceProvider` merges config, conditionally registers dashboard/API routes (gated on `enabled` + `environments`), loads the Blade view namespace, loads the host application's managed route file if present, and publishes config/assets. Route registration is gated in the provider itself (not only in middleware) so that disabled/unauthorized installs never even expose route names.

## Why file-based process definitions instead of a database

Per spec section 12: process JSON is the source of truth, committed to version control, so process changes review like code changes. No migrations are required to install the package. A `ProcessRepository` interface keeps a future database-backed implementation possible without touching callers.

## Compiler determinism

The compiler must produce byte-identical output for identical input (spec section 16). Generated files intentionally omit "generated at" timestamps from PHP file bodies (that metadata lives only in the JSON manifest) so re-generating with no definition changes produces no Git diff.

## Extension points

See `docs/node-development.md` for how to add a new node type via `NodeHandler` + `NodeHandlerRegistry` without touching a central switch statement.

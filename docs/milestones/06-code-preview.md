# Milestone 6: Code Preview — complete

- [x] Compilation DTOs: `GeneratedFile` (with sha256 checksum), `GeneratedFileCollection`, `CompilationResult`, `CompilationContext`
- [x] Stub templates (`resources/stubs/*.stub`): controller, form-request, action, event, job, resource, feature-test — each carries the exact managed-file marker comment from spec section 2, right after `declare(strict_types=1);`
- [x] `StubRenderer` (simple `{{ token }}` substitution + blank-line normalization) and `ClassNameResolver` (derives class/namespace from node data with sane fallbacks)
- [x] Individual compilers: `RouteFileCompiler`, `ControllerCompiler`, `FormRequestCompiler`, `ActionCompiler`, `EventCompiler`, `JobCompiler`, `ApiResourceCompiler`, `FeatureTestCompiler` — each produces clean, PSR-12, strictly-typed Laravel code per spec section 17's example output
- [x] `ProcessCompiler` orchestrator: validates first (refuses to compile an invalid process), walks the graph to resolve form-request/action/model/resource references into each controller, and resolves each route's actual reachable controller + its real method name (not hardcoded) via `ProcessGraph::reachableFrom()` — multi-hop chains like `route → form_request → controller` work correctly
- [x] `PreviewTokenSigner` / `PreviewToken`: HMAC-SHA256-signed, tamper-evident, TTL-bound preview confirmation tokens (spec section 19) keyed off `app.key`
- [x] `POST /api/processes/{process}/preview`: compiles in memory, writes nothing to disk, returns generated files + validation result + a signed preview token (`null` when invalid)
- [x] 21 new backend tests: 10 compiler tests (valid compile, per-node-type file assertions, managed-marker-on-every-file, **determinism** via double-compile equality check, invalid-process refusal, and **real `php -l` syntax validation** run against every generated file), 5 preview-token signer tests (sign/verify round-trip, tampered signature, wrong key, malformed token, expired token), 4 preview-endpoint feature tests (valid process returns files+token, preview never touches disk, invalid process returns no token, missing process 404) — 110/110 total passing, PHPStan level 8 clean, Pint clean
- [x] Verified live over real HTTP end-to-end, including a caught-and-fixed real bug (see below)

**Bugs found and fixed while building this milestone** (both caught by writing a realistic multi-hop demo process rather than a trivial one-hop test):

1. `RouteRule::validate()` checked only a *direct* edge from route to controller, incorrectly rejecting the spec's own `Route → Form Request → Controller` pattern as "missing a controller connection." Fixed to use `ProcessGraph::reachableFrom()`.
2. `ProcessCompiler` had the identical one-hop bug when resolving which controller (and which of its methods) each route's generated `Route::` statement should call — it silently fell back to a hardcoded `'index'` method name and, once fixed to look up the right controller, initially still failed for multi-hop chains until the controller-resolution loop was also switched to graph reachability instead of `incomingEdges()`.

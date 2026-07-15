# Milestone 3: Process Definition Domain — backend complete (frontend editor deferred to Milestone 4)

- [x] Domain value objects: `NodePosition`, `ProcessNode`, `ProcessEdge`, `ProcessMetadata`, `ProcessDefinition`, `ProcessCollection` — all immutable, `fromArray()`/`toArray()` hydration, matching the JSON schema in spec section 12
- [x] Structural validation at hydration time: required name/kebab-case slug, unknown node types rejected, duplicate node/edge ids rejected, edges referencing missing nodes rejected — all via `InvalidProcessDefinitionException`
- [x] `ProcessRepository` contract + `FileProcessRepository`: atomic writes (temp file + rename), file locking, slug- and id-based lookup, safe slug validation (`SafePath`) that rejects path traversal without ever touching the filesystem for unsafe identifiers, readable pretty-printed JSON
- [x] Process CRUD API: `GET/POST /api/processes`, `GET/PUT/DELETE /api/processes/{process}`, `POST /api/processes/{process}/duplicate` — slug-collision 409, validation 422, not-found 404, all through Laravel Form Requests and a consistent `{data, meta, errors}` envelope
- [x] Domain exception → HTTP response mapping via `ProcessBuilderException::render()` (`ProcessNotFoundException` → 404, `InvalidProcessDefinitionException` → 422, `UnsafeOutputPathException` → 422)
- [x] 31 new tests (domain hydration/validation, `SafePath`, `FileProcessRepository`, CRUD endpoints incl. CSRF-protected writes) — 60/60 total passing, PHPStan level 8 clean, Pint clean
- [x] Verified live over real HTTP: create, list, and show all confirmed working end-to-end including CSRF enforcement on state-changing requests

**Deferred to Milestone 4:** the visual editor UI for creating/editing processes (React Flow canvas, palette, inspector) — this milestone covered the backend domain + API only, per the spec's own implementation order.

**Bug found and fixed:** `SafePath::resolveWithin` had a path-traversal gap — `pathinfo($filename, PATHINFO_FILENAME)` strips directory components before validating, so `'../../etc/passwd.json'` passed the "safe slug" check on its basename even though the full string contained traversal segments. Fixed by rejecting any `/` or `\` in the filename outright.

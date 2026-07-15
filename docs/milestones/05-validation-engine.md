# Milestone 5: Validation Engine — complete

- [x] `ValidationError` / `ValidationResult` DTOs matching spec section 15's JSON error shape (`code`, `message`, `nodeId`, `field`, `severity`), plus a `ValidationRule` contract — separate rule classes composed into a pipeline, not one giant validator (per spec section 15's explicit requirement)
- [x] `ProcessGraph` helper (BFS reachability, DFS cycle detection from the entry node, incoming/outgoing edge lookup) shared by multiple rules
- [x] `ConnectionMap` (PHP source of truth mirroring the frontend's `connectionRules.ts`) + `AllowedConnectionsRule`
- [x] `GraphStructureRule`: unique node/edge ids (enforced earlier at `ProcessDefinition::fromArray()` hydration), entry node required, single route-entry-node limit, orphan-node warnings, cycle detection, terminal-response warning
- [x] `RouteRule`: supported HTTP method, valid URI, valid route name, controller connection required, structured middleware values
- [x] `RouteCollisionRule`: cross-process duplicate route name detection via the repository
- [x] `ClassNameRule`: valid StudlyCase class names, reserved-keyword rejection, valid namespace syntax — applied to every class-bearing node type (controller/action/service/form request/event/job/notification/API resource)
- [x] `FormRequestRule`: rules must be structured arrays (never raw strings), explicit authorization mode, rejection of embedded PHP/`eval` in rule strings
- [x] `ValidationPipeline` orchestrator + `POST /api/processes/{process}/validate` endpoint, wired into the service provider with all six rules
- [x] Frontend: `ValidationResult`/`ValidationIssue` types, `validateProcess()` API client, `validate()` action + state on `useProcessEditorStore`, `ValidationPanel` component (errors/warnings list) wired into the editor toolbar
- [x] 31 new backend tests (per-rule unit tests + pipeline merge test + validate-endpoint feature tests) — 91/91 total passing, PHPStan level 8 clean, Pint clean
- [x] 5 new frontend tests (store validate action + ValidationPanel rendering) — 33/33 total passing, typecheck clean, production build clean

**Bug found and fixed:** `RouteRule::validateRouteNode()` checked only a *direct* edge from route to controller (via `outgoingEdges()`), incorrectly rejecting the spec's own valid `Route → Form Request → Controller` multi-hop pattern with `route.controller_missing`. Fixed using `ProcessGraph::reachableFrom()` instead of one-hop `incomingEdges()`/`outgoingEdges()`.

# Roadmap

## MVP (current)

Visual builder for HTTP-triggered Laravel processes: routes, middleware, form requests, controllers, actions, transactions, model create/update, events, jobs, API resources, JSON responses, conditions, and a terminal end node. File-based definitions, preview/diff, safe generation with backups and rollback.

## Phase 2

- Deeper AST dependency analysis (service calls, transaction blocks, resource returns).
- Import existing routes into editable managed copies.
- Listener generation.
- Notification and Mail builders.
- Advanced query builder node.
- Policy and authorization nodes.
- Custom node plugin API.
- Process templates.
- Export and import.
- Team collaboration.

## Phase 3

- Runtime workflow engine: process definitions and published versions, process instances, states and transitions.
- Human tasks, user/role assignments, approval inbox.
- Timers and retry policies.
- Workflow audit history and runtime monitoring.

## Phase 4

- BPMN import/export.
- Marketplace for node plugins.
- AI-assisted process generation.
- Process documentation generation.
- Architecture risk analysis and code-quality recommendations.

## Explicit non-goals for the MVP

Full BPMN 2.0 compatibility, persistent workflow execution instances, human task inboxes, long-running orchestration, timers/scheduled transitions, multi-tenant execution, arbitrary user-provided PHP execution, editing arbitrary existing controllers/routes, visual database schema designer, full form UI builder, SaaS cloud sync, production process monitoring.

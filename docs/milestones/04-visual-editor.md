# Milestone 4: Visual Editor — complete

- [x] TypeScript discriminated-union-style node data types (`resources/js/types/process.ts`) matching every `NodeType` from spec section 11, plus `IMPLEMENTED_NODE_TYPES` mirroring the backend's `NodeType::implemented()`
- [x] `api/processes.ts` client (list/fetch/create/update/delete/duplicate) wired to the Milestone 3 CRUD API
- [x] `useProcessEditorStore` (Zustand): nodes/edges/selection state, dirty tracking, load/save, add/update/move/remove node, add/remove edge, and a working undo/redo history stack
- [x] React Flow canvas (`ProcessCanvas`): custom node component with type-specific labels, dual success/failure handles on condition nodes, drag-from-palette-to-drop node creation, click-to-select, drag-to-move, delete-key removal, connection validation against the allowed graph (`connectionRules.ts`, mirrors spec section 24), minimap/controls/background, empty-state guidance
- [x] `NodePalette`: categorized (HTTP/Application/Data/Async/Flow Control per spec section 10), draggable for implemented types, visibly disabled for experimental/unimplemented types
- [x] `PropertyInspector`: typed forms per node type (Route: method/URI/name; Controller/Action: class/method; Event/Job/FormRequest/ApiResource: class; Response: status) — only structured fields, no raw code entry
- [x] `ProcessList` (browse/open) and `ProcessEditor` (toolbar with undo/redo/save + palette/canvas/inspector three-pane layout), tabbed into `App.tsx` alongside the existing Project Explorer
- [x] 22 new frontend tests (store: create/add/update/remove/undo/redo/save-success/save-failure; palette: implemented vs. disabled rendering; inspector: placeholder/render/edit; connection rules; process list: fetch/open/create) — 28/28 total passing, typecheck clean, production build clean
- [x] Verified live: dashboard boots and serves the built bundle over a real HTTP server

**Scope note:** auto-layout (Dagre/ELK), copy/paste, and multi-selection are deferred — the spec allows shipping the "most important" interactions first (section 10) and marking the rest for later milestones; nothing here blocks Milestone 5 (validation).

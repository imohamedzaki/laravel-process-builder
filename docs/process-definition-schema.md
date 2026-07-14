# Process Definition Schema

Process definitions are stored as formatted JSON files under `process-builder/definitions/{slug}.json` and are the source of truth (spec section 12) — not a database.

## Top-level shape

```json
{
    "schemaVersion": "1.0",
    "id": "01JPROCESS123",
    "name": "Create Order",
    "slug": "create-order",
    "description": "Creates a new customer order.",
    "version": 1,
    "status": "draft",
    "entryNodeId": "route_01",
    "nodes": [ /* ProcessNode[] */ ],
    "edges": [ /* ProcessEdge[] */ ],
    "metadata": {
        "createdAt": "ISO-8601 date",
        "updatedAt": "ISO-8601 date",
        "generatedAt": null,
        "generatorVersion": "package version"
    }
}
```

| Field | Type | Notes |
|---|---|---|
| `schemaVersion` | string | Used to select a migration path if the schema changes. |
| `id` | string (ULID) | Immutable once created. |
| `slug` | string | Kebab-case, unique, used as the filename and in URLs. Validated against path traversal. |
| `status` | `draft \| validated \| generated \| archived` | See `ProcessStatus` enum. |
| `version` | int | Incremented on every save; used to detect definition drift between preview and generate. |
| `entryNodeId` | string | Must reference a node id present in `nodes`. |

## Node shape

```json
{
    "id": "route_01",
    "type": "route",
    "position": { "x": 120, "y": 180 },
    "data": { "...": "type-specific fields, see NodeType enum" }
}
```

`type` must be one of the `NodeType` enum values. `data` is validated per-type by that type's `NodeHandler::validate()`.

## Edge shape

```json
{
    "id": "edge_01",
    "source": "route_01",
    "sourceHandle": "success",
    "target": "request_01",
    "targetHandle": "input",
    "label": null
}
```

`sourceHandle`/`targetHandle` distinguish success/failure/exception branches (e.g. a `condition` node has `success` and `failure` source handles). See `docs/idea.md` section 24 for the enforced connection map.

## Serialization guarantees

- Keys are ordered deterministically and the file is pretty-printed so Git diffs stay readable.
- Re-saving an unchanged process produces a byte-identical file.
- `FileProcessRepository` validates the slug, uses file locks, and writes atomically (temp file + rename).

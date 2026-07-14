# Adding a new node type

Node behavior is never implemented as a central `switch` statement. Each node type gets its own class implementing `NodeHandler`, registered into `NodeHandlerRegistry` via the container.

## Steps

1. Add the case to `MohamedZaki\LaravelProcessBuilder\Enums\NodeType` (and, if it should ship enabled in the MVP, add it to `NodeType::implemented()`).
2. Create a DTO for the node's `data` payload under `src/DTO/` (immutable, constructor-promoted, strictly typed).
3. Implement `MohamedZaki\LaravelProcessBuilder\Contracts\NodeHandler`:

```php
final class MyNodeHandler implements NodeHandler
{
    public function type(): NodeType { return NodeType::MyType; }

    public function validate(ProcessNode $node, ProcessDefinition $process): ValidationResult { /* ... */ }

    public function normalize(ProcessNode $node): ProcessNode { /* ... */ }

    public function compile(ProcessNode $node, CompilationContext $context): CompiledNode { /* ... */ }
}
```

4. Bind it as a tagged service in the service provider so `NodeHandlerRegistry` can resolve it by `NodeType`.
5. If the node produces a generated file, add a compiler service under `src/Compilation/` and a stub under `resources/stubs/`.
6. Add the allowed source/target connections for the new type to the connection map (spec section 24) — enforced in `src/Graph`.
7. Add the frontend node component under `resources/js/nodes/`, add it to the discriminated `ProcessNode` union in `resources/js/types/`, and add it to the palette.
8. Write unit tests for the handler (`validate`, `normalize`, `compile`) and a snapshot test for generated output.

## Rules

- No raw executable PHP from node `data` — only structured fields the compiler renders into templates.
- Keep the handler focused: one node type, one class.
- Mark unimplemented/experimental types clearly in the palette rather than pretending they work.

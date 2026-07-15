import { useProcessEditorStore } from '@/stores/useProcessEditorStore';

export function PropertyInspector(): JSX.Element {
    const { nodes, selectedNodeId, updateNodeData } = useProcessEditorStore();

    const node = nodes.find((candidate) => candidate.id === selectedNodeId) ?? null;

    if (node === null) {
        return (
            <aside className="pb-inspector" aria-label="Property inspector">
                <p className="pb-inspector-empty">Select a node to edit its properties.</p>
            </aside>
        );
    }

    function handleChange(field: string, value: string): void {
        if (node !== null) {
            updateNodeData(node.id, { [field]: value });
        }
    }

    return (
        <aside className="pb-inspector" aria-label="Property inspector">
            <h3>{node.type}</h3>

            {node.type === 'route' && (
                <>
                    <label htmlFor="inspector-method">
                        HTTP method
                        <select
                            id="inspector-method"
                            value={(node.data.method as string) ?? 'GET'}
                            onChange={(event) => handleChange('method', event.target.value)}
                        >
                            {['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].map((method) => (
                                <option key={method} value={method}>
                                    {method}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label htmlFor="inspector-uri">
                        URI
                        <input
                            id="inspector-uri"
                            type="text"
                            value={(node.data.uri as string) ?? ''}
                            onChange={(event) => handleChange('uri', event.target.value)}
                        />
                    </label>
                    <label htmlFor="inspector-name">
                        Route name
                        <input
                            id="inspector-name"
                            type="text"
                            value={(node.data.name as string) ?? ''}
                            onChange={(event) => handleChange('name', event.target.value)}
                        />
                    </label>
                </>
            )}

            {(node.type === 'controller' || node.type === 'action') && (
                <>
                    <label htmlFor="inspector-class">
                        Class name
                        <input
                            id="inspector-class"
                            type="text"
                            value={(node.data.class as string) ?? ''}
                            onChange={(event) => handleChange('class', event.target.value)}
                        />
                    </label>
                    <label htmlFor="inspector-method-name">
                        Method name
                        <input
                            id="inspector-method-name"
                            type="text"
                            value={(node.data.method as string) ?? ''}
                            onChange={(event) => handleChange('method', event.target.value)}
                        />
                    </label>
                </>
            )}

            {(node.type === 'event' || node.type === 'job' || node.type === 'form_request' || node.type === 'api_resource') && (
                <label htmlFor="inspector-generic-class">
                    Class name
                    <input
                        id="inspector-generic-class"
                        type="text"
                        value={(node.data.class as string) ?? ''}
                        onChange={(event) => handleChange('class', event.target.value)}
                    />
                </label>
            )}

            {node.type === 'response' && (
                <label htmlFor="inspector-status">
                    HTTP status
                    <input
                        id="inspector-status"
                        type="number"
                        value={(node.data.status as number) ?? 200}
                        onChange={(event) => handleChange('status', event.target.value)}
                    />
                </label>
            )}
        </aside>
    );
}

import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import { Icon } from '@/components/Icon';
import { NODE_ICONS, NODE_LABELS } from '@/palette/NodePalette';

export function PropertyInspector(): JSX.Element {
    const { nodes, lanes, selectedNodeId, updateNodeData, assignNodeToLane, removeNode } = useProcessEditorStore();

    const node = nodes.find((candidate) => candidate.id === selectedNodeId) ?? null;

    if (node === null) {
        return (
            <aside className="pb-inspector" aria-label="Property inspector">
                <header><span className="pb-eyebrow">Inspector</span><h2>Properties</h2></header>
                <div className="pb-inspector-empty"><span><Icon name="settings" /></span><strong>Nothing selected</strong><p>Select a node to edit its properties.</p></div>
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
            <header className="pb-inspector-heading"><span className={`pb-palette-symbol pb-palette-symbol--${node.type}`}><Icon name={NODE_ICONS[node.type]} /></span><div><span className="pb-eyebrow">Selected component</span><h2>{NODE_LABELS[node.type]}</h2></div>{node.type !== 'start' ? <button type="button" className="pb-icon-button pb-icon-button--danger" onClick={() => removeNode(node.id)} aria-label={`Delete ${NODE_LABELS[node.type]}`}><Icon name="x" /></button> : <span className="pb-start-lock"><Icon name="shield" /></span>}</header>
            <div className="pb-inspector-id">{node.id}</div>

            {lanes.length > 0 && (
                <label htmlFor="inspector-lane">
                    Lane
                    <select
                        id="inspector-lane"
                        value={(node.data.laneId as string) ?? ''}
                        onChange={(event) => assignNodeToLane(node.id, event.target.value || null)}
                    >
                        <option value="">Unassigned</option>
                        {[...lanes]
                            .sort((a, b) => a.order - b.order)
                            .map((lane) => (
                                <option key={lane.id} value={lane.id}>
                                    {lane.name}
                                </option>
                            ))}
                    </select>
                </label>
            )}

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
            {node.type === 'middleware' && (
                <label htmlFor="inspector-middleware">Middleware stack<input id="inspector-middleware" type="text" value={Array.isArray(node.data.middleware) ? node.data.middleware.join(', ') : ''} onChange={(event) => updateNodeData(node.id, { middleware: event.target.value.split(',').map((item) => item.trim()).filter(Boolean) })} placeholder="web, auth" /></label>
            )}
            {(node.type === 'model_create' || node.type === 'model_update') && (
                <label htmlFor="inspector-model">Model class<input id="inspector-model" type="text" value={(node.data.model as string) ?? ''} onChange={(event) => handleChange('model', event.target.value)} /></label>
            )}
            {(node.type === 'condition' || node.type === 'transaction' || node.type === 'end') && (
                <label htmlFor="inspector-label">Label<input id="inspector-label" type="text" value={(node.data.label as string) ?? ''} onChange={(event) => handleChange('label', event.target.value)} /></label>
            )}
            <div className="pb-inspector-tip"><Icon name="code" /><span>Changes update the process definition now. Laravel files change only when you publish.</span></div>
        </aside>
    );
}

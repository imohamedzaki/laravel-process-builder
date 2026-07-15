import { IMPLEMENTED_NODE_TYPES, type NodeType } from '@/types/process';

interface PaletteCategory {
    label: string;
    types: NodeType[];
}

const CATEGORIES: PaletteCategory[] = [
    { label: 'HTTP', types: ['route', 'middleware', 'form_request', 'authorization', 'controller', 'api_resource', 'response'] },
    { label: 'Application', types: ['action', 'service', 'transaction', 'condition'] },
    { label: 'Data', types: ['model', 'model_create', 'model_update', 'model_delete'] },
    { label: 'Async', types: ['event', 'job', 'notification'] },
    { label: 'Flow Control', types: ['start', 'condition', 'success', 'failure', 'exception', 'end'] },
];

const NODE_LABELS: Record<NodeType, string> = {
    start: 'Start',
    route: 'Route',
    middleware: 'Middleware',
    form_request: 'Form Request',
    authorization: 'Authorization',
    controller: 'Controller',
    action: 'Action',
    service: 'Service',
    transaction: 'Transaction',
    condition: 'Condition',
    model: 'Model',
    model_create: 'Model Create',
    model_update: 'Model Update',
    model_delete: 'Model Delete',
    event: 'Event',
    job: 'Job',
    notification: 'Notification',
    api_resource: 'API Resource',
    response: 'Response',
    success: 'Success',
    failure: 'Failure',
    exception: 'Exception',
    end: 'End',
};

export const PALETTE_DRAG_MIME = 'application/x-process-builder-node-type';

export function NodePalette(): JSX.Element {
    function handleDragStart(event: React.DragEvent<HTMLButtonElement>, nodeType: NodeType): void {
        event.dataTransfer.setData(PALETTE_DRAG_MIME, nodeType);
        event.dataTransfer.effectAllowed = 'move';
    }

    return (
        <aside className="pb-palette" aria-label="Node palette">
            {CATEGORIES.map((category) => (
                <div key={category.label} className="pb-palette-category">
                    <h3>{category.label}</h3>
                    <div className="pb-palette-items">
                        {category.types.map((nodeType) => {
                            const isImplemented = IMPLEMENTED_NODE_TYPES.includes(nodeType);

                            return (
                                <button
                                    key={nodeType}
                                    type="button"
                                    draggable={isImplemented}
                                    disabled={!isImplemented}
                                    onDragStart={(event) => handleDragStart(event, nodeType)}
                                    className={`pb-palette-item${isImplemented ? '' : ' pb-palette-item--disabled'}`}
                                    title={isImplemented ? undefined : 'Not yet implemented'}
                                >
                                    {NODE_LABELS[nodeType]}
                                </button>
                            );
                        })}
                    </div>
                </div>
            ))}
        </aside>
    );
}

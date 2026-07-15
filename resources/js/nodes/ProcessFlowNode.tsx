import { Handle, Position, type NodeProps } from '@xyflow/react';
import { IMPLEMENTED_NODE_TYPES, type NodeType } from '@/types/process';

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

export interface ProcessFlowNodeData extends Record<string, unknown> {
    nodeType: NodeType;
    summary?: string;
}

export function ProcessFlowNode({ data, selected }: NodeProps): JSX.Element {
    const nodeData = data as ProcessFlowNodeData;
    const nodeType = nodeData.nodeType;
    const isExperimental = !IMPLEMENTED_NODE_TYPES.includes(nodeType);
    const isCondition = nodeType === 'condition';

    return (
        <div
            className={`pb-node${selected ? ' pb-node--selected' : ''}${isExperimental ? ' pb-node--experimental' : ''}`}
            data-node-type={nodeType}
        >
            <Handle type="target" position={Position.Left} id="input" />

            <div className="pb-node-label">{NODE_LABELS[nodeType] ?? nodeType}</div>
            {nodeData.summary && <div className="pb-node-summary">{nodeData.summary}</div>}
            {isExperimental && <div className="pb-node-badge">Experimental</div>}

            {isCondition ? (
                <>
                    <Handle type="source" position={Position.Right} id="success" style={{ top: '35%' }} />
                    <Handle type="source" position={Position.Right} id="failure" style={{ top: '65%' }} />
                </>
            ) : (
                <Handle type="source" position={Position.Right} id="output" />
            )}
        </div>
    );
}

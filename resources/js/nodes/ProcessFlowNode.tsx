import { Handle, Position, type NodeProps } from '@xyflow/react';
import { Icon } from '@/components/Icon';
import { NODE_ICONS, NODE_LABELS } from '@/palette/NodePalette';
import { IMPLEMENTED_NODE_TYPES, type NodeType } from '@/types/process';

export interface ProcessFlowNodeData extends Record<string, unknown> { nodeType: NodeType; summary?: string }

function nodeSubtitle(data: ProcessFlowNodeData): string {
    if (data.summary) return data.summary;
    if (data.nodeType === 'route') return `${String(data.method ?? 'GET')} ${String(data.uri ?? '/path')}`;
    if (data.class) return String(data.class).split('\\').at(-1) ?? String(data.class);
    if (data.model) return String(data.model);
    if (data.nodeType === 'response') return `HTTP ${String(data.status ?? 200)}`;
    return data.nodeType === 'condition' ? 'Success / failure' : 'Configure component';
}

export function ProcessFlowNode({ data, selected }: NodeProps): JSX.Element {
    const nodeData = data as ProcessFlowNodeData; const nodeType = nodeData.nodeType;
    const isExperimental = !IMPLEMENTED_NODE_TYPES.includes(nodeType); const isCondition = nodeType === 'condition';
    const isTerminal = ['end', 'success', 'failure', 'exception'].includes(nodeType);
    return <div className={`pb-node pb-node--${nodeType}${selected ? ' pb-node--selected' : ''}${isExperimental ? ' pb-node--experimental' : ''}`} data-node-type={nodeType}>
        {nodeType !== 'start' && <Handle type="target" position={Position.Left} id="input" className="pb-node-handle" />}
        <span className="pb-node-icon"><Icon name={NODE_ICONS[nodeType]} /></span><span className="pb-node-content"><span className="pb-node-label">{NODE_LABELS[nodeType] ?? nodeType}</span><span className="pb-node-summary">{nodeSubtitle(nodeData)}</span></span><span className="pb-node-menu">•••</span>
        {isExperimental && <span className="pb-node-badge">Beta</span>}
        {isCondition ? <><Handle type="source" position={Position.Right} id="success" className="pb-node-handle pb-node-handle--success" style={{ top: '35%' }} /><Handle type="source" position={Position.Right} id="failure" className="pb-node-handle pb-node-handle--failure" style={{ top: '68%' }} /></> : !isTerminal && <Handle type="source" position={Position.Right} id="output" className="pb-node-handle" />}
    </div>;
}

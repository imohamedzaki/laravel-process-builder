import { useCallback, useMemo, useState } from 'react';
import { Background, BackgroundVariant, ConnectionMode, Controls, MarkerType, MiniMap, ReactFlow, ReactFlowProvider, useReactFlow, type Connection, type Edge, type FinalConnectionState, type Node, type NodeTypes, type XYPosition } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { allowedTargetTypes, isConnectionAllowed, shouldReverseConnection } from '@/canvas/connectionRules';
import { buildParticipantLayouts, constrainPositionToParticipant, PARTICIPANT_PADDING, participantForY } from '@/canvas/participantLayout';
import { SwimlaneHeaders } from '@/canvas/SwimlaneHeaders';
import { Icon } from '@/components/Icon';
import { LaneBandNode, LANE_BAND_WIDTH, type LaneBandNodeData } from '@/nodes/LaneBandNode';
import { ProcessFlowNode, type ProcessFlowNodeData } from '@/nodes/ProcessFlowNode';
import { NODE_ICONS, NODE_LABELS, PALETTE_DRAG_MIME } from '@/palette/NodePalette';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { NodeType } from '@/types/process';

const NODE_TYPES: NodeTypes = { processNode: ProcessFlowNode, laneBandNode: LaneBandNode };
let nodeIdCounter = 0;
function nextNodeId(type: NodeType): string { nodeIdCounter += 1; return `${type}_${Date.now()}_${nodeIdCounter}`; }
function defaultData(type: NodeType): Record<string, unknown> {
    if (type === 'route') return { method: 'GET', uri: '/path', name: 'route.name' };
    if (type === 'middleware') return { middleware: ['web'] };
    if (type === 'form_request') return { class: 'App\\Http\\Requests\\ProcessRequest', rules: {} };
    if (type === 'controller' || type === 'action') return { class: type === 'controller' ? 'App\\Http\\Controllers\\ProcessController' : 'App\\Actions\\ProcessAction', method: 'handle' };
    if (type === 'response') return { responseType: 'json', status: 200 };
    if (type === 'condition') return { label: 'Decision' };
    if (type === 'model_create' || type === 'model_update') return { model: 'App\\Models\\Model' };
    if (['event', 'job', 'api_resource'].includes(type)) return { class: `App\\${NODE_LABELS[type].replaceAll(' ', '')}` };
    return {};
}

interface QuickAddState { sourceId: string; sourceHandle: string | null; screen: XYPosition; flow: XYPosition }

function CanvasInner(): JSX.Element {
    const { screenToFlowPosition } = useReactFlow();
    const { nodes: processNodes, edges: processEdges, participants, selectedNodeId, addNode, moveNode, selectNode, removeNode, addEdge, removeEdge, assignNodeToParticipant } = useProcessEditorStore();
    const [quickAdd, setQuickAdd] = useState<QuickAddState | null>(null);
    const sortedParticipants = useMemo(() => [...participants].sort((a, b) => a.order - b.order), [participants]);
    const participantLayouts = useMemo(() => buildParticipantLayouts(sortedParticipants, processNodes), [sortedParticipants, processNodes]);
    const laneBandNodes: Node[] = useMemo(() => participantLayouts.map((layout) => { const participant = sortedParticipants.find((item) => item.id === layout.id)!; return { id: `participant-band_${participant.id}`, type: 'laneBandNode', position: { x: -LANE_BAND_WIDTH / 2, y: layout.top }, draggable: false, selectable: false, deletable: false, zIndex: -1, data: { name: participant.name, color: participant.color, height: layout.height } satisfies LaneBandNodeData }; }), [participantLayouts, sortedParticipants]);
    const flowNodes: Node[] = useMemo(() => [...laneBandNodes, ...processNodes.map((node) => ({ id: node.id, type: 'processNode', position: node.position, selected: node.id === selectedNodeId, deletable: node.type !== 'start', data: { nodeType: node.type, ...node.data } satisfies ProcessFlowNodeData }))], [laneBandNodes, processNodes, selectedNodeId]);
    const flowEdges: Edge[] = useMemo(() => processEdges.map((edge) => ({ id: edge.id, source: edge.source, sourceHandle: edge.sourceHandle ?? undefined, target: edge.target, targetHandle: edge.targetHandle ?? undefined, label: edge.label ?? undefined, type: 'smoothstep', markerEnd: { type: MarkerType.ArrowClosed }, className: 'pb-flow-edge' })), [processEdges]);

    const placementFor = useCallback((position: XYPosition) => {
        const layout = participantForY(participantLayouts, position.y + 40);
        if (!layout) return { position, participantId: null };
        const constrained = participantLayouts.length === 1
            ? { ...position, y: Math.max(PARTICIPANT_PADDING, position.y) }
            : constrainPositionToParticipant(position, layout);
        return { position: constrained, participantId: layout.id };
    }, [participantLayouts]);
    const handleNodeDragStop = useCallback((_event: unknown, node: Node) => { const placement = placementFor(node.position); moveNode(node.id, placement.position); assignNodeToParticipant(node.id, placement.participantId); }, [moveNode, assignNodeToParticipant, placementFor]);
    const handleConnect = useCallback((connection: Connection) => {
        if (!connection.source || !connection.target || connection.source === connection.target) return;
        const source = processNodes.find((node) => node.id === connection.source);
        const target = processNodes.find((node) => node.id === connection.target);
        if (!source || !target) return;
        if (shouldReverseConnection(source.type, target.type) && isConnectionAllowed(target.type, source.type, connection.targetHandle)) {
            addEdge({ id: `edge_${Date.now()}`, source: connection.target, sourceHandle: target.type === 'condition' ? connection.targetHandle : 'output', target: connection.source, targetHandle: 'input', label: null });
        } else if (isConnectionAllowed(source.type, target.type, connection.sourceHandle)) {
            addEdge({ id: `edge_${Date.now()}`, source: connection.source, sourceHandle: connection.sourceHandle, target: connection.target, targetHandle: connection.targetHandle, label: null });
        } else if (isConnectionAllowed(target.type, source.type, connection.targetHandle)) {
            addEdge({ id: `edge_${Date.now()}`, source: connection.target, sourceHandle: connection.targetHandle, target: connection.source, targetHandle: connection.sourceHandle, label: null });
        }
    }, [processNodes, addEdge]);
    const handleConnectEnd = useCallback((event: MouseEvent | TouchEvent, state: FinalConnectionState) => {
        if (state.toNode || !state.fromNode) return;
        const pointer = 'changedTouches' in event ? event.changedTouches[0] : event;
        if (!pointer) return;
        const flow = screenToFlowPosition({ x: pointer.clientX, y: pointer.clientY });
        const bounds = (event.target as Element | null)?.closest('.pb-canvas')?.getBoundingClientRect();
        setQuickAdd({ sourceId: state.fromNode.id, sourceHandle: state.fromHandle?.id ?? null, screen: { x: pointer.clientX - (bounds?.left ?? 0), y: pointer.clientY - (bounds?.top ?? 0) }, flow });
    }, [screenToFlowPosition]);
    function addConnectedNode(type: NodeType): void {
        if (!quickAdd) return; const id = nextNodeId(type);
        const placement = placementFor(quickAdd.flow);
        addNode({ id, type, position: placement.position, data: { ...defaultData(type), participantId: placement.participantId } });
        addEdge({ id: `edge_${Date.now()}`, source: quickAdd.sourceId, sourceHandle: quickAdd.sourceHandle, target: id, targetHandle: 'input', label: null });
        selectNode(id); setQuickAdd(null);
    }
    const quickTargets = quickAdd ? allowedTargetTypes(processNodes.find((node) => node.id === quickAdd.sourceId)?.type ?? 'end', quickAdd.sourceHandle) : [];
    const handleDrop = useCallback((event: React.DragEvent) => { event.preventDefault(); const type = event.dataTransfer.getData(PALETTE_DRAG_MIME) as NodeType; if (!type) return; const id = nextNodeId(type); const placement = placementFor(screenToFlowPosition({ x: event.clientX, y: event.clientY })); addNode({ id, type, position: placement.position, data: { ...defaultData(type), participantId: placement.participantId } }); selectNode(id); }, [addNode, placementFor, screenToFlowPosition, selectNode]);

    return <div className="pb-canvas" onDragOver={(event) => { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; }} onDrop={handleDrop}>
        <div className="pb-canvas-caption"><span><span className="pb-live-dot" />Design canvas</span><small>Drag to pan · Scroll to zoom · Connect handles to link</small></div>
        <ReactFlow nodes={flowNodes} edges={flowEdges} nodeTypes={NODE_TYPES} connectionMode={ConnectionMode.Loose} connectionRadius={32} onNodeDrag={(_event, node) => { const position = participantLayouts.length === 1 ? { ...node.position, y: Math.max(PARTICIPANT_PADDING, node.position.y) } : node.position; moveNode(node.id, position); }} onNodeDragStop={handleNodeDragStop} onNodeClick={(_event, node) => selectNode(node.id)} onPaneClick={() => { selectNode(null); setQuickAdd(null); }} onNodesDelete={(deleted) => deleted.forEach((node) => removeNode(node.id))} onEdgesDelete={(deleted) => deleted.forEach((edge) => removeEdge(edge.id))} onConnect={handleConnect} onConnectEnd={handleConnectEnd} isValidConnection={(connection) => { if (!connection.source || !connection.target || connection.source === connection.target) return false; const source = processNodes.find((node) => node.id === connection.source); const target = processNodes.find((node) => node.id === connection.target); return Boolean(source && target && (isConnectionAllowed(source.type, target.type, connection.sourceHandle) || isConnectionAllowed(target.type, source.type, connection.targetHandle))); }} defaultEdgeOptions={{ type: 'smoothstep', markerEnd: { type: MarkerType.ArrowClosed } }} fitView fitViewOptions={{ padding: 0.25 }}>
            <Background variant={BackgroundVariant.Dots} gap={22} size={1.2} /><Controls showInteractive={false} /><MiniMap pannable zoomable nodeStrokeWidth={3} /><SwimlaneHeaders participants={participants} layouts={participantLayouts} />
        </ReactFlow>
        {processNodes.length === 0 && <div className="pb-canvas-empty-state"><span className="pb-empty-orbit"><Icon name="git-branch" /></span><strong>Start with a route</strong><span>Drag a component here to draw your Laravel request pipeline.</span></div>}
        {quickAdd && <div className="pb-quick-add" style={{ left: quickAdd.screen.x, top: quickAdd.screen.y }}><header><span>Add next component</span><button type="button" onClick={() => setQuickAdd(null)} aria-label="Close"><Icon name="x" /></button></header>{quickTargets.length ? quickTargets.map((type) => <button type="button" key={type} onClick={() => addConnectedNode(type)}><span className={`pb-palette-symbol pb-palette-symbol--${type}`}><Icon name={NODE_ICONS[type]} /></span><span>{NODE_LABELS[type]}<small>Connect automatically</small></span></button>) : <p>No compatible next components.</p>}</div>}
    </div>;
}

export function ProcessCanvas(): JSX.Element { return <ReactFlowProvider><CanvasInner /></ReactFlowProvider>; }

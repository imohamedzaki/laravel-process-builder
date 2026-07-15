import { useCallback, useMemo } from 'react';
import {
    Background,
    Controls,
    MiniMap,
    ReactFlow,
    type Connection,
    type Edge,
    type Node,
    type NodeTypes,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { isConnectionAllowed } from '@/canvas/connectionRules';
import { PALETTE_DRAG_MIME } from '@/palette/NodePalette';
import { ProcessFlowNode, type ProcessFlowNodeData } from '@/nodes/ProcessFlowNode';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { NodeType } from '@/types/process';

const NODE_TYPES: NodeTypes = {
    processNode: ProcessFlowNode,
};

let nodeIdCounter = 0;

function nextNodeId(nodeType: NodeType): string {
    nodeIdCounter += 1;

    return `${nodeType}_${Date.now()}_${nodeIdCounter}`;
}

export function ProcessCanvas(): JSX.Element {
    const {
        nodes: processNodes,
        edges: processEdges,
        selectedNodeId,
        addNode,
        moveNode,
        selectNode,
        removeNode,
        addEdge: addProcessEdge,
        removeEdge,
    } = useProcessEditorStore();

    const flowNodes: Node[] = useMemo(
        () =>
            processNodes.map((node) => ({
                id: node.id,
                type: 'processNode',
                position: node.position,
                selected: node.id === selectedNodeId,
                data: { nodeType: node.type, ...node.data } satisfies ProcessFlowNodeData,
            })),
        [processNodes, selectedNodeId],
    );

    const flowEdges: Edge[] = useMemo(
        () =>
            processEdges.map((edge) => ({
                id: edge.id,
                source: edge.source,
                sourceHandle: edge.sourceHandle ?? undefined,
                target: edge.target,
                targetHandle: edge.targetHandle ?? undefined,
                label: edge.label ?? undefined,
            })),
        [processEdges],
    );

    const handleNodeDragStop = useCallback(
        (_event: unknown, node: Node) => {
            moveNode(node.id, node.position);
        },
        [moveNode],
    );

    const handleNodeClick = useCallback(
        (_event: unknown, node: Node) => {
            selectNode(node.id);
        },
        [selectNode],
    );

    const handlePaneClick = useCallback(() => {
        selectNode(null);
    }, [selectNode]);

    const handleNodesDelete = useCallback(
        (deleted: Node[]) => {
            deleted.forEach((node) => removeNode(node.id));
        },
        [removeNode],
    );

    const handleEdgesDelete = useCallback(
        (deleted: Edge[]) => {
            deleted.forEach((edge) => removeEdge(edge.id));
        },
        [removeEdge],
    );

    const handleConnect = useCallback(
        (connection: Connection) => {
            const sourceNode = processNodes.find((node) => node.id === connection.source);
            const targetNode = processNodes.find((node) => node.id === connection.target);

            if (!sourceNode || !targetNode) {
                return;
            }

            if (!isConnectionAllowed(sourceNode.type, targetNode.type, connection.sourceHandle)) {
                return;
            }

            addProcessEdge({
                id: `edge_${Date.now()}`,
                source: connection.source ?? '',
                sourceHandle: connection.sourceHandle,
                target: connection.target ?? '',
                targetHandle: connection.targetHandle,
                label: null,
            });
        },
        [processNodes, addProcessEdge],
    );

    const handleDragOver = useCallback((event: React.DragEvent) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }, []);

    const handleDrop = useCallback(
        (event: React.DragEvent) => {
            event.preventDefault();

            const nodeType = event.dataTransfer.getData(PALETTE_DRAG_MIME) as NodeType;

            if (!nodeType) {
                return;
            }

            const bounds = event.currentTarget.getBoundingClientRect();

            addNode({
                id: nextNodeId(nodeType),
                type: nodeType,
                position: {
                    x: event.clientX - bounds.left,
                    y: event.clientY - bounds.top,
                },
                data: {},
            });
        },
        [addNode],
    );

    return (
        <div className="pb-canvas" onDragOver={handleDragOver} onDrop={handleDrop}>
            <ReactFlow
                nodes={flowNodes}
                edges={flowEdges}
                nodeTypes={NODE_TYPES}
                onNodeDragStop={handleNodeDragStop}
                onNodeClick={handleNodeClick}
                onPaneClick={handlePaneClick}
                onNodesDelete={handleNodesDelete}
                onEdgesDelete={handleEdgesDelete}
                onConnect={handleConnect}
                fitView
            >
                <Background />
                <Controls />
                <MiniMap />
            </ReactFlow>

            {processNodes.length === 0 && (
                <div className="pb-canvas-empty-state">
                    Drag a node from the palette to start building your process.
                </div>
            )}
        </div>
    );
}

import { create } from 'zustand';
import { createProcess, fetchProcess, updateProcess, validateProcess } from '@/api/processes';
import type { ProcessDefinition, ProcessEdge, ProcessLane, ProcessNode, ValidationResult } from '@/types/process';
import { shouldReverseConnection } from '@/canvas/connectionRules';

interface HistoryEntry {
    nodes: ProcessNode[];
    edges: ProcessEdge[];
    lanes: ProcessLane[];
}

interface ProcessEditorState {
    process: ProcessDefinition | null;
    nodes: ProcessNode[];
    edges: ProcessEdge[];
    lanes: ProcessLane[];
    selectedNodeId: string | null;
    isDirty: boolean;
    isLoading: boolean;
    isSaving: boolean;
    isValidating: boolean;
    error: string | null;
    validation: ValidationResult | null;

    past: HistoryEntry[];
    future: HistoryEntry[];

    load: (idOrSlug: string) => Promise<void>;
    loadFromDefinition: (process: ProcessDefinition) => void;
    createDraft: (name: string, slug: string, description?: string | null) => void;
    save: () => Promise<void>;
    validate: () => Promise<void>;

    addNode: (node: ProcessNode) => void;
    updateNodeData: (nodeId: string, data: Record<string, unknown>) => void;
    moveNode: (nodeId: string, position: { x: number; y: number }) => void;
    removeNode: (nodeId: string) => void;
    selectNode: (nodeId: string | null) => void;

    addEdge: (edge: ProcessEdge) => void;
    removeEdge: (edgeId: string) => void;

    addLane: (lane: ProcessLane) => void;
    updateLane: (laneId: string, changes: Partial<Omit<ProcessLane, 'id'>>) => void;
    removeLane: (laneId: string) => void;
    assignNodeToLane: (nodeId: string, laneId: string | null) => void;

    undo: () => void;
    redo: () => void;
}

function snapshot(state: ProcessEditorState): HistoryEntry {
    return { nodes: state.nodes, edges: state.edges, lanes: state.lanes };
}

function withRequiredStart(process: ProcessDefinition): ProcessDefinition {
    const base = process.schemaVersion === '1.2' && process.guard
        ? process
        : { ...process, schemaVersion: '1.2', guard: process.guard || process.slug };
    const nodeTypes = new Map(base.nodes.map((node) => [node.id, node.type]));
    const normalizedEdges = base.edges.map((edge) => {
        const sourceType = nodeTypes.get(edge.source);
        const targetType = nodeTypes.get(edge.target);
        if (!sourceType || !targetType || !shouldReverseConnection(sourceType, targetType)) return edge;
        return { ...edge, source: edge.target, sourceHandle: targetType === 'condition' ? edge.targetHandle : 'output', target: edge.source, targetHandle: 'input' };
    });
    const normalizedBase = normalizedEdges.some((edge, index) => edge !== base.edges[index]) ? { ...base, edges: normalizedEdges } : base;
    const existingStart = normalizedBase.nodes.find((node) => node.type === 'start');
    if (existingStart) {
        return normalizedBase.entryNodeId === existingStart.id ? normalizedBase : { ...normalizedBase, entryNodeId: existingStart.id };
    }

    const previousEntry = process.nodes.find((node) => node.id === process.entryNodeId) ?? process.nodes[0];
    const startId = `start_${process.slug}`;
    const start: ProcessNode = {
        id: startId,
        type: 'start',
        position: previousEntry ? { x: previousEntry.position.x - 230, y: previousEntry.position.y } : { x: 80, y: 180 },
        data: { label: `${process.guard || process.slug} guard` },
    };
    const edge = previousEntry ? [{ id: `edge_${startId}_${previousEntry.id}`, source: startId, sourceHandle: 'output', target: previousEntry.id, targetHandle: 'input', label: null }] : [];

    return { ...normalizedBase, entryNodeId: startId, nodes: [start, ...process.nodes], edges: [...edge, ...normalizedEdges] };
}

export const useProcessEditorStore = create<ProcessEditorState>((set, get) => ({
    process: null,
    nodes: [],
    edges: [],
    lanes: [],
    selectedNodeId: null,
    isDirty: false,
    isLoading: false,
    isSaving: false,
    isValidating: false,
    error: null,
    validation: null,
    past: [],
    future: [],

    load: async (idOrSlug) => {
        set({ isLoading: true, error: null });

        try {
            const fetched = await fetchProcess(idOrSlug);
            const process = withRequiredStart(fetched);
            set({
                process,
                nodes: process.nodes,
                edges: process.edges,
                lanes: process.lanes,
                isLoading: false,
                isDirty: process !== fetched,
                past: [],
                future: [],
                selectedNodeId: null,
            });
        } catch {
            set({ isLoading: false, error: 'Unable to load the process.' });
        }
    },

    loadFromDefinition: (process) => {
        const normalized = withRequiredStart(process);
        set({
            process: normalized,
            nodes: normalized.nodes,
            edges: normalized.edges,
            lanes: normalized.lanes,
            isDirty: normalized !== process,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    createDraft: (name, slug, description = null) => {
        const now = new Date().toISOString();
        const startId = `start_${slug}`;
        const startNode: ProcessNode = { id: startId, type: 'start', position: { x: 80, y: 180 }, data: { label: `${slug} guard` } };

        set({
            process: {
                schemaVersion: '1.2',
                id: '',
                name,
                slug,
                guard: slug,
                description,
                version: 0,
                status: 'draft',
                entryNodeId: startId,
                nodes: [startNode],
                edges: [],
                lanes: [],
                metadata: { createdAt: now, updatedAt: now, generatedAt: null, generatorVersion: null },
            },
            nodes: [startNode],
            edges: [],
            lanes: [],
            isDirty: false,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    save: async () => {
        const { process, nodes, edges, lanes } = get();

        if (process === null) {
            return;
        }

        set({ isSaving: true, error: null });

        const payload = {
            name: process.name,
            slug: process.slug,
            guard: process.guard ?? process.slug,
            description: process.description,
            entryNodeId: process.entryNodeId,
            nodes,
            edges,
            lanes,
        };

        try {
            const saved = process.id === ''
                ? await createProcess(payload)
                : await updateProcess(process.slug, payload);

            set({
                process: saved,
                nodes: saved.nodes,
                edges: saved.edges,
                lanes: saved.lanes,
                isSaving: false,
                isDirty: false,
            });
        } catch {
            set({ isSaving: false, error: 'Unable to save the process.' });
        }
    },

    validate: async () => {
        const { process } = get();

        if (process === null || process.id === '') {
            set({ error: 'Save the process before validating it.' });

            return;
        }

        set({ isValidating: true, error: null });

        try {
            const validation = await validateProcess(process.slug);
            set({ validation, isValidating: false });
        } catch {
            set({ isValidating: false, error: 'Unable to validate the process.' });
        }
    },

    addNode: (node) => {
        const state = get();
        set({
            nodes: [...state.nodes, node],
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    updateNodeData: (nodeId, data) => {
        const state = get();
        set({
            nodes: state.nodes.map((node) =>
                node.id === nodeId ? { ...node, data: { ...node.data, ...data } } : node,
            ),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    moveNode: (nodeId, position) => {
        const state = get();
        set({
            nodes: state.nodes.map((node) => (node.id === nodeId ? { ...node, position } : node)),
            isDirty: true,
        });
    },

    removeNode: (nodeId) => {
        const state = get();
        if (state.nodes.find((node) => node.id === nodeId)?.type === 'start') {
            return;
        }
        set({
            nodes: state.nodes.filter((node) => node.id !== nodeId),
            edges: state.edges.filter((edge) => edge.source !== nodeId && edge.target !== nodeId),
            selectedNodeId: state.selectedNodeId === nodeId ? null : state.selectedNodeId,
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    selectNode: (nodeId) => {
        set({ selectedNodeId: nodeId });
    },

    addEdge: (edge) => {
        const state = get();
        set({
            edges: [...state.edges, edge],
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    removeEdge: (edgeId) => {
        const state = get();
        set({
            edges: state.edges.filter((edge) => edge.id !== edgeId),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    addLane: (lane) => {
        const state = get();
        set({
            lanes: [...state.lanes, lane],
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    updateLane: (laneId, changes) => {
        const state = get();
        set({
            lanes: state.lanes.map((lane) => (lane.id === laneId ? { ...lane, ...changes } : lane)),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    removeLane: (laneId) => {
        const state = get();
        set({
            lanes: state.lanes.filter((lane) => lane.id !== laneId),
            nodes: state.nodes.map((node) =>
                node.data.laneId === laneId ? { ...node, data: { ...node.data, laneId: null } } : node,
            ),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    assignNodeToLane: (nodeId, laneId) => {
        const state = get();
        set({
            nodes: state.nodes.map((node) =>
                node.id === nodeId ? { ...node, data: { ...node.data, laneId } } : node,
            ),
            isDirty: true,
        });
    },

    undo: () => {
        const state = get();
        const previous = state.past.at(-1);

        if (!previous) {
            return;
        }

        set({
            nodes: previous.nodes,
            edges: previous.edges,
            lanes: previous.lanes,
            past: state.past.slice(0, -1),
            future: [snapshot(state), ...state.future],
            isDirty: true,
        });
    },

    redo: () => {
        const state = get();
        const next = state.future[0];

        if (!next) {
            return;
        }

        set({
            nodes: next.nodes,
            edges: next.edges,
            lanes: next.lanes,
            past: [...state.past, snapshot(state)],
            future: state.future.slice(1),
            isDirty: true,
        });
    },
}));

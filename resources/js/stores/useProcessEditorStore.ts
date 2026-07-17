import { create } from 'zustand';
import { createProcess, fetchProcess, updateProcess, validateProcess } from '@/api/processes';
import type { ProcessDefinition, ProcessEdge, ProcessNode, ProcessParticipant, ValidationResult } from '@/types/process';
import { shouldReverseConnection } from '@/canvas/connectionRules';
import { normalizeParticipantPositions } from '@/canvas/participantLayout';

interface HistoryEntry {
    nodes: ProcessNode[];
    edges: ProcessEdge[];
    participants: ProcessParticipant[];
}

interface ProcessEditorState {
    process: ProcessDefinition | null;
    nodes: ProcessNode[];
    edges: ProcessEdge[];
    participants: ProcessParticipant[];
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

    addParticipant: (participant: ProcessParticipant) => void;
    updateParticipant: (participantId: string, changes: Partial<Omit<ProcessParticipant, 'id'>>) => void;
    removeParticipant: (participantId: string) => void;
    assignNodeToParticipant: (nodeId: string, participantId: string | null) => void;

    undo: () => void;
    redo: () => void;
}

function snapshot(state: ProcessEditorState): HistoryEntry {
    return { nodes: state.nodes, edges: state.edges, participants: state.participants };
}

function withRequiredStart(process: ProcessDefinition): ProcessDefinition {
    const base = { ...process, schemaVersion: '1.3' };
    const nodeTypes = new Map(base.nodes.map((node) => [node.id, node.type]));
    const normalizedEdges = base.edges.map((edge) => {
        const sourceType = nodeTypes.get(edge.source);
        const targetType = nodeTypes.get(edge.target);
        if (!sourceType || !targetType || !shouldReverseConnection(sourceType, targetType)) return edge;
        return { ...edge, source: edge.target, sourceHandle: targetType === 'condition' ? edge.targetHandle : 'output', target: edge.source, targetHandle: 'input' };
    });
    const normalizedBase = normalizedEdges.some((edge, index) => edge !== base.edges[index]) ? { ...base, edges: normalizedEdges } : base;
    if (normalizedBase.participants.length === 0) return normalizedBase;
    const firstParticipant = [...normalizedBase.participants].sort((a, b) => a.order - b.order)[0]!;
    const existingStart = normalizedBase.nodes.find((node) => node.type === 'start');
    if (existingStart) {
        const nodes = existingStart.data.participantId ? normalizedBase.nodes : normalizedBase.nodes.map((node) => node.id === existingStart.id ? { ...node, data: { ...node.data, participantId: firstParticipant.id } } : node);
        const processWithStart = { ...normalizedBase, nodes, entryNodeId: existingStart.id };
        return { ...processWithStart, nodes: normalizeParticipantPositions(processWithStart.participants, processWithStart.nodes) };
    }

    const previousEntry = process.nodes.find((node) => node.id === process.entryNodeId) ?? process.nodes[0];
    const startId = `start_${process.slug}`;
    const start: ProcessNode = {
        id: startId,
        type: 'start',
        position: previousEntry ? { x: previousEntry.position.x - 230, y: previousEntry.position.y } : { x: 80, y: 180 },
        data: { label: 'Start', participantId: firstParticipant.id },
    };
    const edge = previousEntry ? [{ id: `edge_${startId}_${previousEntry.id}`, source: startId, sourceHandle: 'output', target: previousEntry.id, targetHandle: 'input', label: null }] : [];

    const processWithStart = { ...normalizedBase, entryNodeId: startId, nodes: [start, ...process.nodes], edges: [...edge, ...normalizedEdges] };
    return { ...processWithStart, nodes: normalizeParticipantPositions(processWithStart.participants, processWithStart.nodes) };
}

export const useProcessEditorStore = create<ProcessEditorState>((set, get) => ({
    process: null,
    nodes: [],
    edges: [],
    participants: [],
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
                participants: process.participants,
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
            participants: normalized.participants,
            isDirty: normalized !== process,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    createDraft: (name, slug, description = null) => {
        const now = new Date().toISOString();
        set({
            process: {
                schemaVersion: '1.3',
                id: '',
                name,
                slug,
                description,
                version: 0,
                status: 'draft',
                entryNodeId: null,
                nodes: [],
                edges: [],
                participants: [],
                metadata: { createdAt: now, updatedAt: now, generatedAt: null, generatorVersion: null },
            },
            nodes: [],
            edges: [],
            participants: [],
            isDirty: false,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    save: async () => {
        const { process, nodes, edges, participants } = get();

        if (process === null) {
            return;
        }

        set({ isSaving: true, error: null });

        const payload = {
            name: process.name,
            slug: process.slug,
            description: process.description,
            entryNodeId: process.entryNodeId,
            nodes,
            edges,
            participants,
        };

        try {
            const saved = process.id === ''
                ? await createProcess(payload)
                : await updateProcess(process.slug, payload);

            set({
                process: saved,
                nodes: saved.nodes,
                edges: saved.edges,
                participants: saved.participants,
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

    addParticipant: (participant) => {
        const state = get();
        const isFirst = state.participants.length === 0;
        const existingStart = state.nodes.find((node) => node.type === 'start');
        const startId = existingStart?.id ?? `start_${state.process?.slug ?? 'process'}`;
        const startNode: ProcessNode = { id: startId, type: 'start', position: { x: 80, y: 80 }, data: { label: 'Start', participantId: participant.id } };
        const firstParticipantNodes = existingStart
            ? state.nodes.map((node) => node.id === existingStart.id ? { ...node, data: { ...node.data, participantId: participant.id } } : node)
            : [startNode, ...state.nodes];
        set({
            participants: [...state.participants, participant],
            nodes: isFirst ? firstParticipantNodes : state.nodes,
            process: isFirst && state.process ? { ...state.process, entryNodeId: startId } : state.process,
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    updateParticipant: (participantId, changes) => {
        const state = get();
        set({
            participants: state.participants.map((participant) => (participant.id === participantId ? { ...participant, ...changes } : participant)),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    removeParticipant: (participantId) => {
        const state = get();
        if (state.participants.length === 1) return;
        set({
            participants: state.participants.filter((participant) => participant.id !== participantId),
            nodes: state.nodes.map((node) =>
                node.data.participantId === participantId ? { ...node, data: { ...node.data, participantId: null } } : node,
            ),
            past: [...state.past, snapshot(state)],
            future: [],
            isDirty: true,
        });
    },

    assignNodeToParticipant: (nodeId, participantId) => {
        const state = get();
        set({
            nodes: state.nodes.map((node) =>
                node.id === nodeId ? { ...node, data: { ...node.data, participantId } } : node,
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
            participants: previous.participants,
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
            participants: next.participants,
            past: [...state.past, snapshot(state)],
            future: state.future.slice(1),
            isDirty: true,
        });
    },
}));

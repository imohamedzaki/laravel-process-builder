import { create } from 'zustand';
import { createProcess, fetchProcess, updateProcess, validateProcess } from '@/api/processes';
import type { ProcessDefinition, ProcessEdge, ProcessNode, ValidationResult } from '@/types/process';

interface HistoryEntry {
    nodes: ProcessNode[];
    edges: ProcessEdge[];
}

interface ProcessEditorState {
    process: ProcessDefinition | null;
    nodes: ProcessNode[];
    edges: ProcessEdge[];
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
    createDraft: (name: string, slug: string) => void;
    save: () => Promise<void>;
    validate: () => Promise<void>;

    addNode: (node: ProcessNode) => void;
    updateNodeData: (nodeId: string, data: Record<string, unknown>) => void;
    moveNode: (nodeId: string, position: { x: number; y: number }) => void;
    removeNode: (nodeId: string) => void;
    selectNode: (nodeId: string | null) => void;

    addEdge: (edge: ProcessEdge) => void;
    removeEdge: (edgeId: string) => void;

    undo: () => void;
    redo: () => void;
}

function snapshot(state: ProcessEditorState): HistoryEntry {
    return { nodes: state.nodes, edges: state.edges };
}

export const useProcessEditorStore = create<ProcessEditorState>((set, get) => ({
    process: null,
    nodes: [],
    edges: [],
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
            const process = await fetchProcess(idOrSlug);
            set({
                process,
                nodes: process.nodes,
                edges: process.edges,
                isLoading: false,
                isDirty: false,
                past: [],
                future: [],
                selectedNodeId: null,
            });
        } catch {
            set({ isLoading: false, error: 'Unable to load the process.' });
        }
    },

    loadFromDefinition: (process) => {
        set({
            process,
            nodes: process.nodes,
            edges: process.edges,
            isDirty: false,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    createDraft: (name, slug) => {
        const now = new Date().toISOString();

        set({
            process: {
                schemaVersion: '1.0',
                id: '',
                name,
                slug,
                description: null,
                version: 0,
                status: 'draft',
                entryNodeId: null,
                nodes: [],
                edges: [],
                metadata: { createdAt: now, updatedAt: now, generatedAt: null, generatorVersion: null },
            },
            nodes: [],
            edges: [],
            isDirty: false,
            past: [],
            future: [],
            selectedNodeId: null,
            error: null,
        });
    },

    save: async () => {
        const { process, nodes, edges } = get();

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
        };

        try {
            const saved = process.id === ''
                ? await createProcess(payload)
                : await updateProcess(process.slug, payload);

            set({ process: saved, nodes: saved.nodes, edges: saved.edges, isSaving: false, isDirty: false });
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

    undo: () => {
        const state = get();
        const previous = state.past.at(-1);

        if (!previous) {
            return;
        }

        set({
            nodes: previous.nodes,
            edges: previous.edges,
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
            past: [...state.past, snapshot(state)],
            future: state.future.slice(1),
            isDirty: true,
        });
    },
}));

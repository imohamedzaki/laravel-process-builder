import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import * as processApi from '@/api/processes';
import type { ProcessNode } from '@/types/process';

function makeNode(id: string): ProcessNode {
    return { id, type: 'action', position: { x: 0, y: 0 }, data: {} };
}

describe('useProcessEditorStore', () => {
    beforeEach(() => {
        useProcessEditorStore.setState({
            process: null,
            nodes: [],
            edges: [],
            selectedNodeId: null,
            isDirty: false,
            isLoading: false,
            isSaving: false,
            error: null,
            past: [],
            future: [],
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('creates a draft process', () => {
        useProcessEditorStore.getState().createDraft('Create Order', 'create-order');

        const state = useProcessEditorStore.getState();
        expect(state.process?.name).toBe('Create Order');
        expect(state.process?.slug).toBe('create-order');
        expect(state.isDirty).toBe(false);
    });

    it('adds a node and marks the state dirty', () => {
        useProcessEditorStore.getState().addNode(makeNode('n1'));

        const state = useProcessEditorStore.getState();
        expect(state.nodes).toHaveLength(1);
        expect(state.isDirty).toBe(true);
    });

    it('selects and deselects a node', () => {
        useProcessEditorStore.getState().selectNode('n1');
        expect(useProcessEditorStore.getState().selectedNodeId).toBe('n1');

        useProcessEditorStore.getState().selectNode(null);
        expect(useProcessEditorStore.getState().selectedNodeId).toBeNull();
    });

    it('updates node data by merging fields', () => {
        useProcessEditorStore.getState().addNode(makeNode('n1'));
        useProcessEditorStore.getState().updateNodeData('n1', { uri: '/orders' });

        const node = useProcessEditorStore.getState().nodes[0];
        expect(node?.data.uri).toBe('/orders');
    });

    it('removes a node and any edges attached to it', () => {
        useProcessEditorStore.getState().addNode(makeNode('n1'));
        useProcessEditorStore.getState().addNode(makeNode('n2'));
        useProcessEditorStore.getState().addEdge({ id: 'e1', source: 'n1', target: 'n2' });

        useProcessEditorStore.getState().removeNode('n1');

        const state = useProcessEditorStore.getState();
        expect(state.nodes).toHaveLength(1);
        expect(state.edges).toHaveLength(0);
    });

    it('undoes and redoes node additions', () => {
        useProcessEditorStore.getState().addNode(makeNode('n1'));
        useProcessEditorStore.getState().addNode(makeNode('n2'));

        expect(useProcessEditorStore.getState().nodes).toHaveLength(2);

        useProcessEditorStore.getState().undo();
        expect(useProcessEditorStore.getState().nodes).toHaveLength(1);

        useProcessEditorStore.getState().undo();
        expect(useProcessEditorStore.getState().nodes).toHaveLength(0);

        useProcessEditorStore.getState().redo();
        expect(useProcessEditorStore.getState().nodes).toHaveLength(1);
    });

    it('saves a new draft via createProcess and clears the dirty flag', async () => {
        useProcessEditorStore.getState().createDraft('New', 'new');
        useProcessEditorStore.getState().addNode(makeNode('n1'));

        const saved = {
            schemaVersion: '1.0',
            id: 'ABC123',
            name: 'New',
            slug: 'new',
            description: null,
            version: 1,
            status: 'draft' as const,
            entryNodeId: null,
            nodes: [makeNode('n1')],
            edges: [],
            metadata: { createdAt: '', updatedAt: '', generatedAt: null, generatorVersion: null },
        };

        vi.spyOn(processApi, 'createProcess').mockResolvedValue(saved);

        await useProcessEditorStore.getState().save();

        const state = useProcessEditorStore.getState();
        expect(state.process?.id).toBe('ABC123');
        expect(state.isDirty).toBe(false);
        expect(state.isSaving).toBe(false);
    });

    it('sets an error when saving fails', async () => {
        useProcessEditorStore.getState().createDraft('New', 'new');

        vi.spyOn(processApi, 'createProcess').mockRejectedValue(new Error('network error'));

        await useProcessEditorStore.getState().save();

        expect(useProcessEditorStore.getState().error).toBe('Unable to save the process.');
    });

    it('requires the process to be saved before validating', async () => {
        useProcessEditorStore.getState().createDraft('New', 'new');

        await useProcessEditorStore.getState().validate();

        expect(useProcessEditorStore.getState().error).toBe('Save the process before validating it.');
    });

    it('fetches and stores the validation result for a saved process', async () => {
        useProcessEditorStore.setState({
            process: {
                schemaVersion: '1.0',
                id: 'ABC',
                name: 'Saved',
                slug: 'saved',
                description: null,
                version: 1,
                status: 'draft',
                entryNodeId: null,
                nodes: [],
                edges: [],
                metadata: { createdAt: '', updatedAt: '', generatedAt: null, generatorVersion: null },
            },
        });

        vi.spyOn(processApi, 'validateProcess').mockResolvedValue({ valid: false, errors: [], warnings: [] });

        await useProcessEditorStore.getState().validate();

        const state = useProcessEditorStore.getState();
        expect(state.validation?.valid).toBe(false);
        expect(state.isValidating).toBe(false);
    });
});

import { describe, expect, it, vi, afterEach, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProcessList } from '@/components/ProcessList';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import * as processApi from '@/api/processes';
import type { ProcessDefinition } from '@/types/process';

const existingProcess: ProcessDefinition = {
    schemaVersion: '1.0',
    id: 'ABC',
    name: 'Create Order',
    slug: 'create-order',
    description: null,
    version: 1,
    status: 'draft',
    entryNodeId: null,
    nodes: [],
    edges: [],
    lanes: [],
    metadata: { createdAt: '', updatedAt: '', generatedAt: null, generatorVersion: null },
};

describe('ProcessList', () => {
    beforeEach(() => {
        useProcessEditorStore.setState({ process: null, nodes: [], edges: [], isDirty: false });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('lists existing processes', async () => {
        vi.spyOn(processApi, 'fetchProcesses').mockResolvedValue([existingProcess]);

        render(<ProcessList />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Create Order' })).toBeInTheDocument();
        });
    });

    it('opens a process into the editor store when clicked', async () => {
        vi.spyOn(processApi, 'fetchProcesses').mockResolvedValue([existingProcess]);
        vi.spyOn(processApi, 'fetchProcess').mockResolvedValue(existingProcess);

        const user = userEvent.setup();
        render(<ProcessList />);

        await waitFor(() => screen.getByRole('button', { name: 'Create Order' }));
        await user.click(screen.getByRole('button', { name: 'Create Order' }));

        await waitFor(() => {
            expect(useProcessEditorStore.getState().process?.slug).toBe('create-order');
        });
    });

    it('creates a new draft process from the name field', async () => {
        vi.spyOn(processApi, 'fetchProcesses').mockResolvedValue([]);

        const user = userEvent.setup();
        render(<ProcessList />);

        await user.type(screen.getByLabelText('New process name'), 'Approve Leave Request');
        await user.click(screen.getByRole('button', { name: 'New Process' }));

        const state = useProcessEditorStore.getState();
        expect(state.process?.name).toBe('Approve Leave Request');
        expect(state.process?.slug).toBe('approve-leave-request');
    });
});

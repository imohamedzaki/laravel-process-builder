import { describe, expect, it, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { PropertyInspector } from '@/inspector/PropertyInspector';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';

describe('PropertyInspector', () => {
    beforeEach(() => {
        useProcessEditorStore.setState({
            nodes: [],
            edges: [],
            selectedNodeId: null,
            past: [],
            future: [],
            isDirty: false,
        });
    });

    it('shows a placeholder when no node is selected', () => {
        render(<PropertyInspector />);

        expect(screen.getByText('Select a node to edit its properties.')).toBeInTheDocument();
    });

    it('renders route fields for a selected route node', () => {
        useProcessEditorStore.setState({
            nodes: [{ id: 'n1', type: 'route', position: { x: 0, y: 0 }, data: { method: 'GET', uri: '/orders' } }],
            selectedNodeId: 'n1',
        });

        render(<PropertyInspector />);

        expect(screen.getByLabelText('URI')).toHaveValue('/orders');
    });

    it('updates node data when a field changes', async () => {
        useProcessEditorStore.setState({
            nodes: [{ id: 'n1', type: 'route', position: { x: 0, y: 0 }, data: { method: 'GET', uri: '/orders' } }],
            selectedNodeId: 'n1',
        });

        const user = userEvent.setup();
        render(<PropertyInspector />);

        const uriInput = screen.getByLabelText('URI');
        await user.clear(uriInput);
        await user.type(uriInput, '/customers');

        expect(useProcessEditorStore.getState().nodes[0]?.data.uri).toBe('/customers');
    });
});

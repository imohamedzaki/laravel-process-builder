import { describe, expect, it, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ValidationPanel } from '@/components/ValidationPanel';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';

describe('ValidationPanel', () => {
    beforeEach(() => {
        useProcessEditorStore.setState({ validation: null });
    });

    it('renders nothing when no validation has run', () => {
        const { container } = render(<ValidationPanel />);

        expect(container).toBeEmptyDOMElement();
    });

    it('shows a valid message when there are no issues', () => {
        useProcessEditorStore.setState({ validation: { valid: true, errors: [], warnings: [] } });

        render(<ValidationPanel />);

        expect(screen.getByText('Process is valid')).toBeInTheDocument();
    });

    it('lists errors and warnings', () => {
        useProcessEditorStore.setState({
            validation: {
                valid: false,
                errors: [{ code: 'route.controller_missing', message: 'The route must connect to a controller node.', nodeId: 'r1', field: null, severity: 'error' }],
                warnings: [{ code: 'graph.orphan_node', message: 'Node is orphaned.', nodeId: 'n2', field: null, severity: 'warning' }],
            },
        });

        render(<ValidationPanel />);

        expect(screen.getByText('Validation errors')).toBeInTheDocument();
        expect(screen.getByText('The route must connect to a controller node.')).toBeInTheDocument();
        expect(screen.getByText('Node is orphaned.')).toBeInTheDocument();
    });
});

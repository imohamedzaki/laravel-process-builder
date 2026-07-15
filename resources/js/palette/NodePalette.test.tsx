import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { NodePalette } from '@/palette/NodePalette';

describe('NodePalette', () => {
    it('renders implemented node types as draggable', () => {
        render(<NodePalette />);

        const routeButton = screen.getByRole('button', { name: 'Route' });

        expect(routeButton).toHaveAttribute('draggable', 'true');
        expect(routeButton).not.toBeDisabled();
    });

    it('renders unimplemented node types as disabled', () => {
        render(<NodePalette />);

        const serviceButton = screen.getByRole('button', { name: 'Service' });

        expect(serviceButton).toBeDisabled();
    });

    it('groups nodes under category headings', () => {
        render(<NodePalette />);

        expect(screen.getByText('HTTP')).toBeInTheDocument();
        expect(screen.getByText('Application')).toBeInTheDocument();
        expect(screen.getByText('Data')).toBeInTheDocument();
        expect(screen.getByText('Async')).toBeInTheDocument();
        expect(screen.getByText('Flow Control')).toBeInTheDocument();
    });
});

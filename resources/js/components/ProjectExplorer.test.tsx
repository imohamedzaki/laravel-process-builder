import { describe, expect, it, vi, afterEach, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProjectExplorer } from '@/components/ProjectExplorer';
import { useProjectStore } from '@/stores/useProjectStore';
import * as projectApi from '@/api/project';
import type { ProjectSummary } from '@/types/project';

const summary: ProjectSummary = {
    routes: [
        {
            methods: ['GET'],
            uri: 'orders',
            name: 'orders.index',
            domain: null,
            action: 'App\\Http\\Controllers\\OrderController@index',
            controller: 'App\\Http\\Controllers\\OrderController',
            controllerMethod: 'index',
            middleware: ['web'],
            parameters: [],
            isVendorRoute: false,
            isPackageInternal: false,
        },
    ],
    routeCount: 1,
    controllerCount: 1,
    namedRouteCount: 1,
    unnamedRouteCount: 0,
    routesByMethod: { GET: 1 },
    duplicateRouteNames: [],
    routesWithMissingControllers: [],
};

describe('ProjectExplorer', () => {
    beforeEach(() => {
        useProjectStore.setState({ summary: null, isLoading: false, error: null });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('scans on mount and renders route data', async () => {
        vi.spyOn(projectApi, 'fetchProjectSummary').mockResolvedValue(summary);

        render(<ProjectExplorer />);

        await waitFor(() => {
            expect(screen.getByText('orders')).toBeInTheDocument();
        });

        expect(screen.getByText('orders.index')).toBeInTheDocument();
        expect(screen.getByText('App\\Http\\Controllers\\OrderController@index')).toBeInTheDocument();
    });

    it('shows an error when the scan fails', async () => {
        vi.spyOn(projectApi, 'fetchProjectSummary').mockRejectedValue(new Error('network error'));

        render(<ProjectExplorer />);

        await waitFor(() => {
            expect(screen.getByRole('alert')).toHaveTextContent('Unable to scan the project.');
        });
    });

    it('rescans when the button is clicked', async () => {
        const spy = vi.spyOn(projectApi, 'fetchProjectSummary').mockResolvedValue(summary);
        const user = userEvent.setup();

        render(<ProjectExplorer />);

        await waitFor(() => expect(spy).toHaveBeenCalledTimes(1));

        await user.click(screen.getByRole('button', { name: /rescan/i }));

        await waitFor(() => expect(spy).toHaveBeenCalledTimes(2));
    });
});

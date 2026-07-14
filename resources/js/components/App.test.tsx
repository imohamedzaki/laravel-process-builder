import { describe, expect, it, vi, afterEach, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { App } from '@/components/App';
import { apiClient } from '@/api/client';
import { useProjectStore } from '@/stores/useProjectStore';

vi.mock('@/api/client', () => ({
    apiClient: {
        get: vi.fn(),
    },
}));

vi.mock('@/api/project', () => ({
    fetchProjectSummary: vi.fn().mockResolvedValue({
        routes: [],
        routeCount: 0,
        controllerCount: 0,
        namedRouteCount: 0,
        unnamedRouteCount: 0,
        routesByMethod: {},
        duplicateRouteNames: [],
        routesWithMissingControllers: [],
    }),
}));

describe('App', () => {
    beforeEach(() => {
        useProjectStore.setState({ summary: null, isLoading: false, error: null });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('renders the dashboard header with app name and tagline', async () => {
        vi.mocked(apiClient.get).mockResolvedValue({ data: { data: {} } });

        render(<App config={{ appName: 'Laravel Process Builder', tagline: 'Design visually.', version: '0.1.0' }} />);

        expect(screen.getByText('Laravel Process Builder')).toBeInTheDocument();
        expect(screen.getByText('Design visually.')).toBeInTheDocument();

        await waitFor(() => expect(apiClient.get).toHaveBeenCalled());
    });

    it('shows an error message when the health endpoint is unreachable', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('network error'));

        render(<App config={{ appName: 'PB', tagline: '', version: '0.1.0' }} />);

        await waitFor(() => {
            expect(screen.getByRole('alert')).toHaveTextContent('Unable to reach the Laravel Process Builder API.');
        });
    });

    it('renders health status once the API call resolves', async () => {
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { data: { status: 'ok', version: '0.1.0', generationEnabled: false, environment: 'testing' } },
        });

        render(<App config={{ appName: 'PB', tagline: '', version: '0.1.0' }} />);

        await waitFor(() => {
            expect(screen.getByText('Status: ok')).toBeInTheDocument();
        });
    });
});

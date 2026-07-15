import { useEffect, useState } from 'react';
import { apiClient } from '@/api/client';
import { ProcessEditor } from '@/components/ProcessEditor';
import { ProcessList } from '@/components/ProcessList';
import { ProjectExplorer } from '@/components/ProjectExplorer';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { DashboardConfig, HealthResponse } from '@/types/dashboard';

interface AppProps {
    config: DashboardConfig;
}

type Tab = 'explorer' | 'processes';

export function App({ config }: AppProps): JSX.Element {
    const [health, setHealth] = useState<HealthResponse['data'] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [tab, setTab] = useState<Tab>('explorer');
    const process = useProcessEditorStore((state) => state.process);

    useEffect(() => {
        let cancelled = false;

        apiClient
            .get<HealthResponse>('/health')
            .then((response) => {
                if (!cancelled) {
                    setHealth(response.data.data);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError('Unable to reach the Laravel Process Builder API.');
                }
            });

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <div className="pb-shell">
            <header className="pb-topbar">
                <span className="pb-logo">{config.appName}</span>
                <span className="pb-tagline">{config.tagline}</span>
                <nav className="pb-tabs">
                    <button
                        type="button"
                        className={tab === 'explorer' ? 'pb-tab pb-tab--active' : 'pb-tab'}
                        onClick={() => setTab('explorer')}
                    >
                        Project Explorer
                    </button>
                    <button
                        type="button"
                        className={tab === 'processes' ? 'pb-tab pb-tab--active' : 'pb-tab'}
                        onClick={() => setTab('processes')}
                    >
                        Processes
                    </button>
                </nav>
            </header>

            {tab === 'explorer' && (
                <main className="pb-main">
                    <h1>Welcome to Laravel Process Builder</h1>
                    <p>Version {config.version}</p>
                    {health && (
                        <ul>
                            <li>Status: {health.status}</li>
                            <li>Environment: {health.environment}</li>
                            <li>Generation enabled: {String(health.generationEnabled)}</li>
                        </ul>
                    )}
                    {error && <p role="alert">{error}</p>}
                    <ProjectExplorer />
                </main>
            )}

            {tab === 'processes' && (process ? <ProcessEditor /> : <ProcessList />)}
        </div>
    );
}

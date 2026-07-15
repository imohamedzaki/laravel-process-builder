import { useEffect, useState } from 'react';
import { apiClient } from '@/api/client';
import { ProcessEditor } from '@/components/ProcessEditor';
import { ProcessList } from '@/components/ProcessList';
import { ProjectExplorer } from '@/components/ProjectExplorer';
import { Icon } from '@/components/Icon';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { DashboardConfig, HealthResponse } from '@/types/dashboard';

interface AppProps {
    config: DashboardConfig;
}

type Tab = 'explorer' | 'processes';

export function App({ config }: AppProps): JSX.Element {
    const [health, setHealth] = useState<HealthResponse['data'] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [tab, setTab] = useState<Tab>(() => new URLSearchParams(window.location.search).get('tab') === 'processes' ? 'processes' : 'explorer');
    const process = useProcessEditorStore((state) => state.process);
    const loadProcess = useProcessEditorStore((state) => state.load);
    const [theme, setTheme] = useState<'light' | 'dark'>(() => {
        try { return (window.localStorage?.getItem('process-builder-theme') as 'light' | 'dark' | null) ?? 'light'; }
        catch { return 'light'; }
    });

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

    useEffect(() => {
        const slug = new URLSearchParams(window.location.search).get('process');
        if (slug) void loadProcess(slug);
    }, [loadProcess]);

    useEffect(() => {
        document.documentElement.dataset.theme = theme;
        try { window.localStorage?.setItem('process-builder-theme', theme); } catch { /* Storage may be disabled. */ }
    }, [theme]);

    return (
        <div className="pb-shell">
            <aside className="pb-app-rail">
                <div className="pb-brand-mark"><Icon name="git-branch" /></div>
                <nav className="pb-rail-nav" aria-label="Primary navigation">
                    <button
                        type="button"
                        className={tab === 'explorer' ? 'pb-rail-button pb-rail-button--active' : 'pb-rail-button'}
                        onClick={() => setTab('explorer')}
                        title="Project Explorer"
                    >
                        <Icon name="layers" /><span>Explore</span>
                    </button>
                    <button
                        type="button"
                        className={tab === 'processes' ? 'pb-rail-button pb-rail-button--active' : 'pb-rail-button'}
                        onClick={() => setTab('processes')}
                        title="Processes"
                    >
                        <Icon name="git-branch" /><span>Processes</span>
                    </button>
                </nav>
                <button className="pb-rail-button pb-theme-toggle" type="button" onClick={() => setTheme(theme === 'light' ? 'dark' : 'light')} aria-label={`Switch to ${theme === 'light' ? 'dark' : 'light'} mode`}>
                    <Icon name={theme === 'light' ? 'moon' : 'sun'} /><span>Theme</span>
                </button>
            </aside>

            <div className="pb-workspace">
                <header className="pb-topbar">
                    <div>
                        <div className="pb-logo">{config.appName}</div>
                        <div className="pb-tagline">{config.tagline}</div>
                    </div>
                    <div className="pb-system-state">
                        <span className={`pb-live-dot${error ? ' pb-live-dot--error' : ''}`} />
                        {error ? 'API offline' : health ? `${health.environment} connected` : 'Connecting'}
                    </div>
                    {health && <span className="pb-visually-hidden">Status: {health.status}</span>}
                    <span className="pb-version">v{config.version}</span>
                </header>

            {tab === 'explorer' && (
                <main className="pb-main">
                    {error && <div className="pb-alert" role="alert">{error}</div>}
                    <ProjectExplorer health={health} onOpenProcesses={() => setTab('processes')} />
                </main>
            )}

                {tab === 'processes' && (process ? <ProcessEditor onBack={() => useProcessEditorStore.setState({ process: null })} generationEnabled={health?.generationEnabled ?? false} /> : <ProcessList />)}
            </div>
        </div>
    );
}

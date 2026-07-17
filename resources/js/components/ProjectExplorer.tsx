import { useEffect, useMemo, useState } from 'react';
import { fetchProcesses } from '@/api/processes';
import { Icon } from '@/components/Icon';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import { useProjectStore } from '@/stores/useProjectStore';
import type { HealthResponse } from '@/types/dashboard';
import type { ProcessDefinition } from '@/types/process';
import { discoverGuards, participantGuards } from '@/utils/guards';

interface ProjectExplorerProps { health?: HealthResponse['data'] | null; onOpenProcesses?: () => void }

export function ProjectExplorer({ health = null, onOpenProcesses }: ProjectExplorerProps): JSX.Element {
    const { summary, isLoading, error, scan } = useProjectStore();
    const [processes, setProcesses] = useState<ProcessDefinition[]>([]);
    const [query, setQuery] = useState('');
    const [method, setMethod] = useState('ALL');
    const load = useProcessEditorStore((state) => state.load);

    useEffect(() => { void scan(); void Promise.resolve(fetchProcesses()).then((items) => setProcesses(Array.isArray(items) ? items : [])).catch(() => undefined); }, [scan]);

    const routes = useMemo(() => summary?.routes.filter((route) => {
        const search = query.toLowerCase();
        return (method === 'ALL' || route.methods.includes(method)) && (!search || `${route.uri} ${route.name ?? ''} ${route.controller ?? ''}`.toLowerCase().includes(search));
    }) ?? [], [summary, query, method]);
    const guards = [...new Set([...discoverGuards(summary), ...processes.flatMap(participantGuards)])].sort();

    function openProcess(process: ProcessDefinition): void { void load(process.slug); onOpenProcesses?.(); }

    return <section className="pb-explorer">
        <header className="pb-page-heading">
            <div><span className="pb-eyebrow">Application topology</span><h1>Project Explorer</h1><p>Routes, controllers, independent processes, and participant guard mappings discovered from your Laravel project.</p></div>
            <button className="pb-button pb-button--secondary" type="button" onClick={() => void scan()} disabled={isLoading}><Icon name="refresh" />{isLoading ? 'Scanning…' : 'Rescan project'}</button>
        </header>
        {error && <div className="pb-alert" role="alert">{error}</div>}
        {summary && <>
            <dl className="pb-explorer-stats">
                <div className="pb-stat-card pb-stat-card--primary"><span className="pb-stat-icon"><Icon name="route" /></span><div><dt>Application routes</dt><dd>{summary.routeCount}</dd><small>{summary.namedRouteCount} named</small></div></div>
                <div className="pb-stat-card"><span className="pb-stat-icon"><Icon name="controller" /></span><div><dt>Controllers</dt><dd>{summary.controllerCount}</dd><small>Resolvable classes</small></div></div>
                <div className="pb-stat-card"><span className="pb-stat-icon"><Icon name="shield" /></span><div><dt>Available guards</dt><dd>{guards.length}</dd><small>{guards.join(' · ')}</small></div></div>
                <div className="pb-stat-card"><span className="pb-stat-icon"><Icon name="git-branch" /></span><div><dt>Processes</dt><dd>{processes.length}</dd><small>{processes.filter((item) => item.status === 'generated').length} published</small></div></div>
            </dl>
            {summary.duplicateRouteNames.length > 0 && <div className="pb-alert" role="alert">Duplicate route names: {summary.duplicateRouteNames.join(', ')}</div>}
            {summary.routesWithMissingControllers.length > 0 && <div className="pb-alert" role="alert">{summary.routesWithMissingControllers.length} route(s) reference a missing controller.</div>}
            <div className="pb-explorer-grid">
                <article className="pb-panel pb-route-panel">
                    <header className="pb-panel-header"><div><h2>Route inventory</h2><span>{routes.length} visible endpoints</span></div><div className="pb-table-tools"><label className="pb-search"><Icon name="search" /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Filter routes…" aria-label="Filter routes" /></label><select value={method} onChange={(event) => setMethod(event.target.value)} aria-label="Filter by method">{['ALL', ...Object.keys(summary.routesByMethod)].map((item) => <option key={item}>{item}</option>)}</select></div></header>
                    <div className="pb-table-wrap"><table className="pb-explorer-table"><thead><tr><th>Method</th><th>Endpoint</th><th>Controller</th><th>Pipeline</th></tr></thead><tbody>{routes.map((route) => <tr key={`${route.methods.join(',')}-${route.uri}`}><td><span className={`pb-method pb-method--${route.methods[0]?.toLowerCase()}`}>{route.methods.join('|')}</span></td><td><strong>{route.uri}</strong><small>{route.name ?? 'Unnamed route'}</small></td><td>{route.controller ? <><span>{route.controller.split('\\').at(-1)}@{route.controllerMethod ?? ''}</span><small>{route.action ?? `${route.controller}@${route.controllerMethod ?? ''}`}</small></> : 'Closure'}</td><td><div className="pb-chip-list">{route.middleware.map((item) => <span className="pb-chip" key={item}>{item}</span>)}</div></td></tr>)}</tbody></table></div>
                </article>
                <aside className="pb-panel pb-topology-panel">
                    <header className="pb-panel-header"><div><h2>Participant guard topology</h2><span>A guard can participate in several independent workflows</span></div></header>
                    <div className="pb-guard-list">{guards.map((guard) => { const linked = processes.filter((item) => participantGuards(item).includes(guard)); const primary = linked[0]; return <button type="button" className="pb-guard-card" key={guard} onClick={() => primary ? openProcess(primary) : onOpenProcesses?.()}><span className="pb-guard-icon"><Icon name="shield" /></span><span><strong>{guard}</strong><small>{linked.length ? linked.map((item) => item.name).join(' · ') : 'Not mapped to a participant'}</small></span><span className={`pb-status-pill pb-status-pill--${primary?.status ?? 'empty'}`}>{linked.length ? `${linked.length} process${linked.length === 1 ? '' : 'es'}` : 'available'}</span></button>; })}</div>
                    <div className="pb-generation-state"><span><Icon name="code" />Code generation</span><strong>{health?.generationEnabled ? 'Enabled' : 'Read-only'}</strong></div>
                </aside>
            </div>
        </>}
    </section>;
}

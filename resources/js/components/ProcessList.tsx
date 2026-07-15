import { useEffect, useState } from 'react';
import { fetchProcesses } from '@/api/processes';
import { Icon } from '@/components/Icon';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { ProcessDefinition } from '@/types/process';
import { processGuard } from '@/utils/guards';

export function ProcessList(): JSX.Element {
    const [processes, setProcesses] = useState<ProcessDefinition[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [newName, setNewName] = useState('');
    const load = useProcessEditorStore((state) => state.load);
    const createDraft = useProcessEditorStore((state) => state.createDraft);

    useEffect(() => { fetchProcesses().then(setProcesses).catch(() => setError('Unable to load processes.')); }, []);

    function handleCreate(): void {
        if (newName.trim() === '') return;
        const slug = newName.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        createDraft(newName.trim(), slug, `Guard workflow for ${slug}.`); setNewName('');
    }

    return <section className="pb-process-list" aria-label="Processes">
        <header className="pb-page-heading"><div><span className="pb-eyebrow">Guard workflows</span><h1>Processes</h1><p>Every process is an independent guard with its own Start node and generated pipeline.</p></div><span className="pb-capacity"><strong>{processes.length}</strong> process guards</span></header>
        {error && <div className="pb-alert" role="alert">{error}</div>}
        <ul className="pb-process-cards">{processes.map((process) => <li key={process.id}><button aria-label={process.name} className="pb-process-card" type="button" onClick={() => void load(process.slug)}><span className="pb-process-card-icon"><Icon name="git-branch" /></span><span className="pb-process-card-body"><span className="pb-process-card-top"><strong>{process.name}</strong><span className={`pb-status-pill pb-status-pill--${process.status}`}>{process.status}</span></span><small><Icon name="shield" />Guard: {processGuard(process)} · v{process.version}</small><span>{process.nodes.length} components · {process.edges.length} connections</span></span><Icon name="play" className="pb-process-open" /></button></li>)}</ul>
        <div className="pb-process-list-new pb-panel"><div className="pb-new-process-copy"><span className="pb-new-process-icon"><Icon name="plus" /></span><div><h2>New process guard</h2><p>The process slug becomes its unique guard identifier.</p></div></div><input type="text" placeholder="New process name" value={newName} onChange={(event) => setNewName(event.target.value)} aria-label="New process name" /><div className="pb-guard-preview"><Icon name="shield" /><span>Guard ID<strong>{newName.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'process-guard'}</strong></span></div><button className="pb-button pb-button--primary" type="button" onClick={handleCreate}><Icon name="plus" />New Process</button></div>
    </section>;
}

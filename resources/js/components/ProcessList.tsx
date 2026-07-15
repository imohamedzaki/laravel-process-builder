import { useEffect, useState } from 'react';
import { fetchProcesses } from '@/api/processes';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { ProcessDefinition } from '@/types/process';

export function ProcessList(): JSX.Element {
    const [processes, setProcesses] = useState<ProcessDefinition[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [newName, setNewName] = useState('');
    const load = useProcessEditorStore((state) => state.load);
    const createDraft = useProcessEditorStore((state) => state.createDraft);

    useEffect(() => {
        fetchProcesses()
            .then(setProcesses)
            .catch(() => setError('Unable to load processes.'));
    }, []);

    function handleCreate(): void {
        if (newName.trim() === '') {
            return;
        }

        const slug = newName
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        createDraft(newName.trim(), slug);
        setNewName('');
    }

    return (
        <section className="pb-process-list" aria-label="Processes">
            <h2>Processes</h2>

            {error && <p role="alert">{error}</p>}

            <ul>
                {processes.map((process) => (
                    <li key={process.id}>
                        <button type="button" onClick={() => void load(process.slug)}>
                            {process.name}
                        </button>
                    </li>
                ))}
            </ul>

            <div className="pb-process-list-new">
                <input
                    type="text"
                    placeholder="New process name"
                    value={newName}
                    onChange={(event) => setNewName(event.target.value)}
                    aria-label="New process name"
                />
                <button type="button" onClick={handleCreate}>
                    New Process
                </button>
            </div>
        </section>
    );
}

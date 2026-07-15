import { ProcessCanvas } from '@/canvas/ProcessCanvas';
import { PropertyInspector } from '@/inspector/PropertyInspector';
import { NodePalette } from '@/palette/NodePalette';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import { ValidationPanel } from '@/components/ValidationPanel';

export function ProcessEditor(): JSX.Element {
    const { process, isDirty, isSaving, isValidating, error, save, validate, undo, redo, past, future } =
        useProcessEditorStore();

    if (process === null) {
        return <p className="pb-editor-empty">No process is open.</p>;
    }

    return (
        <div className="pb-editor">
            <div className="pb-editor-toolbar">
                <span className="pb-editor-title">{process.name}</span>
                <span className="pb-editor-status">{isDirty ? 'Unsaved changes' : 'Saved'}</span>
                <button type="button" onClick={undo} disabled={past.length === 0}>
                    Undo
                </button>
                <button type="button" onClick={redo} disabled={future.length === 0}>
                    Redo
                </button>
                <button type="button" onClick={() => void validate()} disabled={isValidating}>
                    {isValidating ? 'Validating…' : 'Validate'}
                </button>
                <button type="button" onClick={() => void save()} disabled={isSaving}>
                    {isSaving ? 'Saving…' : 'Save'}
                </button>
            </div>

            {error && <p role="alert">{error}</p>}

            <ValidationPanel />

            <div className="pb-editor-body">
                <NodePalette />
                <ProcessCanvas />
                <PropertyInspector />
            </div>
        </div>
    );
}

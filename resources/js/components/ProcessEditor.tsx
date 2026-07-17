import { useState } from 'react';
import { ProcessCanvas } from '@/canvas/ProcessCanvas';
import { Icon } from '@/components/Icon';
import { ParticipantCreator, ParticipantManager } from '@/components/ParticipantManager';
import { ValidationPanel } from '@/components/ValidationPanel';
import { PropertyInspector } from '@/inspector/PropertyInspector';
import { NodePalette } from '@/palette/NodePalette';
import { generateProcess, previewProcess, type ProcessPreview } from '@/api/processes';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';

interface ProcessEditorProps { onBack?: () => void; generationEnabled?: boolean }

export function ProcessEditor({ onBack, generationEnabled = true }: ProcessEditorProps): JSX.Element {
    const { process, nodes, edges, participants, isDirty, isSaving, isValidating, error, save, validate, undo, redo, past, future } = useProcessEditorStore();
    const [preview, setPreview] = useState<ProcessPreview | null>(null);
    const [previewFile, setPreviewFile] = useState(0);
    const [isPreviewing, setIsPreviewing] = useState(false);
    const [isPublishing, setIsPublishing] = useState(false);
    const [publishedFiles, setPublishedFiles] = useState<number | null>(null);

    if (process === null) return <p className="pb-editor-empty">No process is open.</p>;
    const openProcess = process;

    async function handlePreview(): Promise<void> {
        setIsPreviewing(true); setPublishedFiles(null);
        await save();
        const current = useProcessEditorStore.getState().process;
        if (current?.id) {
            try { const result = await previewProcess(current.slug); setPreview(result); setPreviewFile(0); } catch { useProcessEditorStore.setState({ error: 'Unable to prepare the code preview.' }); }
        }
        setIsPreviewing(false);
    }

    async function handlePublish(): Promise<void> {
        if (!preview?.previewToken) return;
        setIsPublishing(true);
        try { const result = await generateProcess(openProcess.slug, preview.previewToken); setPublishedFiles(result.files.length); setPreview(null); await useProcessEditorStore.getState().load(openProcess.slug); }
        catch { useProcessEditorStore.setState({ error: 'Publishing failed. Refresh the preview and try again.' }); }
        setIsPublishing(false);
    }

    const activeFile = preview?.files[previewFile];
    return <div className="pb-editor">
        <div className="pb-editor-toolbar">
            <button className="pb-icon-button" type="button" onClick={onBack} aria-label="Back to processes"><Icon name="arrow-left" /></button>
            <div className="pb-editor-identity"><span className="pb-editor-title">{process.name}</span><span className="pb-editor-status"><span className={`pb-save-dot${isDirty ? ' pb-save-dot--dirty' : ''}`} />{isDirty ? 'Unsaved changes' : 'All changes saved'}</span></div>
            <div className="pb-editor-metrics"><span>{nodes.length} components</span><span>{edges.length} links</span></div>
            {participants.length > 0 && <ParticipantManager />}
            <div className="pb-toolbar-group"><button className="pb-icon-button" type="button" onClick={undo} disabled={past.length === 0} aria-label="Undo"><Icon name="undo" /></button><button className="pb-icon-button" type="button" onClick={redo} disabled={future.length === 0} aria-label="Redo"><Icon name="redo" /></button></div>
            <button className="pb-button pb-button--secondary" type="button" onClick={() => void validate()} disabled={isValidating}><Icon name="check" />{isValidating ? 'Checking…' : 'Validate'}</button>
            <button className="pb-button pb-button--secondary" type="button" onClick={() => void save()} disabled={isSaving}><Icon name="terminal" />{isSaving ? 'Saving…' : 'Save draft'}</button>
            <button className="pb-button pb-button--publish" type="button" onClick={() => void handlePreview()} disabled={isPreviewing}><Icon name="zap" />{isPreviewing ? 'Compiling…' : 'Publish'}</button>
        </div>
        {error && <div className="pb-editor-alert pb-alert" role="alert">{error}</div>}
        {publishedFiles !== null && <div className="pb-publish-success" role="status"><Icon name="check" />Published successfully. {publishedFiles} Laravel files generated.</div>}
        <ValidationPanel />
        {participants.length === 0 ? <div className="pb-participant-gate"><ParticipantCreator onboarding /></div> : <div className="pb-editor-body"><NodePalette /><ProcessCanvas /><PropertyInspector /></div>}
        {preview && <div className="pb-modal-backdrop" role="presentation"><section className="pb-code-modal" role="dialog" aria-modal="true" aria-label="Publish code preview">
            <header><div><span className="pb-eyebrow">Generated code preview</span><h2>{preview.validation.valid ? 'Ready to publish' : 'Resolve validation issues'}</h2><p>{preview.files.length} files will be written to your Laravel project.</p></div><button className="pb-icon-button" type="button" onClick={() => setPreview(null)} aria-label="Close preview"><Icon name="x" /></button></header>
            <div className="pb-code-modal-body"><nav aria-label="Generated files">{preview.files.map((file, index) => <button type="button" className={index === previewFile ? 'active' : ''} key={file.relativePath} onClick={() => setPreviewFile(index)}><Icon name="file" /><span>{file.relativePath}<small>{file.logicalType}</small></span></button>)}</nav><div className="pb-code-preview"><div className="pb-code-preview-bar"><span>{activeFile?.relativePath ?? 'No generated files'}</span><small>{activeFile?.sha256.slice(0, 12)}</small></div><pre><code>{activeFile?.contents ?? preview.validation.errors.map((item) => item.message).join('\n')}</code></pre></div></div>
            <footer><span><Icon name="shield" />A backup is created before managed files change.</span><div><button className="pb-button pb-button--secondary" type="button" onClick={() => setPreview(null)}>Cancel</button><button className="pb-button pb-button--publish" type="button" disabled={!generationEnabled || !preview.previewToken || isPublishing} onClick={() => void handlePublish()}><Icon name="zap" />{isPublishing ? 'Publishing…' : generationEnabled ? 'Publish code' : 'Generation disabled'}</button></div></footer>
        </section></div>}
    </div>;
}

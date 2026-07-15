import { useMemo, useState } from 'react';
import { Icon, type IconName } from '@/components/Icon';
import { IMPLEMENTED_NODE_TYPES, type NodeType } from '@/types/process';

interface PaletteCategory { label: string; types: NodeType[] }
const CATEGORIES: PaletteCategory[] = [
    { label: 'HTTP', types: ['route', 'middleware', 'form_request', 'authorization', 'controller', 'api_resource', 'response'] },
    { label: 'Application', types: ['action', 'service', 'transaction', 'condition'] },
    { label: 'Data', types: ['model', 'model_create', 'model_update', 'model_delete'] },
    { label: 'Async', types: ['event', 'job', 'notification'] },
    { label: 'Flow Control', types: ['start', 'success', 'failure', 'exception', 'end'] },
];
export const NODE_LABELS: Record<NodeType, string> = { start:'Start',route:'Route',middleware:'Middleware',form_request:'Form Request',authorization:'Authorization',controller:'Controller',action:'Action',service:'Service',transaction:'Transaction',condition:'Decision',model:'Model',model_create:'Create Model',model_update:'Update Model',model_delete:'Delete Model',event:'Event',job:'Queued Job',notification:'Notification',api_resource:'API Resource',response:'Response',success:'Success',failure:'Failure',exception:'Exception',end:'End' };
export const NODE_ICONS: Record<NodeType, IconName> = { start:'play',route:'route',middleware:'shield',form_request:'file',authorization:'shield',controller:'controller',action:'zap',service:'settings',transaction:'database',condition:'diamond',model:'database',model_create:'database',model_update:'database',model_delete:'database',event:'activity',job:'command',notification:'activity',api_resource:'box',response:'code',success:'check',failure:'x',exception:'x',end:'check' };
export const PALETTE_DRAG_MIME = 'application/x-process-builder-node-type';

export function NodePalette(): JSX.Element {
    const [query, setQuery] = useState('');
    const categories = useMemo(() => CATEGORIES.map((category) => ({ ...category, types: category.types.filter((type) => NODE_LABELS[type].toLowerCase().includes(query.toLowerCase())) })).filter((category) => category.types.length), [query]);
    function handleDragStart(event: React.DragEvent<HTMLButtonElement>, nodeType: NodeType): void { event.dataTransfer.setData(PALETTE_DRAG_MIME, nodeType); event.dataTransfer.effectAllowed = 'move'; }
    return <aside className="pb-palette" aria-label="Node palette">
        <header><span className="pb-eyebrow">Components</span><h2>Build your flow</h2><p>Drag onto the canvas or pull a connector to add the next step.</p></header>
        <label className="pb-palette-search"><Icon name="search" /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Find component" aria-label="Find component" /></label>
        <div className="pb-palette-scroll">{categories.map((category) => <section key={category.label} className="pb-palette-category"><h3>{category.label}<span>{category.types.length}</span></h3><div className="pb-palette-items">{category.types.map((nodeType) => { const isImplemented = IMPLEMENTED_NODE_TYPES.includes(nodeType); return <button aria-label={NODE_LABELS[nodeType]} key={nodeType} type="button" draggable={isImplemented} disabled={!isImplemented} onDragStart={(event) => handleDragStart(event, nodeType)} className={`pb-palette-item${isImplemented ? '' : ' pb-palette-item--disabled'}`} title={isImplemented ? `Drag ${NODE_LABELS[nodeType]} to canvas` : 'Not yet implemented'}><span className={`pb-palette-symbol pb-palette-symbol--${nodeType}`}><Icon name={NODE_ICONS[nodeType]} /></span><span>{NODE_LABELS[nodeType]}<small>{nodeType.replace('_', ' ')}</small></span><span className="pb-drag-grip">⠿</span></button>; })}</div></section>)}</div>
    </aside>;
}

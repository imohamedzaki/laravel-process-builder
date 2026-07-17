import { useState } from 'react';
import { Icon } from '@/components/Icon';
import { useProcessEditorStore } from '@/stores/useProcessEditorStore';
import type { ParticipantActorType } from '@/types/process';

let participantIdCounter = 0;
const toGuard = (value: string): string => value.trim().toLowerCase().replace(/[^a-z0-9._-]+/g, '-').replace(/^[._-]+|[._-]+$/g, '');

function nextParticipantId(): string {
    participantIdCounter += 1;
    return `participant_${Date.now()}_${participantIdCounter}`;
}

export function ParticipantCreator({ onboarding = false }: { onboarding?: boolean }): JSX.Element {
    const { participants, addParticipant } = useProcessEditorStore();
    const [name, setName] = useState('');
    const [guard, setGuard] = useState('');
    const normalizedGuard = toGuard(guard || name);
    const duplicate = participants.some((item) => item.guard === normalizedGuard);

    function create(): void {
        if (!name.trim() || !normalizedGuard || duplicate) return;
        addParticipant({ id: nextParticipantId(), name: name.trim(), guard: normalizedGuard, actorType: 'human', order: participants.length, color: null });
        setName(''); setGuard('');
    }

    return <div className={onboarding ? 'pb-participant-onboarding' : 'pb-participant-create'}>
        {onboarding && <div className="pb-participant-onboarding-copy"><span className="pb-onboarding-icon"><Icon name="users" /></span><span className="pb-eyebrow">Required first step</span><h2>Create a participant</h2><p>The process stays independent. Each participant maps a role in this diagram to a Laravel guard, so buyer and seller can safely share one workflow.</p></div>}
        <div className="pb-participant-form">
            <label>Participant name<input value={name} onChange={(event) => setName(event.target.value)} placeholder="Buyer, Seller, Admin…" autoFocus={onboarding} /></label>
            <label>Laravel guard<input value={guard} onChange={(event) => setGuard(event.target.value)} placeholder={toGuard(name) || 'web'} /></label>
            <button className="pb-button pb-button--primary" type="button" onClick={create} disabled={!name.trim() || !normalizedGuard || duplicate}><Icon name="plus" />Create participant</button>
            {duplicate && <small className="pb-field-error">This guard already has a participant in this process.</small>}
        </div>
        {onboarding && <div className="pb-onboarding-sequence"><span className="active">1&nbsp; Participant + guard</span><span>2&nbsp; Start created automatically</span><span>3&nbsp; Design the pipeline</span></div>}
    </div>;
}

export function ParticipantManager(): JSX.Element {
    const { participants, updateParticipant, removeParticipant } = useProcessEditorStore();
    const [isOpen, setIsOpen] = useState(false);

    return <div className="pb-lane-manager">
        <button type="button" onClick={() => setIsOpen((open) => !open)}><Icon name="users" />Participants ({participants.length})</button>
        {isOpen && <div className="pb-lane-manager-panel pb-participant-manager-panel">
            <header><strong>Participants & guards</strong><small>One process can coordinate several guards.</small></header>
            {[...participants].sort((a, b) => a.order - b.order).map((participant) => <div key={participant.id} className="pb-lane-manager-row pb-participant-row">
                <input value={participant.name} onChange={(event) => updateParticipant(participant.id, { name: event.target.value })} aria-label="Participant name" />
                <input value={participant.guard} onChange={(event) => updateParticipant(participant.id, { guard: toGuard(event.target.value) })} aria-label="Participant guard" />
                <select value={participant.actorType ?? ''} onChange={(event) => updateParticipant(participant.id, { actorType: (event.target.value || null) as ParticipantActorType })} aria-label="Participant actor type"><option value="human">Human</option><option value="system">System</option><option value="">None</option></select>
                <button type="button" disabled={participants.length === 1} onClick={() => removeParticipant(participant.id)} aria-label={`Remove ${participant.name}`}><Icon name="trash" /></button>
            </div>)}
            <ParticipantCreator />
        </div>}
    </div>;
}

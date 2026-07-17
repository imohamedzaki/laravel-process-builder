import { useViewport } from '@xyflow/react';
import type { ParticipantLayout } from '@/canvas/participantLayout';
import type { ProcessParticipant } from '@/types/process';

interface SwimlaneHeadersProps {
    participants: ProcessParticipant[];
    layouts: ParticipantLayout[];
}

const LANE_HEADER_WIDTH = 160;

export function SwimlaneHeaders({ participants, layouts }: SwimlaneHeadersProps): JSX.Element | null {
    const { y: viewportY, zoom } = useViewport();

    if (participants.length === 0) {
        return null;
    }

    const sortedParticipants = [...participants].sort((a, b) => a.order - b.order);

    return (
        <div className="pb-lane-headers" style={{ width: LANE_HEADER_WIDTH }}>
            {sortedParticipants.map((participant) => {
                const layout = layouts.find((item) => item.id === participant.id);
                if (!layout) return null;
                const top = viewportY + layout.top * zoom;
                const height = layout.height * zoom;

                return (
                    <div
                        key={participant.id}
                        className="pb-lane-header"
                        style={{ top, height, background: participant.color ?? undefined }}
                    >
                        <span className="pb-lane-header-icon" aria-hidden="true">
                            {participant.actorType === 'system' ? '⚙' : '◉'}
                        </span>
                        <span className="pb-lane-header-name">{participant.name}<small>{participant.guard}</small></span>
                    </div>
                );
            })}
        </div>
    );
}

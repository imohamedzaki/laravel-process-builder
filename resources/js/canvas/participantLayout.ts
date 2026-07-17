import type { Position, ProcessNode, ProcessParticipant } from '@/types/process';

export const PARTICIPANT_MIN_HEIGHT = 220;
export const PARTICIPANT_NODE_HEIGHT = 80;
export const PARTICIPANT_PADDING = 32;

export interface ParticipantLayout {
    id: string;
    top: number;
    height: number;
}

export function buildParticipantLayouts(participants: ProcessParticipant[], nodes: ProcessNode[]): ParticipantLayout[] {
    const sorted = [...participants].sort((a, b) => a.order - b.order);
    if (sorted.length === 0) return [];

    if (sorted.length === 1) {
        const contentBottom = nodes.reduce(
            (bottom, node) => Math.max(bottom, Math.max(PARTICIPANT_PADDING, node.position.y) + PARTICIPANT_NODE_HEIGHT + PARTICIPANT_PADDING),
            PARTICIPANT_MIN_HEIGHT,
        );
        return [{ id: sorted[0]!.id, top: 0, height: contentBottom }];
    }

    return sorted.map((participant, index) => ({
        id: participant.id,
        top: index * PARTICIPANT_MIN_HEIGHT,
        height: PARTICIPANT_MIN_HEIGHT,
    }));
}

export function participantForY(layouts: ParticipantLayout[], y: number): ParticipantLayout | null {
    if (layouts.length === 0) return null;
    return layouts.find((layout) => y >= layout.top && y < layout.top + layout.height)
        ?? (y < layouts[0]!.top ? layouts[0]! : layouts.at(-1)!);
}

export function constrainPositionToParticipant(position: Position, layout: ParticipantLayout): Position {
    const minimumY = layout.top + PARTICIPANT_PADDING;
    const maximumY = Math.max(minimumY, layout.top + layout.height - PARTICIPANT_NODE_HEIGHT - PARTICIPANT_PADDING);
    return { ...position, y: Math.min(Math.max(position.y, minimumY), maximumY) };
}

export function normalizeParticipantPositions(
    participants: ProcessParticipant[],
    nodes: ProcessNode[],
): ProcessNode[] {
    const layouts = buildParticipantLayouts(participants, nodes);
    if (layouts.length === 0) return nodes;

    return nodes.map((node) => {
        const assigned = layouts.find((layout) => layout.id === node.data.participantId) ?? layouts[0]!;
        const position = constrainPositionToParticipant(node.position, assigned);
        return position.y === node.position.y ? node : { ...node, position };
    });
}

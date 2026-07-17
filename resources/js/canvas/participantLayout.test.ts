import { describe, expect, it } from 'vitest';
import { buildParticipantLayouts, constrainPositionToParticipant, normalizeParticipantPositions, participantForY } from '@/canvas/participantLayout';
import type { ProcessNode, ProcessParticipant } from '@/types/process';

const participant = (id: string, order: number): ProcessParticipant => ({ id, name: id, guard: id, actorType: 'human', order, color: null });
const node = (id: string, y: number, participantId: string): ProcessNode => ({ id, type: 'action', position: { x: 100, y }, data: { participantId } });

describe('participant canvas layout', () => {
    it('expands a single participant to contain its lowest component', () => {
        const layouts = buildParticipantLayouts([participant('buyer', 0)], [node('n1', 780, 'buyer')]);
        expect(layouts[0]?.height).toBe(892);
    });

    it('keeps multiple participant bands stable so dragging can transfer ownership', () => {
        const layouts = buildParticipantLayouts([participant('buyer', 0), participant('seller', 1)], [node('n1', 900, 'buyer')]);
        expect(layouts).toEqual([{ id: 'buyer', top: 0, height: 220 }, { id: 'seller', top: 220, height: 220 }]);
        expect(participantForY(layouts, 300)?.id).toBe('seller');
    });

    it('constrains a component within its participant boundaries', () => {
        expect(constrainPositionToParticipant({ x: 10, y: -100 }, { id: 'buyer', top: 0, height: 220 }).y).toBe(32);
        expect(constrainPositionToParticipant({ x: 10, y: 999 }, { id: 'buyer', top: 0, height: 220 }).y).toBe(108);
    });

    it('normalizes existing components that were stored outside their participant', () => {
        const nodes = normalizeParticipantPositions([participant('buyer', 0), participant('seller', 1)], [node('n1', -80, 'buyer'), node('n2', 900, 'seller')]);
        expect(nodes.map((item) => item.position.y)).toEqual([32, 328]);
    });
});

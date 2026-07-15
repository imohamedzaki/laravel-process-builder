import { describe, expect, it } from 'vitest';
import { isConnectionAllowed } from '@/canvas/connectionRules';

describe('isConnectionAllowed', () => {
    it('allows route to controller', () => {
        expect(isConnectionAllowed('route', 'controller')).toBe(true);
    });

    it('allows controller to action', () => {
        expect(isConnectionAllowed('controller', 'action')).toBe(true);
    });

    it('allows normal pipeline components to connect directly', () => {
        expect(isConnectionAllowed('route', 'event')).toBe(true);
    });

    it('rejects connections out of terminal components', () => {
        expect(isConnectionAllowed('end', 'event')).toBe(false);
    });

    it('requires the correct handle for condition branches', () => {
        expect(isConnectionAllowed('condition', 'action', 'success')).toBe(true);
        expect(isConnectionAllowed('condition', 'end', 'success')).toBe(false);
        expect(isConnectionAllowed('condition', 'end', 'failure')).toBe(true);
    });

    it('rejects condition connections with no handle specified', () => {
        expect(isConnectionAllowed('condition', 'action')).toBe(false);
    });
});

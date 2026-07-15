import type { NodeType } from '@/types/process';

interface AllowedConnection {
    source: NodeType;
    sourceHandle?: string;
    target: NodeType;
}

const ALLOWED_CONNECTIONS: AllowedConnection[] = [
    { source: 'route', target: 'middleware' },
    { source: 'route', target: 'form_request' },
    { source: 'route', target: 'controller' },

    { source: 'middleware', target: 'form_request' },
    { source: 'middleware', target: 'controller' },

    { source: 'form_request', target: 'controller' },

    { source: 'controller', target: 'action' },
    { source: 'controller', target: 'response' },

    { source: 'action', target: 'transaction' },
    { source: 'action', target: 'model_create' },
    { source: 'action', target: 'model_update' },
    { source: 'action', target: 'condition' },
    { source: 'action', target: 'event' },
    { source: 'action', target: 'job' },
    { source: 'action', target: 'response' },

    { source: 'transaction', target: 'model_create' },
    { source: 'transaction', target: 'model_update' },

    { source: 'model_create', target: 'event' },
    { source: 'model_create', target: 'job' },
    { source: 'model_create', target: 'response' },
    { source: 'model_create', target: 'condition' },

    { source: 'model_update', target: 'event' },
    { source: 'model_update', target: 'job' },
    { source: 'model_update', target: 'response' },
    { source: 'model_update', target: 'condition' },

    { source: 'condition', sourceHandle: 'success', target: 'action' },
    { source: 'condition', sourceHandle: 'success', target: 'event' },
    { source: 'condition', sourceHandle: 'success', target: 'job' },
    { source: 'condition', sourceHandle: 'success', target: 'response' },

    { source: 'condition', sourceHandle: 'failure', target: 'action' },
    { source: 'condition', sourceHandle: 'failure', target: 'response' },
    { source: 'condition', sourceHandle: 'failure', target: 'end' },

    { source: 'event', target: 'job' },
    { source: 'event', target: 'response' },

    { source: 'job', target: 'response' },
    { source: 'response', target: 'end' },
];

export function isConnectionAllowed(
    sourceType: NodeType,
    targetType: NodeType,
    sourceHandle?: string | null,
): boolean {
    return ALLOWED_CONNECTIONS.some((rule) => {
        if (rule.source !== sourceType || rule.target !== targetType) {
            return false;
        }

        if (rule.sourceHandle && rule.sourceHandle !== sourceHandle) {
            return false;
        }

        return true;
    });
}

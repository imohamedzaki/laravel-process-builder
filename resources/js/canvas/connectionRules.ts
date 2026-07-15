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

const PIPELINE_RANK: Record<NodeType, number> = {
    start: 0, route: 10, middleware: 20, form_request: 30, authorization: 35,
    controller: 40, action: 50, service: 55, transaction: 60, model: 70,
    model_create: 70, model_update: 70, model_delete: 70, condition: 75,
    event: 80, job: 85, notification: 90, api_resource: 95, response: 100,
    success: 110, failure: 110, exception: 110, end: 110,
};

export function shouldReverseConnection(sourceType: NodeType, targetType: NodeType): boolean {
    if (sourceType === 'condition' || targetType === 'condition') return false;
    return PIPELINE_RANK[sourceType] > PIPELINE_RANK[targetType];
}

export function isConnectionAllowed(
    sourceType: NodeType,
    targetType: NodeType,
    sourceHandle?: string | null,
): boolean {
    if (targetType === 'start' || ['end', 'success', 'failure', 'exception'].includes(sourceType)) {
        return false;
    }

    if (sourceType === 'start') {
        return true;
    }

    if (sourceType === 'response') {
        return targetType === 'end';
    }

    if (sourceType !== 'condition') {
        return !shouldReverseConnection(sourceType, targetType);
    }

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

export function allowedTargetTypes(sourceType: NodeType, sourceHandle?: string | null): NodeType[] {
    return IMPLEMENTED_TARGETS.filter((target) => isConnectionAllowed(sourceType, target, sourceHandle));
}

const IMPLEMENTED_TARGETS: NodeType[] = ['route', 'middleware', 'form_request', 'controller', 'action', 'transaction', 'condition', 'model_create', 'model_update', 'event', 'job', 'api_resource', 'response', 'end'];

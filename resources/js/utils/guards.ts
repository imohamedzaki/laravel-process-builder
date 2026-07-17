import type { ProcessDefinition } from '@/types/process';
import type { ProjectSummary } from '@/types/project';

export function discoverGuards(summary: ProjectSummary | null): string[] {
    if (!summary) return ['web'];
    const names = new Set<string>();
    summary.routes.forEach((route) => route.middleware.forEach((middleware) => {
        const name = middleware.split(':')[0]?.trim();
        if (name && !['bindings', 'throttle', 'signed', 'verified'].includes(name)) names.add(name);
    }));
    return names.size > 0 ? [...names].sort() : ['web'];
}

export function participantGuards(process: ProcessDefinition): string[] {
    return [...new Set(process.participants.map((participant) => participant.guard))].sort();
}

export function descriptionForGuard(guard: string): string {
    return `[guard:${guard}] Workflow generated for the ${guard} guard.`;
}

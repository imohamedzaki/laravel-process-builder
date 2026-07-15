import { apiClient } from '@/api/client';
import type { ApiEnvelope } from '@/types/project';
import type { ProcessDefinition, ValidationResult } from '@/types/process';

export interface CreateProcessPayload {
    name: string;
    slug: string;
    description?: string | null;
    entryNodeId?: string | null;
    nodes?: ProcessDefinition['nodes'];
    edges?: ProcessDefinition['edges'];
}

export type UpdateProcessPayload = CreateProcessPayload;

export async function fetchProcesses(): Promise<ProcessDefinition[]> {
    const response = await apiClient.get<ApiEnvelope<ProcessDefinition[]>>('/processes');

    return response.data.data;
}

export async function fetchProcess(idOrSlug: string): Promise<ProcessDefinition> {
    const response = await apiClient.get<ApiEnvelope<ProcessDefinition>>(`/processes/${idOrSlug}`);

    return response.data.data;
}

export async function createProcess(payload: CreateProcessPayload): Promise<ProcessDefinition> {
    const response = await apiClient.post<ApiEnvelope<ProcessDefinition>>('/processes', payload);

    return response.data.data;
}

export async function updateProcess(idOrSlug: string, payload: UpdateProcessPayload): Promise<ProcessDefinition> {
    const response = await apiClient.put<ApiEnvelope<ProcessDefinition>>(`/processes/${idOrSlug}`, payload);

    return response.data.data;
}

export async function deleteProcess(idOrSlug: string): Promise<void> {
    await apiClient.delete(`/processes/${idOrSlug}`);
}

export async function duplicateProcess(idOrSlug: string): Promise<ProcessDefinition> {
    const response = await apiClient.post<ApiEnvelope<ProcessDefinition>>(`/processes/${idOrSlug}/duplicate`);

    return response.data.data;
}

export async function validateProcess(idOrSlug: string): Promise<ValidationResult> {
    const response = await apiClient.post<ApiEnvelope<ValidationResult>>(`/processes/${idOrSlug}/validate`);

    return response.data.data;
}

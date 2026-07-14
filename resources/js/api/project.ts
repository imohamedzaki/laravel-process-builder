import { apiClient } from '@/api/client';
import type { ApiEnvelope, ControllerInfo, ProjectSummary, RouteInfo } from '@/types/project';

export async function fetchProjectSummary(): Promise<ProjectSummary> {
    const response = await apiClient.get<ApiEnvelope<ProjectSummary>>('/project');

    return response.data.data;
}

export async function fetchProjectRoutes(): Promise<RouteInfo[]> {
    const response = await apiClient.get<ApiEnvelope<RouteInfo[]>>('/project/routes');

    return response.data.data;
}

export async function fetchProjectControllers(): Promise<ControllerInfo[]> {
    const response = await apiClient.get<ApiEnvelope<ControllerInfo[]>>('/project/controllers');

    return response.data.data;
}

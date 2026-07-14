export interface DashboardConfig {
    appName: string;
    tagline: string;
    version: string;
}

export interface HealthResponse {
    data: {
        status: 'ok';
        version: string;
        generationEnabled: boolean;
        environment: string;
    };
    meta: Record<string, unknown>;
    errors: unknown[];
}

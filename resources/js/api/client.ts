import axios, { type AxiosInstance } from 'axios';

function resolveBasePath(): string {
    const meta = document.querySelector('meta[name="process-builder-base-path"]');
    const basePath = meta?.getAttribute('content') ?? 'process-builder';

    return `/${basePath.replace(/^\/|\/$/g, '')}`;
}

export function createApiClient(): AxiosInstance {
    const client = axios.create({
        baseURL: `${resolveBasePath()}/api`,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    });

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta?.getAttribute('content');

    if (csrfToken) {
        client.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }

    return client;
}

export const apiClient = createApiClient();

import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from '@/components/App';
import type { DashboardConfig } from '@/types/dashboard';
import '@/../css/app.css';

const rootElement = document.getElementById('process-builder-root');

if (rootElement) {
    const config: DashboardConfig = {
        appName: rootElement.dataset.appName ?? 'Laravel Process Builder',
        tagline: rootElement.dataset.tagline ?? '',
        version: rootElement.dataset.version ?? '0.0.0',
    };

    createRoot(rootElement).render(
        <StrictMode>
            <App config={config} />
        </StrictMode>,
    );
}

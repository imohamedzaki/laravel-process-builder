import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: 'dist',
        manifest: true,
        rollupOptions: {
            input: 'resources/js/app.tsx',
        },
    },
    server: {
        port: 5173,
        strictPort: true,
        cors: true,
        origin: 'http://localhost:5173',
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/test-setup.ts'],
    },
});

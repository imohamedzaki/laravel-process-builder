import { create } from 'zustand';
import { fetchProjectSummary } from '@/api/project';
import type { ProjectSummary } from '@/types/project';

interface ProjectState {
    summary: ProjectSummary | null;
    isLoading: boolean;
    error: string | null;
    scan: () => Promise<void>;
}

export const useProjectStore = create<ProjectState>((set) => ({
    summary: null,
    isLoading: false,
    error: null,
    scan: async () => {
        set({ isLoading: true, error: null });

        try {
            const summary = await fetchProjectSummary();
            set({ summary, isLoading: false });
        } catch {
            set({ isLoading: false, error: 'Unable to scan the project.' });
        }
    },
}));

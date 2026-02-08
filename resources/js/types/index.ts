export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';

export type StorageUsage = {
    totalBytes: number;
    freeBytes: number;
    usedBytes: number;
    usedPercent: number;
};

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    storageUsage: StorageUsage | null;
    [key: string]: unknown;
};

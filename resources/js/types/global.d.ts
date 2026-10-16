import type { Auth } from '@/types/auth';
import type { NotificationsSummary, PushConfig } from '@/types/notifications';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            locale: string;
            auth: Auth;
            sidebarOpen: boolean;
            notifications: NotificationsSummary | null;
            push: PushConfig;
            [key: string]: unknown;
        };
    }
}

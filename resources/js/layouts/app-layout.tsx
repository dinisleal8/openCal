import { AppTopbar } from '@/components/app-topbar';
import { BottomNav } from '@/components/bottom-nav';
import type { AppLayoutProps } from '@/types';

export default function AppLayout({ children }: AppLayoutProps) {
    return (
        <div className="bg-muted/40 flex min-h-dvh flex-col">
            <AppTopbar />
            <main className="flex-1 pt-14 pb-[calc(4rem_+_env(safe-area-inset-bottom))]">
                {children}
            </main>
            <BottomNav />
        </div>
    );
}

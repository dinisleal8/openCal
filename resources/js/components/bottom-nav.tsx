import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    Home,
    Settings,
    Target,
    type LucideIcon,
} from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n, type TranslationKey } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { dashboard, history } from '@/routes';
import { edit as editGoal } from '@/routes/goal';
import { edit as editProfile } from '@/routes/profile';

type NavItem = {
    key: TranslationKey;
    href: string;
    icon: LucideIcon;
    startsWith?: boolean;
};

export function BottomNav() {
    const { t } = useI18n();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const items: NavItem[] = [
        { key: 'nav.today', href: dashboard().url, icon: Home },
        { key: 'nav.history', href: history().url, icon: CalendarDays },
        { key: 'nav.goals', href: editGoal().url, icon: Target },
        {
            key: 'nav.settings',
            href: editProfile().url,
            icon: Settings,
            startsWith: true,
        },
    ];

    return (
        <nav
            className="bg-background/95 fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] backdrop-blur-md"
            aria-label="Main"
        >
            <div className="mx-auto flex h-16 w-full max-w-xl items-stretch justify-around px-2">
                {items.map((item) => {
                    const active = item.startsWith
                        ? isCurrentOrParentUrl(item.href)
                        : isCurrentUrl(item.href);

                    return (
                        <Link
                            key={item.key}
                            href={item.href}
                            prefetch
                            className={cn(
                                'flex flex-1 flex-col items-center justify-center gap-1 rounded-lg text-[11px] font-medium transition-colors',
                                active
                                    ? 'text-primary'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <item.icon
                                className={cn(
                                    'size-5.5',
                                    active && 'stroke-[2.5]',
                                )}
                            />
                            {t(item.key)}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}

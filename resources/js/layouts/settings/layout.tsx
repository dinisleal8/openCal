import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import {
    Palette,
    Plug,
    Settings2,
    ShieldCheck,
    User,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useI18n, type TranslationKey } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { index as indexIntegrations } from '@/routes/integrations';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as indexUsers } from '@/routes/users';

type NavItem = {
    key: TranslationKey;
    href: string;
    icon: LucideIcon;
};

const navItems: NavItem[] = [
    { key: 'settings.navProfile', href: edit().url, icon: User },
    {
        key: 'settings.navSecurity',
        href: editSecurity().url,
        icon: ShieldCheck,
    },
    {
        key: 'settings.navAppearance',
        href: editAppearance().url,
        icon: Palette,
    },
    { key: 'settings.users', href: indexUsers().url, icon: Users },
    { key: 'settings.integrations', href: indexIntegrations().url, icon: Plug },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useI18n();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto w-full max-w-xl px-5 py-6">
            <div className="mb-6 flex items-center gap-3">
                <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                    <Settings2 className="size-5" />
                </span>
                <div>
                    <h1 className="text-xl font-bold">{t('settings.title')}</h1>
                    <p className="text-muted-foreground text-sm">
                        {t('settings.description')}
                    </p>
                </div>
            </div>

            <nav
                className="bg-muted/50 mb-6 flex gap-1 overflow-x-auto rounded-xl p-1"
                aria-label={t('settings.title')}
            >
                {navItems.map((item) => {
                    const active = isCurrentOrParentUrl(item.href);

                    return (
                        <Link
                            key={item.key}
                            href={item.href}
                            className={cn(
                                'flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                                active
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <item.icon className="size-4" />
                            <span>{t(item.key)}</span>
                        </Link>
                    );
                })}
            </nav>

            <section className="space-y-6">{children}</section>
        </div>
    );
}

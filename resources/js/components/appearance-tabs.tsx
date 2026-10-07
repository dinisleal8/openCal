import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useI18n, type TranslationKey } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useI18n();

    const tabs: {
        value: Appearance;
        icon: LucideIcon;
        label: TranslationKey;
    }[] = [
        { value: 'light', icon: Sun, label: 'settings.appearanceLight' },
        { value: 'dark', icon: Moon, label: 'settings.appearanceDark' },
        { value: 'system', icon: Monitor, label: 'settings.appearanceSystem' },
    ];

    return (
        <div
            className={cn(
                'bg-muted/50 inline-flex w-full gap-1 rounded-xl p-1',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm transition-colors',
                        appearance === value
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    <Icon className="size-4" />
                    <span>{t(label)}</span>
                </button>
            ))}
        </div>
    );
}

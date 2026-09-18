import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    selected: boolean;
    onSelect: () => void;
    title: string;
    description?: string;
    icon?: LucideIcon;
    trailing?: React.ReactNode;
};

export function OptionCard({
    selected,
    onSelect,
    title,
    description,
    icon: Icon,
    trailing,
}: Props) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={selected}
            onClick={onSelect}
            className={cn(
                'flex w-full items-center gap-3 rounded-xl border p-3.5 text-left transition-all',
                selected
                    ? 'border-primary bg-primary/5 ring-primary ring-1'
                    : 'border-border bg-card hover:border-primary/40',
            )}
        >
            {Icon && (
                <span
                    className={cn(
                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                        selected
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground',
                    )}
                >
                    <Icon className="size-4.5" />
                </span>
            )}
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-medium">{title}</span>
                {description && (
                    <span className="text-muted-foreground mt-0.5 block text-xs leading-snug">
                        {description}
                    </span>
                )}
            </span>
            {trailing}
        </button>
    );
}

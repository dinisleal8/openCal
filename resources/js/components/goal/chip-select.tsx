import { cn } from '@/lib/utils';

type Option<T extends string> = {
    value: T;
    label: string;
};

type Props<T extends string> = {
    options: Option<T>[];
    value: string;
    onChange: (value: T) => void;
    className?: string;
};

export function ChipSelect<T extends string>({
    options,
    value,
    onChange,
    className,
}: Props<T>) {
    return (
        <div className={cn('flex flex-wrap gap-2', className)}>
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    role="radio"
                    aria-checked={value === option.value}
                    onClick={() => onChange(option.value)}
                    className={cn(
                        'rounded-full border px-4 py-2 text-sm font-medium transition-colors',
                        value === option.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card text-muted-foreground hover:border-primary/40 hover:text-foreground',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

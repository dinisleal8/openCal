import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    suffix?: string;
    placeholder?: string;
    error?: string;
    min?: number;
    max?: number;
    step?: string;
    type?: 'number' | 'date';
    className?: string;
    required?: boolean;
};

export function NumberField({
    id,
    label,
    value,
    onChange,
    suffix,
    placeholder,
    error,
    min,
    max,
    step,
    type = 'number',
    className,
    required,
}: Props) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{label}</Label>
            <div className="relative">
                <Input
                    id={id}
                    type={type}
                    inputMode={type === 'number' ? 'decimal' : undefined}
                    value={value}
                    min={min}
                    max={max}
                    step={step}
                    required={required}
                    placeholder={placeholder}
                    onChange={(e) => onChange(e.target.value)}
                    className={cn(
                        suffix && 'pr-12',
                        error &&
                            'border-destructive focus-visible:ring-destructive',
                    )}
                />
                {suffix && (
                    <span className="text-muted-foreground pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2 text-sm">
                        {suffix}
                    </span>
                )}
            </div>
            {error && <p className="text-destructive text-xs">{error}</p>}
        </div>
    );
}

import { Flame } from 'lucide-react';
import { useI18n } from '@/lib/i18n';
import type { TargetEstimate } from '@/lib/goal-math';

type Props = {
    estimate: TargetEstimate | null;
    hint: string;
    compact?: boolean;
};

export function TargetsPreview({ estimate, hint, compact = false }: Props) {
    const { t } = useI18n();

    if (!estimate) {
        return (
            <div className="bg-card rounded-2xl border p-5">
                <p className="text-muted-foreground text-sm">{hint}</p>
            </div>
        );
    }

    return (
        <div className="bg-card overflow-hidden rounded-2xl border">
            <div className="from-primary/15 to-primary/5 bg-gradient-to-br p-5">
                <div className="flex items-center gap-3">
                    <span className="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-xl">
                        <Flame className="size-5.5" />
                    </span>
                    <div>
                        <p className="text-muted-foreground text-xs font-medium">
                            {t('onboarding.previewTitle')}
                        </p>
                        <p className="text-2xl leading-tight font-bold tracking-tight">
                            {estimate.calorieTarget.toLocaleString()}{' '}
                            <span className="text-muted-foreground text-sm font-medium">
                                {t('common.kcal')}
                            </span>
                        </p>
                    </div>
                </div>

                {!compact && (
                    <div className="mt-4 grid grid-cols-3 gap-2">
                        <MacroPill
                            label={t('goals.protein')}
                            value={`${estimate.proteinG}${t('common.g')}`}
                        />
                        <MacroPill
                            label={t('goals.carbs')}
                            value={`${estimate.carbsG}${t('common.g')}`}
                        />
                        <MacroPill
                            label={t('goals.fat')}
                            value={`${estimate.fatG}${t('common.g')}`}
                        />
                    </div>
                )}
            </div>

            {!compact && (
                <div className="text-muted-foreground flex items-center justify-between px-5 py-3 text-xs">
                    <span>
                        {t('goals.bmr')}: {estimate.bmr.toLocaleString()}{' '}
                        {t('common.kcal')}
                    </span>
                    <span>
                        {t('onboarding.maintenance')}:{' '}
                        {estimate.tdee.toLocaleString()} {t('common.kcal')}
                    </span>
                </div>
            )}
        </div>
    );
}

function MacroPill({ label, value }: { label: string; value: string }) {
    return (
        <div className="bg-background/70 rounded-lg px-2.5 py-2 text-center">
            <p className="text-[11px] font-medium opacity-70">{label}</p>
            <p className="text-sm font-semibold">{value}</p>
        </div>
    );
}

import { Head, router } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ChipSelect } from '@/components/goal/chip-select';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';
import type { WeighIn } from '@/types/models';

type DayRow = {
    date: string;
    calories: number;
    target: number;
    logged: boolean;
    water: number;
    exercise: number;
};

type Props = {
    days: DayRow[];
    dayCount: number;
    weightHistory: WeighIn[];
    averages: {
        calories: number;
        water: number;
        exercise: number;
    };
};

export default function History({
    days,
    dayCount,
    weightHistory,
    averages,
}: Props) {
    const { t } = useI18n();

    const weekSize = 7;
    const [weekIndex, setWeekIndex] = useState(0);

    const pages = useMemo<DayRow[][]>(() => {
        if (dayCount !== 30) {
            return [days];
        }

        const chunks: DayRow[][] = [];

        for (let end = days.length; end > 0; end -= weekSize) {
            chunks.push(days.slice(Math.max(0, end - weekSize), end));
        }

        return chunks;
    }, [days, dayCount]);

    const visibleDays = pages[weekIndex] ?? days;

    useEffect(() => {
        setWeekIndex(0);
    }, [dayCount]);

    const formatShortDate = (iso: string) =>
        new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });

    const rangeLabel =
        visibleDays.length > 0
            ? `${formatShortDate(visibleDays[0].date)} – ${formatShortDate(
                  visibleDays[visibleDays.length - 1].date,
              )}`
            : '';

    const chartWidth = 600;
    const chartHeight = 120;
    const padding = 30;

    function renderWeightChart() {
        if (weightHistory.length < 2) {
            return (
                <p className="text-muted-foreground text-center text-sm">
                    {t('history.weightProgress')}
                </p>
            );
        }

        const weights = weightHistory.map((w) => parseFloat(w.weight_kg));
        const minWeight = Math.min(...weights) - 2;
        const maxWeight = Math.max(...weights) + 2;
        const range = maxWeight - minWeight;

        const points = weightHistory.map((w, i) => {
            const x =
                padding +
                (i / (weightHistory.length - 1)) * (chartWidth - padding * 2);
            const y =
                chartHeight -
                padding -
                ((parseFloat(w.weight_kg) - minWeight) / range) *
                    (chartHeight - padding * 2);
            return { x, y, weight: w.weight_kg };
        });

        const polyline = points.map((p) => `${p.x},${p.y}`).join(' ');

        return (
            <svg
                viewBox={`0 0 ${chartWidth} ${chartHeight}`}
                className="h-[120px] w-full"
                preserveAspectRatio="none"
            >
                <polyline
                    points={polyline}
                    fill="none"
                    stroke="hsl(var(--primary))"
                    strokeWidth="2"
                    strokeLinejoin="round"
                    strokeLinecap="round"
                />
                {points.map((p, i) => (
                    <circle
                        key={i}
                        cx={p.x}
                        cy={p.y}
                        r="3"
                        fill="hsl(var(--primary))"
                    />
                ))}
                <text
                    x={points[0].x}
                    y={points[0].y - 8}
                    textAnchor="middle"
                    className="fill-foreground text-[10px]"
                >
                    {points[0].weight}
                </text>
                <text
                    x={points[points.length - 1].x}
                    y={points[points.length - 1].y - 8}
                    textAnchor="middle"
                    className="fill-foreground text-[10px]"
                >
                    {points[points.length - 1].weight}
                </text>
            </svg>
        );
    }

    return (
        <>
            <Head title={t('history.title')} />
            <div className="mx-auto w-full max-w-xl px-5 py-6">
                <div className="mb-6 flex items-center gap-3">
                    <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                        <CalendarDays className="size-5" />
                    </span>
                    <div>
                        <h1 className="text-xl font-bold">
                            {t('history.title')}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {dayCount === 7
                                ? t('history.last7')
                                : t('history.last30')}
                        </p>
                    </div>
                </div>

                {/* Day toggle */}
                <ChipSelect
                    options={[
                        { value: '7', label: t('history.last7') },
                        { value: '30', label: t('history.last30') },
                    ]}
                    value={String(dayCount)}
                    onChange={(v) =>
                        router.get(
                            '/history',
                            { days: v },
                            { preserveScroll: true },
                        )
                    }
                    className="mb-6"
                />

                {/* Weight trend chart */}
                {weightHistory.length > 0 && (
                    <div className="bg-card mb-6 rounded-2xl border p-4">
                        <p className="text-muted-foreground mb-2 text-xs font-medium">
                            {t('history.weightProgress')}
                        </p>
                        {renderWeightChart()}
                    </div>
                )}

                {/* Averages summary */}
                <div className="bg-card mb-6 grid grid-cols-3 gap-3 rounded-2xl border p-4">
                    <div className="text-center">
                        <p className="text-muted-foreground text-xs">
                            {t('history.avgEaten')}
                        </p>
                        <p className="text-lg font-bold">{averages.calories}</p>
                        <p className="text-muted-foreground text-[11px]">
                            {t('common.kcal')}
                        </p>
                    </div>
                    <div className="text-center">
                        <p className="text-muted-foreground text-xs">
                            {t('history.avgBurned')}
                        </p>
                        <p className="text-lg font-bold">{averages.exercise}</p>
                        <p className="text-muted-foreground text-[11px]">
                            {t('common.kcal')}
                        </p>
                    </div>
                    <div className="text-center">
                        <p className="text-muted-foreground text-xs">
                            {t('goals.water')}
                        </p>
                        <p className="text-lg font-bold">{averages.water}</p>
                        <p className="text-muted-foreground text-[11px]">
                            {t('common.ml')}
                        </p>
                    </div>
                </div>

                {/* Week pagination (30-day view) */}
                {dayCount === 30 && pages.length > 1 && (
                    <div className="mb-4 flex items-center justify-between gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={weekIndex >= pages.length - 1}
                            onClick={() => setWeekIndex((i) => i + 1)}
                            className="gap-1"
                        >
                            <ChevronLeft className="size-4" />
                            <span className="hidden sm:inline">
                                {t('history.previousWeek')}
                            </span>
                        </Button>

                        <div className="text-center">
                            <p className="text-xs font-medium">{rangeLabel}</p>
                            <p className="text-muted-foreground text-[11px]">
                                {t('history.weekOf', {
                                    current: weekIndex + 1,
                                    total: pages.length,
                                })}
                            </p>
                        </div>

                        <Button
                            variant="outline"
                            size="sm"
                            disabled={weekIndex === 0}
                            onClick={() => setWeekIndex((i) => i - 1)}
                            className="gap-1"
                        >
                            <span className="hidden sm:inline">
                                {t('history.nextWeek')}
                            </span>
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                )}

                {/* Day list */}
                <div className="space-y-2">
                    {visibleDays.map((day) => {
                        const pct =
                            day.target > 0
                                ? Math.min(
                                      (day.calories / day.target) * 100,
                                      100,
                                  )
                                : 0;

                        return (
                            <div
                                key={day.date}
                                className="bg-card flex items-center gap-3 rounded-xl border p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            {new Date(
                                                day.date + 'T00:00:00',
                                            ).toLocaleDateString(undefined, {
                                                weekday: 'short',
                                                month: 'short',
                                                day: 'numeric',
                                            })}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {day.calories} / {day.target}{' '}
                                            {t('common.kcal')}
                                        </span>
                                    </div>
                                    <div className="bg-muted mt-1.5 h-1.5 rounded-full">
                                        <div
                                            className="bg-primary h-full rounded-full transition-all"
                                            style={{ width: `${pct}%` }}
                                        />
                                    </div>
                                </div>
                                {day.logged && (
                                    <div className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-[10px] font-medium">
                                        ✓
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {visibleDays.every((d) => !d.logged) && (
                    <p className="text-muted-foreground mt-8 text-center text-sm">
                        {t('history.noData')}
                    </p>
                )}
            </div>
        </>
    );
}

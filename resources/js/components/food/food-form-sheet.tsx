import { router, useForm } from '@inertiajs/react';
import { Camera, ImagePlus, Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ChipSelect } from '@/components/goal/chip-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import {
    bulk as foodBulk,
    store as foodStore,
    update as foodUpdate,
} from '@/routes/food';
import type { FoodEntry, MealType } from '@/types/models';

type AiItem = {
    name?: string;
    calories?: number;
    protein_g?: number;
    carbs_g?: number;
    fat_g?: number;
    serving_description?: string;
};

type DetectedItem = AiItem & { selected: boolean };

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mealType: MealType;
    entry?: FoodEntry;
    date: string;
    frequentFoods?: Array<{
        name: string;
        calories_kcal: string;
        protein_g: string;
        carbs_g: string;
        fat_g: string;
        serving_description: string | null;
        times_logged: number;
    }>;
};

const aiMaxDimension = 1280;

const round = (value: number | string | undefined): number =>
    Math.round(Number(value ?? 0));

/**
 * Re-encode the selected image to JPEG so any browser-decodable format
 * (AVIF, HEIC on Safari, etc.) is accepted by the AI provider. Also caps the
 * longest edge to keep token usage and payload size down.
 */
async function toProviderImage(
    file: File,
): Promise<{ blob: Blob; name: string }> {
    if (typeof createImageBitmap !== 'function') {
        return { blob: file, name: file.name };
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(
            1,
            aiMaxDimension / Math.max(bitmap.width, bitmap.height),
        );
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');

        if (!context) {
            bitmap.close?.();

            return { blob: file, name: file.name };
        }

        context.drawImage(bitmap, 0, 0, width, height);
        bitmap.close?.();

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', 0.85),
        );

        if (!blob) {
            return { blob: file, name: file.name };
        }

        return {
            blob,
            name: file.name.replace(/\.[^.]+$/, '') + '.jpg',
        };
    } catch {
        return { blob: file, name: file.name };
    }
}

export function FoodFormSheet({
    open,
    onOpenChange,
    mealType,
    entry,
    date,
    frequentFoods = [],
}: Props) {
    const { t } = useI18n();
    const isEditing = !!entry;
    const fileRef = useRef<HTMLInputElement>(null);

    const [mode, setMode] = useState<'photo' | 'manual'>(
        isEditing ? 'manual' : 'photo',
    );
    const [analyzing, setAnalyzing] = useState(false);
    const [aiSuggestion, setAiSuggestion] = useState<string | null>(null);
    const [detected, setDetected] = useState<DetectedItem[]>([]);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [photoId, setPhotoId] = useState<number | null>(
        entry?.photo_id ?? null,
    );
    const [submitting, setSubmitting] = useState(false);

    const mealOptions: { value: MealType; label: string }[] = [
        { value: 'breakfast', label: t('dashboard.breakfast') },
        { value: 'lunch', label: t('dashboard.lunch') },
        { value: 'dinner', label: t('dashboard.dinner') },
        { value: 'snack', label: t('dashboard.snack') },
    ];

    const form = useForm({
        date,
        meal_type: entry?.meal_type ?? mealType,
        name: entry?.name ?? '',
        serving_description: entry?.serving_description ?? '',
        calories_kcal: entry?.calories_kcal ?? '',
        protein_g: entry?.protein_g ?? '',
        carbs_g: entry?.carbs_g ?? '',
        fat_g: entry?.fat_g ?? '',
        photo_id: entry?.photo_id ? String(entry.photo_id) : '',
    });

    const { data, setData, post, put, processing, errors, reset } = form;

    const selectedItems = detected.filter((item) => item.selected);
    const allSelected =
        detected.length > 0 && detected.every((i) => i.selected);

    const totals = selectedItems.reduce(
        (acc, item) => ({
            calories: acc.calories + Number(item.calories ?? 0),
            protein: acc.protein + Number(item.protein_g ?? 0),
            carbs: acc.carbs + Number(item.carbs_g ?? 0),
            fat: acc.fat + Number(item.fat_g ?? 0),
        }),
        { calories: 0, protein: 0, carbs: 0, fat: 0 },
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = {
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
            preserveScroll: true,
        };

        if (isEditing) {
            put(foodUpdate(entry.id).url, opts);
        } else {
            post(foodStore().url, opts);
        }
    };

    const handlePhotoAnalysis = async (file: File) => {
        setAnalyzing(true);
        setAiSuggestion(null);
        setDetected([]);

        try {
            const { blob, name } = await toProviderImage(file);
            const formData = new FormData();
            formData.append('photo', blob, name);

            const response = await fetch('/api/photo/analyze', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                    ),
                },
                body: formData,
            });

            if (!response.ok) {
                setAiSuggestion(t('food.aiFailed'));

                return;
            }

            const result = await response.json();

            if (result.photo_id) {
                setPhotoId(Number(result.photo_id));
                setData('photo_id', String(result.photo_id));
            }

            const items: AiItem[] | null = Array.isArray(result.analysis)
                ? result.analysis
                : null;

            if (items === null) {
                setAiSuggestion(t('food.aiFailed'));
            } else if (items.length > 0) {
                setDetected(items.map((item) => ({ ...item, selected: true })));
            } else {
                setAiSuggestion(t('food.aiNoItems'));
            }
        } catch {
            setAiSuggestion(t('food.aiFailed'));
        } finally {
            setAnalyzing(false);
        }
    };

    const toggleAll = () =>
        setDetected((prev) =>
            prev.map((i) => ({ ...i, selected: !allSelected })),
        );

    const toggleItem = (index: number) =>
        setDetected((prev) =>
            prev.map((item, i) =>
                i === index ? { ...item, selected: !item.selected } : item,
            ),
        );

    const submitBulk = () => {
        if (selectedItems.length === 0) {
            return;
        }

        setSubmitting(true);

        router.post(
            foodBulk().url,
            {
                date,
                meal_type: data.meal_type,
                photo_id: photoId,
                items: selectedItems.map((item) => ({
                    name: item.name ?? 'Unknown',
                    serving_description: item.serving_description ?? null,
                    calories_kcal: Number(item.calories ?? 0),
                    protein_g: Number(item.protein_g ?? 0),
                    carbs_g: Number(item.carbs_g ?? 0),
                    fat_g: Number(item.fat_g ?? 0),
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    useEffect(() => {
        if (open) {
            return;
        }

        setPreviewUrl(null);
        setDetected([]);
        setAiSuggestion(null);
        setMode(isEditing ? 'manual' : 'photo');
        setPhotoId(entry?.photo_id ?? null);

        if (fileRef.current) {
            fileRef.current.value = '';
        }
    }, [open, isEditing, entry?.photo_id]);

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] gap-0 overflow-y-auto p-0 sm:max-w-lg">
                <DialogHeader className="border-b px-5 py-4">
                    <DialogTitle>
                        {isEditing ? t('food.editTitle') : t('food.title')}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4 px-5 py-4">
                    <div>
                        <p className="text-muted-foreground mb-1.5 text-xs font-medium">
                            {t('food.meal')}
                        </p>
                        <ChipSelect
                            options={mealOptions}
                            value={data.meal_type}
                            onChange={(value) => setData('meal_type', value)}
                        />
                    </div>

                    <input
                        ref={fileRef}
                        type="file"
                        accept="image/*"
                        capture="environment"
                        className="hidden"
                        onChange={(e) => {
                            const file = e.target.files?.[0];

                            if (!file) {
                                return;
                            }

                            setPreviewUrl(URL.createObjectURL(file));
                            void handlePhotoAnalysis(file);
                        }}
                    />

                    {mode === 'photo' && !isEditing ? (
                        <div className="space-y-3">
                            {detected.length === 0 ? (
                                <button
                                    type="button"
                                    onClick={() => fileRef.current?.click()}
                                    disabled={analyzing}
                                    className="border-border bg-muted/30 hover:bg-muted/60 flex w-full flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-6 py-10 transition-colors disabled:opacity-60"
                                >
                                    {previewUrl ? (
                                        <img
                                            src={previewUrl}
                                            alt=""
                                            className="max-h-44 rounded-xl object-contain"
                                        />
                                    ) : analyzing ? (
                                        <Loader2 className="text-primary size-8 animate-spin" />
                                    ) : (
                                        <Camera className="text-muted-foreground size-8" />
                                    )}
                                    <span className="text-sm font-medium">
                                        {analyzing
                                            ? t('food.aiAnalyzing')
                                            : previewUrl
                                              ? t('food.replacePhoto')
                                              : t('food.takePhoto')}
                                    </span>
                                    {!previewUrl && !analyzing && (
                                        <span className="text-muted-foreground text-xs">
                                            {t('food.photoHint')}
                                        </span>
                                    )}
                                </button>
                            ) : (
                                <div className="flex items-center gap-3">
                                    {previewUrl && (
                                        <img
                                            src={previewUrl}
                                            alt=""
                                            className="size-14 shrink-0 rounded-lg object-cover"
                                        />
                                    )}
                                    <p className="text-muted-foreground flex-1 text-xs">
                                        {t('food.reviewItems')}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => fileRef.current?.click()}
                                        disabled={analyzing}
                                        aria-label={t('food.replacePhoto')}
                                    >
                                        <ImagePlus className="size-4" />
                                    </Button>
                                </div>
                            )}

                            {aiSuggestion && (
                                <p className="text-muted-foreground text-xs">
                                    {aiSuggestion}
                                </p>
                            )}

                            {detected.length > 0 && (
                                <>
                                    <div className="overflow-hidden rounded-xl border">
                                        <table className="w-full text-sm">
                                            <thead className="bg-muted/50">
                                                <tr className="text-muted-foreground text-left text-[11px] uppercase">
                                                    <th className="w-10 px-2 py-2">
                                                        <input
                                                            type="checkbox"
                                                            checked={
                                                                allSelected
                                                            }
                                                            onChange={toggleAll}
                                                            aria-label={t(
                                                                'food.selectAll',
                                                            )}
                                                            className="accent-primary size-4"
                                                        />
                                                    </th>
                                                    <th className="px-2 py-2 font-medium">
                                                        {t('food.name')}
                                                    </th>
                                                    <th className="px-2 py-2 text-right font-medium">
                                                        {t('common.kcal')}
                                                    </th>
                                                    <th className="hidden px-2 py-2 text-right font-medium sm:table-cell">
                                                        P
                                                    </th>
                                                    <th className="hidden px-2 py-2 text-right font-medium sm:table-cell">
                                                        C
                                                    </th>
                                                    <th className="hidden px-2 py-2 text-right font-medium sm:table-cell">
                                                        F
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {detected.map((item, i) => (
                                                    <tr
                                                        key={`${item.name}-${i}`}
                                                        className={cn(
                                                            'border-t',
                                                            !item.selected &&
                                                                'opacity-50',
                                                        )}
                                                    >
                                                        <td className="px-2 py-2 align-top">
                                                            <input
                                                                type="checkbox"
                                                                checked={
                                                                    item.selected
                                                                }
                                                                onChange={() =>
                                                                    toggleItem(
                                                                        i,
                                                                    )
                                                                }
                                                                className="accent-primary mt-0.5 size-4"
                                                            />
                                                        </td>
                                                        <td className="px-2 py-2">
                                                            <p className="text-sm font-medium">
                                                                {item.name}
                                                            </p>
                                                            <p className="text-muted-foreground text-xs">
                                                                {
                                                                    item.serving_description
                                                                }
                                                                <span className="sm:hidden">
                                                                    {' '}
                                                                    · P
                                                                    {round(
                                                                        item.protein_g,
                                                                    )}{' '}
                                                                    C
                                                                    {round(
                                                                        item.carbs_g,
                                                                    )}{' '}
                                                                    F
                                                                    {round(
                                                                        item.fat_g,
                                                                    )}
                                                                </span>
                                                            </p>
                                                        </td>
                                                        <td className="px-2 py-2 text-right text-sm tabular-nums">
                                                            {round(
                                                                item.calories,
                                                            )}
                                                        </td>
                                                        <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                            {round(
                                                                item.protein_g,
                                                            )}
                                                        </td>
                                                        <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                            {round(
                                                                item.carbs_g,
                                                            )}
                                                        </td>
                                                        <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                            {round(item.fat_g)}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                            <tfoot className="bg-muted/50 border-t">
                                                <tr className="font-medium">
                                                    <td className="px-2 py-2" />
                                                    <td className="px-2 py-2 text-xs">
                                                        {t(
                                                            'food.selectedTotal',
                                                        )}
                                                    </td>
                                                    <td className="px-2 py-2 text-right text-sm tabular-nums">
                                                        {round(totals.calories)}
                                                    </td>
                                                    <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                        {round(totals.protein)}
                                                    </td>
                                                    <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                        {round(totals.carbs)}
                                                    </td>
                                                    <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                                        {round(totals.fat)}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <Button
                                        type="button"
                                        className="w-full"
                                        disabled={
                                            selectedItems.length === 0 ||
                                            submitting
                                        }
                                        onClick={submitBulk}
                                    >
                                        {submitting
                                            ? t('common.saving')
                                            : t('food.addItems', {
                                                  count: selectedItems.length,
                                              })}
                                    </Button>
                                </>
                            )}

                            {!analyzing && (
                                <button
                                    type="button"
                                    onClick={() => setMode('manual')}
                                    className="text-muted-foreground hover:text-foreground w-full text-center text-xs underline"
                                >
                                    {t('food.switchToManual')}
                                </button>
                            )}
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {isEditing && entry?.photo?.url && (
                                <img
                                    src={entry.photo.url}
                                    alt=""
                                    className="h-32 w-full rounded-xl object-cover"
                                />
                            )}

                            {!isEditing && (
                                <button
                                    type="button"
                                    onClick={() => setMode('photo')}
                                    className="text-primary flex items-center gap-1.5 text-xs font-medium"
                                >
                                    <Camera className="size-3.5" />
                                    {t('food.switchToPhoto')}
                                </button>
                            )}

                            {frequentFoods.length > 0 && !isEditing && (
                                <div>
                                    <p className="text-muted-foreground mb-1.5 text-xs font-medium">
                                        {t('food.frequent')}
                                    </p>
                                    <div className="flex flex-wrap gap-1.5">
                                        {frequentFoods.map((food, i) => (
                                            <button
                                                key={`${food.name}-${i}`}
                                                type="button"
                                                className="border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-full border px-2.5 py-1 text-xs transition-colors"
                                                onClick={() => {
                                                    setData('name', food.name);
                                                    setData(
                                                        'calories_kcal',
                                                        food.calories_kcal,
                                                    );
                                                    setData(
                                                        'protein_g',
                                                        food.protein_g,
                                                    );
                                                    setData(
                                                        'carbs_g',
                                                        food.carbs_g,
                                                    );
                                                    setData(
                                                        'fat_g',
                                                        food.fat_g,
                                                    );
                                                    setData(
                                                        'serving_description',
                                                        food.serving_description ??
                                                            '',
                                                    );
                                                }}
                                            >
                                                {food.name}
                                                <span className="text-muted-foreground ml-1">
                                                    {round(food.calories_kcal)}{' '}
                                                    {t('common.kcal')}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="food-name">
                                    {t('food.name')}
                                </Label>
                                <Input
                                    id="food-name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder={t('food.namePlaceholder')}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="food-serving">
                                    {t('food.serving')}
                                </Label>
                                <Input
                                    id="food-serving"
                                    value={data.serving_description}
                                    onChange={(e) =>
                                        setData(
                                            'serving_description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder={t('food.servingPlaceholder')}
                                />
                                <InputError
                                    message={errors.serving_description}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="food-cal">
                                        {t('food.calories')}
                                    </Label>
                                    <Input
                                        id="food-cal"
                                        type="number"
                                        inputMode="decimal"
                                        value={data.calories_kcal}
                                        onChange={(e) =>
                                            setData(
                                                'calories_kcal',
                                                e.target.value,
                                            )
                                        }
                                        min="0"
                                        required
                                    />
                                    <InputError
                                        message={errors.calories_kcal}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="food-protein">
                                        {t('food.protein')}
                                    </Label>
                                    <Input
                                        id="food-protein"
                                        type="number"
                                        inputMode="decimal"
                                        value={data.protein_g}
                                        onChange={(e) =>
                                            setData('protein_g', e.target.value)
                                        }
                                        min="0"
                                        required
                                    />
                                    <InputError message={errors.protein_g} />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="food-carbs">
                                        {t('food.carbs')}
                                    </Label>
                                    <Input
                                        id="food-carbs"
                                        type="number"
                                        inputMode="decimal"
                                        value={data.carbs_g}
                                        onChange={(e) =>
                                            setData('carbs_g', e.target.value)
                                        }
                                        min="0"
                                        required
                                    />
                                    <InputError message={errors.carbs_g} />
                                </div>
                                <div>
                                    <Label htmlFor="food-fat">
                                        {t('food.fat')}
                                    </Label>
                                    <Input
                                        id="food-fat"
                                        type="number"
                                        inputMode="decimal"
                                        value={data.fat_g}
                                        onChange={(e) =>
                                            setData('fat_g', e.target.value)
                                        }
                                        min="0"
                                        required
                                    />
                                    <InputError message={errors.fat_g} />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing
                                    ? t('common.saving')
                                    : t('common.save')}
                            </Button>
                        </form>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

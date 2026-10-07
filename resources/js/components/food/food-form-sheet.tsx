import { router, useForm } from '@inertiajs/react';
import {
    Camera,
    ImagePlus,
    Keyboard,
    Loader2,
    Mic,
    PenLine,
    ScanLine,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ChipSelect } from '@/components/goal/chip-select';
import { BarcodeScanner } from '@/components/food/barcode-scanner';
import { useSpeechRecognition } from '@/hooks/use-speech-recognition';
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
import { barcode as productsBarcode } from '@/routes/products';
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

type Mode = 'photo' | 'text' | 'scan' | 'manual';

type BarcodeProduct = {
    name: string;
    brand?: string;
    barcode: string;
    calories_kcal_per_100g: number;
    protein_g_per_100g: number;
    carbs_g_per_100g: number;
    fat_g_per_100g: number;
    serving_description?: string;
};

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

const round1 = (value: number): number => Math.round(value * 10) / 10;

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

type ReviewTableProps = {
    detected: DetectedItem[];
    allSelected: boolean;
    totals: { calories: number; protein: number; carbs: number; fat: number };
    selectedCount: number;
    submitting: boolean;
    onToggleAll: () => void;
    onToggleItem: (index: number) => void;
    onSubmit: () => void;
};

function ReviewTable({
    detected,
    allSelected,
    totals,
    selectedCount,
    submitting,
    onToggleAll,
    onToggleItem,
    onSubmit,
}: ReviewTableProps) {
    const { t } = useI18n();

    return (
        <>
            <div className="overflow-hidden rounded-xl border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr className="text-muted-foreground text-left text-[11px] uppercase">
                            <th className="w-10 px-2 py-2">
                                <input
                                    type="checkbox"
                                    checked={allSelected}
                                    onChange={onToggleAll}
                                    aria-label={t('food.selectAll')}
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
                                    !item.selected && 'opacity-50',
                                )}
                            >
                                <td className="px-2 py-2 align-top">
                                    <input
                                        type="checkbox"
                                        checked={item.selected}
                                        onChange={() => onToggleItem(i)}
                                        className="accent-primary mt-0.5 size-4"
                                    />
                                </td>
                                <td className="px-2 py-2">
                                    <p className="text-sm font-medium">
                                        {item.name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {item.serving_description}
                                        <span className="sm:hidden">
                                            {' '}
                                            · P{round(item.protein_g)} C
                                            {round(item.carbs_g)} F
                                            {round(item.fat_g)}
                                        </span>
                                    </p>
                                </td>
                                <td className="px-2 py-2 text-right text-sm tabular-nums">
                                    {round(item.calories)}
                                </td>
                                <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                    {round(item.protein_g)}
                                </td>
                                <td className="hidden px-2 py-2 text-right text-xs tabular-nums sm:table-cell">
                                    {round(item.carbs_g)}
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
                                {t('food.selectedTotal')}
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
                disabled={selectedCount === 0 || submitting}
                onClick={onSubmit}
            >
                {submitting
                    ? t('common.saving')
                    : t('food.addItems', { count: selectedCount })}
            </Button>
        </>
    );
}

export function FoodFormSheet({
    open,
    onOpenChange,
    mealType,
    entry,
    date,
    frequentFoods = [],
}: Props) {
    const { t, locale } = useI18n();
    const isEditing = !!entry;
    const fileRef = useRef<HTMLInputElement>(null);

    const {
        supported: speechSupported,
        listening,
        start: startListening,
        stop: stopListening,
    } = useSpeechRecognition(locale === 'pt' ? 'pt-PT' : 'en-US', (text) => {
        setDescription((prev) => (prev ? `${prev} ${text}` : text));
    });

    const [mode, setMode] = useState<Mode>(isEditing ? 'manual' : 'photo');
    const [analyzing, setAnalyzing] = useState(false);
    const [aiSuggestion, setAiSuggestion] = useState<string | null>(null);
    const [detected, setDetected] = useState<DetectedItem[]>([]);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [photoId, setPhotoId] = useState<number | null>(
        entry?.photo_id ?? null,
    );
    const [submitting, setSubmitting] = useState(false);

    const [description, setDescription] = useState('');
    const [barcode, setBarcode] = useState('');
    const [barcodeSearching, setBarcodeSearching] = useState(false);
    const [product, setProduct] = useState<BarcodeProduct | null>(null);
    const [quantity, setQuantity] = useState('');
    const [scanning, setScanning] = useState(false);
    const [cameraError, setCameraError] = useState<string | null>(null);

    const mealOptions: { value: MealType; label: string }[] = [
        { value: 'breakfast', label: t('dashboard.breakfast') },
        { value: 'lunch', label: t('dashboard.lunch') },
        { value: 'dinner', label: t('dashboard.dinner') },
        { value: 'snack', label: t('dashboard.snack') },
    ];

    const modeOptions: { value: Mode; label: string; icon: typeof Camera }[] = [
        { value: 'photo', label: t('food.modePhoto'), icon: Camera },
        { value: 'text', label: t('food.modeText'), icon: PenLine },
        { value: 'scan', label: t('food.modeScan'), icon: ScanLine },
        { value: 'manual', label: t('food.modeManual'), icon: Keyboard },
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

    const close = () => onOpenChange(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = {
            onSuccess: () => {
                close();
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

    const switchMode = (next: Mode) => {
        setMode(next);
        setAiSuggestion(null);
        setDetected([]);
        setCameraError(null);
        setScanning(false);
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

    const handleTextAnalysis = async () => {
        const value = description.trim();

        if (value === '') {
            return;
        }

        setAnalyzing(true);
        setAiSuggestion(null);
        setDetected([]);

        try {
            const response = await fetch('/api/food/analyze-text', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                    ),
                },
                body: JSON.stringify({ description: value }),
            });

            if (!response.ok) {
                setAiSuggestion(t('food.aiFailed'));

                return;
            }

            const result = await response.json();

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

    const lookupBarcode = async (code: string) => {
        setBarcode(code);
        setBarcodeSearching(true);
        setProduct(null);
        setCameraError(null);

        try {
            const response = await fetch(productsBarcode(code).url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                setCameraError(t('food.barcodeNotFound'));

                return;
            }

            setProduct(await response.json());
        } catch {
            setCameraError(t('food.barcodeNotFound'));
        } finally {
            setBarcodeSearching(false);
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
                photo_id: mode === 'text' ? null : photoId,
                source: mode === 'text' ? 'ai_text' : undefined,
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
                onSuccess: () => close(),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const submitBarcode = () => {
        if (!product) {
            return;
        }

        const qty = Number(quantity);
        const factor = qty > 0 ? qty / 100 : 1;

        router.post(
            foodStore().url,
            {
                date,
                meal_type: data.meal_type,
                name: product.name,
                serving_description:
                    qty > 0
                        ? `${qty} ${t('food.unitGram')}`
                        : (product.serving_description ?? ''),
                calories_kcal: round1(
                    Number(product.calories_kcal_per_100g) * factor,
                ),
                protein_g: round1(Number(product.protein_g_per_100g) * factor),
                carbs_g: round1(Number(product.carbs_g_per_100g) * factor),
                fat_g: round1(Number(product.fat_g_per_100g) * factor),
                source: 'barcode',
                barcode: product.barcode,
            },
            {
                preserveScroll: true,
                onSuccess: () => close(),
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
        setDescription('');
        setBarcode('');
        setProduct(null);
        setQuantity('');
        setScanning(false);
        setCameraError(null);

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

                    {!isEditing && (
                        <div className="bg-muted/50 grid grid-cols-4 gap-1 rounded-xl p-1">
                            {modeOptions.map((option) => {
                                const Icon = option.icon;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => switchMode(option.value)}
                                        className={cn(
                                            'flex flex-col items-center justify-center gap-1 rounded-lg px-1 py-2 text-xs font-medium transition-colors',
                                            mode === option.value
                                                ? 'bg-background text-foreground shadow-xs'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        <Icon className="size-4" />
                                        {option.label}
                                    </button>
                                );
                            })}
                        </div>
                    )}

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

                    {mode === 'photo' && !isEditing && (
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
                                <ReviewTable
                                    detected={detected}
                                    allSelected={allSelected}
                                    totals={totals}
                                    selectedCount={selectedItems.length}
                                    submitting={submitting}
                                    onToggleAll={toggleAll}
                                    onToggleItem={toggleItem}
                                    onSubmit={submitBulk}
                                />
                            )}
                        </div>
                    )}

                    {mode === 'text' && !isEditing && (
                        <div className="space-y-3">
                            <div>
                                <Label htmlFor="food-description">
                                    {t('food.description')}
                                </Label>
                                <div className="relative">
                                    <textarea
                                        id="food-description"
                                        value={description}
                                        onChange={(e) =>
                                            setDescription(e.target.value)
                                        }
                                        placeholder={t(
                                            'food.descriptionPlaceholder',
                                        )}
                                        rows={3}
                                        className={cn(
                                            'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                                            speechSupported && 'pr-10',
                                        )}
                                    />
                                    {speechSupported && (
                                        <button
                                            type="button"
                                            onClick={
                                                listening
                                                    ? stopListening
                                                    : startListening
                                            }
                                            aria-label={t('food.speak')}
                                            className={cn(
                                                'text-muted-foreground hover:text-foreground absolute right-2.5 bottom-2 rounded-md p-1 transition-colors',
                                                listening &&
                                                    'text-primary hover:text-primary animate-pulse',
                                            )}
                                        >
                                            {listening ? (
                                                <Loader2 className="size-4 animate-spin" />
                                            ) : (
                                                <Mic className="size-4" />
                                            )}
                                        </button>
                                    )}
                                </div>
                                <p className="text-muted-foreground mt-1.5 text-xs">
                                    {listening
                                        ? t('food.listening')
                                        : t('food.describeHint')}
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                disabled={
                                    analyzing || description.trim() === ''
                                }
                                onClick={handleTextAnalysis}
                            >
                                {analyzing ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <PenLine className="size-4" />
                                )}
                                {analyzing
                                    ? t('food.analyzingText')
                                    : t('food.analyzeText')}
                            </Button>

                            {aiSuggestion && (
                                <p className="text-muted-foreground text-xs">
                                    {aiSuggestion}
                                </p>
                            )}

                            {detected.length > 0 && (
                                <ReviewTable
                                    detected={detected}
                                    allSelected={allSelected}
                                    totals={totals}
                                    selectedCount={selectedItems.length}
                                    submitting={submitting}
                                    onToggleAll={toggleAll}
                                    onToggleItem={toggleItem}
                                    onSubmit={submitBulk}
                                />
                            )}
                        </div>
                    )}

                    {mode === 'scan' && !isEditing && (
                        <div className="space-y-3">
                            {scanning ? (
                                <>
                                    <BarcodeScanner
                                        onDetected={(code) => {
                                            setScanning(false);
                                            void lookupBarcode(code);
                                        }}
                                        onError={() => {
                                            setScanning(false);
                                            setCameraError(
                                                t('food.cameraError'),
                                            );
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => setScanning(false)}
                                    >
                                        {t('food.stopScan')}
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setCameraError(null);
                                            setScanning(true);
                                        }}
                                        className="border-border bg-muted/30 hover:bg-muted/60 flex w-full flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-6 py-8 transition-colors"
                                    >
                                        <ScanLine className="text-muted-foreground size-8" />
                                        <span className="text-sm font-medium">
                                            {t('food.startScan')}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {t('food.scanHint')}
                                        </span>
                                    </button>

                                    <div className="flex gap-2">
                                        <Input
                                            value={barcode}
                                            onChange={(e) =>
                                                setBarcode(e.target.value)
                                            }
                                            placeholder={t(
                                                'food.barcodePlaceholder',
                                            )}
                                            inputMode="numeric"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={
                                                barcodeSearching ||
                                                barcode.trim() === ''
                                            }
                                            onClick={() =>
                                                void lookupBarcode(
                                                    barcode.trim(),
                                                )
                                            }
                                        >
                                            {barcodeSearching
                                                ? t('food.barcodeSearching')
                                                : t('food.barcodeSearch')}
                                        </Button>
                                    </div>
                                </>
                            )}

                            {cameraError && (
                                <p className="text-muted-foreground text-xs">
                                    {cameraError}
                                </p>
                            )}

                            {product && (
                                <div className="space-y-3 rounded-xl border p-3">
                                    <div>
                                        <p className="text-sm font-medium">
                                            {product.name}
                                        </p>
                                        {product.brand && (
                                            <p className="text-muted-foreground text-xs">
                                                {product.brand}
                                            </p>
                                        )}
                                    </div>

                                    <p className="text-muted-foreground text-xs">
                                        {t('food.per100g')}:{' '}
                                        {round(product.calories_kcal_per_100g)}{' '}
                                        {t('common.kcal')} · P
                                        {round(product.protein_g_per_100g)} C
                                        {round(product.carbs_g_per_100g)} F
                                        {round(product.fat_g_per_100g)}
                                    </p>

                                    <div>
                                        <Label htmlFor="food-quantity">
                                            {t('food.quantityGrams')}
                                        </Label>
                                        <Input
                                            id="food-quantity"
                                            type="number"
                                            inputMode="decimal"
                                            value={quantity}
                                            onChange={(e) =>
                                                setQuantity(e.target.value)
                                            }
                                            min="0"
                                            placeholder="100"
                                        />
                                    </div>

                                    <Button
                                        type="button"
                                        className="w-full"
                                        onClick={submitBarcode}
                                    >
                                        {t('food.addProduct')}
                                    </Button>
                                </div>
                            )}
                        </div>
                    )}

                    {(mode === 'manual' || isEditing) && (
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {isEditing && entry?.photo?.url && (
                                <img
                                    src={entry.photo.url}
                                    alt=""
                                    className="h-32 w-full rounded-xl object-cover"
                                />
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

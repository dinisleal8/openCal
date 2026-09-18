import { useForm } from '@inertiajs/react';
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
import {
    store as exerciseStore,
    update as exerciseUpdate,
} from '@/routes/exercise';
import type { ExerciseLog } from '@/types/models';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    date: string;
    entry?: ExerciseLog;
};

export function ExerciseFormSheet({ open, onOpenChange, date, entry }: Props) {
    const { t } = useI18n();
    const isEditing = !!entry;

    const form = useForm({
        date,
        name: entry?.name ?? '',
        duration_min: entry?.duration_min?.toString() ?? '',
        calories_kcal: entry?.calories_kcal ?? '',
    });

    const { data, setData, post, put, processing, errors, reset } = form;

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
            put(exerciseUpdate(entry.id).url, opts);
        } else {
            post(exerciseStore().url, opts);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('exercise.title')}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="ex-name">{t('exercise.name')}</Label>
                        <Input
                            id="ex-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('exercise.namePlaceholder')}
                            required
                        />
                        {errors.name && (
                            <p className="text-destructive text-xs">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="ex-duration">
                                {t('exercise.duration')}
                            </Label>
                            <Input
                                id="ex-duration"
                                type="number"
                                inputMode="numeric"
                                value={data.duration_min}
                                onChange={(e) =>
                                    setData('duration_min', e.target.value)
                                }
                                min="1"
                                max="1440"
                            />
                            <InputError message={errors.duration_min} />
                        </div>
                        <div>
                            <Label htmlFor="ex-cal">
                                {t('exercise.calories')}
                            </Label>
                            <Input
                                id="ex-cal"
                                type="number"
                                inputMode="decimal"
                                value={data.calories_kcal}
                                onChange={(e) =>
                                    setData('calories_kcal', e.target.value)
                                }
                                min="0"
                                required
                            />
                            {errors.calories_kcal && (
                                <p className="text-destructive text-xs">
                                    {errors.calories_kcal}
                                </p>
                            )}
                        </div>
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? t('common.saving') : t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

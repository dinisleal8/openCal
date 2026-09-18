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
import { store as weightStore } from '@/routes/weight';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    date: string;
    currentWeightKg: number | null;
};

export function WeightFormSheet({
    open,
    onOpenChange,
    date,
    currentWeightKg,
}: Props) {
    const { t } = useI18n();

    const form = useForm({
        date,
        weight_kg: currentWeightKg ? String(currentWeightKg) : '',
    });

    const { data, setData, post, processing, errors } = form;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(weightStore().url, {
            onSuccess: () => onOpenChange(false),
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('weight.title')}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="weight-kg">{t('weight.title')}</Label>
                        <Input
                            id="weight-kg"
                            type="number"
                            inputMode="decimal"
                            step="0.1"
                            value={data.weight_kg}
                            onChange={(e) =>
                                setData('weight_kg', e.target.value)
                            }
                            min="30"
                            max="300"
                            required
                        />
                        <InputError message={errors.weight_kg} />
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

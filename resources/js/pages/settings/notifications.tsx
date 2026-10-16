import { Head, useForm } from '@inertiajs/react';
import { BellRing, Check, Loader2, Smartphone } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { usePushNotifications } from '@/hooks/use-push-notifications';
import { useI18n } from '@/lib/i18n';
import {
    edit as editNotifications,
    update as updateNotifications,
} from '@/routes/notifications';

type ReminderSettings = {
    meal_reminders_enabled: boolean;
    meal_reminder_time: string;
    water_reminders_enabled: boolean;
    water_reminder_time: string;
    weigh_in_reminders_enabled: boolean;
    weigh_in_reminder_time: string;
};

type Props = {
    settings: ReminderSettings;
};

export default function NotificationsSettings({ settings }: Props) {
    const { t } = useI18n();
    const push = usePushNotifications();

    const form = useForm<ReminderSettings>({
        meal_reminders_enabled: settings.meal_reminders_enabled,
        meal_reminder_time: settings.meal_reminder_time || '12:00',
        water_reminders_enabled: settings.water_reminders_enabled,
        water_reminder_time: settings.water_reminder_time || '15:00',
        weigh_in_reminders_enabled: settings.weigh_in_reminders_enabled,
        weigh_in_reminder_time: settings.weigh_in_reminder_time || '08:00',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.put(updateNotifications().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('notifications.title')} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('notifications.title')}
                    description={t('notifications.description')}
                />

                <div className="bg-card rounded-2xl border p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex items-start gap-3">
                            <span className="bg-primary/10 text-primary flex size-9 items-center justify-center rounded-xl">
                                <BellRing className="size-4.5" />
                            </span>
                            <div>
                                <h3 className="font-medium">
                                    {t('notifications.push.title')}
                                </h3>
                                <p className="text-muted-foreground mt-0.5 text-sm">
                                    {t('notifications.push.description')}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4">
                        {!push.supported ? (
                            <p className="text-muted-foreground flex items-center gap-2 text-sm">
                                <Smartphone className="size-4" />
                                {t('notifications.push.unsupported')}
                            </p>
                        ) : push.subscribed ? (
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                    <Check className="size-3.5" />
                                    {t('notifications.push.enabled')}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={push.unsubscribe}
                                    disabled={push.busy}
                                >
                                    {t('notifications.push.disable')}
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                <Button
                                    size="sm"
                                    onClick={push.subscribe}
                                    disabled={push.busy}
                                >
                                    {push.busy && (
                                        <Loader2 className="size-4 animate-spin" />
                                    )}
                                    {t('notifications.push.enable')}
                                </Button>
                                <p className="text-muted-foreground text-xs">
                                    {t('notifications.push.iosHint')}
                                </p>
                            </div>
                        )}

                        {push.permission === 'denied' && (
                            <p className="text-destructive mt-2 text-xs">
                                {t('notifications.push.denied')}
                            </p>
                        )}
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <h3 className="text-sm font-semibold">
                        {t('notifications.reminders.title')}
                    </h3>

                    <ReminderRow
                        label={t('notifications.reminders.meal')}
                        enabled={form.data.meal_reminders_enabled}
                        time={form.data.meal_reminder_time}
                        onEnabledChange={(value) =>
                            form.setData('meal_reminders_enabled', value)
                        }
                        onTimeChange={(value) =>
                            form.setData('meal_reminder_time', value)
                        }
                    />
                    <ReminderRow
                        label={t('notifications.reminders.water')}
                        enabled={form.data.water_reminders_enabled}
                        time={form.data.water_reminder_time}
                        onEnabledChange={(value) =>
                            form.setData('water_reminders_enabled', value)
                        }
                        onTimeChange={(value) =>
                            form.setData('water_reminder_time', value)
                        }
                    />
                    <ReminderRow
                        label={t('notifications.reminders.weighIn')}
                        enabled={form.data.weigh_in_reminders_enabled}
                        time={form.data.weigh_in_reminder_time}
                        onEnabledChange={(value) =>
                            form.setData('weigh_in_reminders_enabled', value)
                        }
                        onTimeChange={(value) =>
                            form.setData('weigh_in_reminder_time', value)
                        }
                    />

                    <p className="text-muted-foreground text-xs">
                        {t('notifications.reminders.timezoneNote')}
                    </p>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && (
                                <Loader2 className="size-4 animate-spin" />
                            )}
                            {t('common.save')}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

type ReminderRowProps = {
    label: string;
    enabled: boolean;
    time: string;
    onEnabledChange: (value: boolean) => void;
    onTimeChange: (value: string) => void;
};

function ReminderRow({
    label,
    enabled,
    time,
    onEnabledChange,
    onTimeChange,
}: ReminderRowProps) {
    return (
        <div className="bg-card flex items-center justify-between gap-4 rounded-2xl border p-4">
            <label className="flex flex-1 items-center gap-3">
                <Checkbox
                    checked={enabled}
                    onCheckedChange={(value) => onEnabledChange(value === true)}
                />
                <span className="text-sm font-medium">{label}</span>
            </label>
            <Input
                type="time"
                value={time}
                onChange={(event) => onTimeChange(event.target.value)}
                disabled={!enabled}
                className="w-32"
            />
        </div>
    );
}

NotificationsSettings.layout = {
    breadcrumbs: [
        {
            title: 'Notification settings',
            href: editNotifications(),
        },
    ],
};

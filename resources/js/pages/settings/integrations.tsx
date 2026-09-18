import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/lib/i18n';
import {
    redirect as ghRedirect,
    sync as ghSync,
    disconnect as ghDisconnect,
} from '@/routes/google-health';

type Props = {
    googleHealthConnected: boolean;
    googleHealthLastSync: string | null;
    googleHealthError: string | null;
};

export default function IntegrationsSettings({
    googleHealthConnected,
    googleHealthLastSync,
    googleHealthError,
}: Props) {
    const { t } = useI18n();
    const [syncing, setSyncing] = useState(false);
    const [showSyncDate, setShowSyncDate] = useState(false);

    const syncForm = useForm({ date: new Date().toISOString().split('T')[0] });
    const disconnectForm = useForm({});

    const handleConnect = () => {
        window.location.href = ghRedirect().url;
    };

    const handleSync = () => {
        setSyncing(true);
        syncForm.post(ghSync().url, {
            onFinish: () => setSyncing(false),
            preserveScroll: true,
        });
    };

    const handleSyncDate = (e: React.FormEvent) => {
        e.preventDefault();
        setSyncing(true);
        syncForm.post(ghSync().url, {
            onFinish: () => {
                setSyncing(false);
                setShowSyncDate(false);
            },
            preserveScroll: true,
        });
    };

    const handleDisconnect = () => {
        if (!confirm('Disconnect Google Health?')) return;
        disconnectForm.delete(ghDisconnect().url);
    };

    return (
        <>
            <Head title={t('settings.integrations')} />
            <div className="space-y-6">
                <div>
                    <h2 className="text-lg font-medium">
                        {t('settings.integrations')}
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        {t('settings.googleHealthDesc')}
                    </p>
                </div>

                <div className="bg-card rounded-2xl border p-5">
                    <div className="flex items-start justify-between">
                        <div>
                            <h3 className="font-medium">
                                {t('settings.googleHealth')}
                            </h3>
                            <p className="text-muted-foreground mt-0.5 text-sm">
                                {t('settings.googleHealthDesc')}
                            </p>
                        </div>
                        <div
                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                googleHealthConnected
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-muted text-muted-foreground'
                            }`}
                        >
                            {googleHealthConnected
                                ? t('common.connected')
                                : t('common.notConnected')}
                        </div>
                    </div>

                    {googleHealthError && (
                        <div className="bg-destructive/10 text-destructive mt-3 rounded-lg p-3 text-sm">
                            {t('settings.syncError', {
                                error: googleHealthError,
                            })}
                        </div>
                    )}

                    {googleHealthConnected ? (
                        <div className="mt-4 space-y-3">
                            <p className="text-muted-foreground text-xs">
                                {googleHealthLastSync
                                    ? t('settings.lastSync', {
                                          when: googleHealthLastSync,
                                      })
                                    : t('settings.neverSynced')}
                            </p>

                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleSync}
                                    disabled={syncing}
                                >
                                    {syncing
                                        ? t('common.syncing')
                                        : t('common.sync')}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowSyncDate(true)}
                                >
                                    {t('common.sync')}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive hover:text-destructive"
                                    onClick={handleDisconnect}
                                >
                                    {t('common.disconnect')}
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="mt-4">
                            <Button size="sm" onClick={handleConnect}>
                                {t('common.sync')}
                            </Button>
                            <p className="text-muted-foreground mt-2 text-xs">
                                {t('settings.googleHealthMissing')}
                            </p>
                        </div>
                    )}
                </div>

                <Dialog open={showSyncDate} onOpenChange={setShowSyncDate}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>{t('common.sync')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSyncDate} className="space-y-4">
                            <div>
                                <Label htmlFor="sync-date">Date</Label>
                                <Input
                                    id="sync-date"
                                    type="date"
                                    value={syncForm.data.date}
                                    onChange={(e) =>
                                        syncForm.setData('date', e.target.value)
                                    }
                                    max={new Date().toISOString().split('T')[0]}
                                    required
                                />
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={syncing}
                            >
                                {syncing
                                    ? t('common.syncing')
                                    : t('common.sync')}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

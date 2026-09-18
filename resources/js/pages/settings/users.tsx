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
import { store as userStore, destroy as userDestroy } from '@/routes/users';
import type { User } from '@/types/auth';

type Props = {
    users: User[];
    isOwner: boolean;
};

export default function UsersSettings({ users, isOwner }: Props) {
    const { t } = useI18n();
    const [showCreate, setShowCreate] = useState(false);

    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const deleteForm = useForm({});

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(userStore().url, {
            onSuccess: () => {
                setShowCreate(false);
                createForm.reset();
            },
        });
    };

    const handleDelete = (userId: number) => {
        if (!confirm(t('settings.confirmDeleteUser'))) return;
        deleteForm.delete(userDestroy(userId).url);
    };

    return (
        <>
            <Head title={t('settings.users')} />
            <div className="space-y-6">
                <div>
                    <h2 className="text-lg font-medium">
                        {t('settings.users')}
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        {t('settings.usersDesc')}
                    </p>
                </div>

                {isOwner && (
                    <Button onClick={() => setShowCreate(true)} size="sm">
                        {t('settings.addUser')}
                    </Button>
                )}

                <div className="space-y-2">
                    {users.map((user) => (
                        <div
                            key={user.id}
                            className="bg-card flex items-center justify-between rounded-xl border p-3"
                        >
                            <div>
                                <span className="text-sm font-medium">
                                    {user.name}
                                </span>
                                <span className="text-muted-foreground ml-2 text-xs">
                                    {user.email}
                                </span>
                                {user.is_owner && (
                                    <span className="bg-primary/10 text-primary ml-2 rounded-full px-2 py-0.5 text-[10px] font-medium">
                                        {t('settings.userOwnerBadge')}
                                    </span>
                                )}
                            </div>
                            {isOwner && !user.is_owner && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive hover:text-destructive"
                                    onClick={() => handleDelete(user.id)}
                                >
                                    {t('common.delete')}
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <Dialog open={showCreate} onOpenChange={setShowCreate}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('settings.newUser')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="space-y-4">
                        <div>
                            <Label htmlFor="new-name">
                                {t('settings.userName')}
                            </Label>
                            <Input
                                id="new-name"
                                value={createForm.data.name}
                                onChange={(e) =>
                                    createForm.setData('name', e.target.value)
                                }
                                required
                            />
                            {createForm.errors.name && (
                                <p className="text-destructive text-xs">
                                    {createForm.errors.name}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="new-email">
                                {t('settings.userEmail')}
                            </Label>
                            <Input
                                id="new-email"
                                type="email"
                                value={createForm.data.email}
                                onChange={(e) =>
                                    createForm.setData('email', e.target.value)
                                }
                                required
                            />
                            {createForm.errors.email && (
                                <p className="text-destructive text-xs">
                                    {createForm.errors.email}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="new-password">
                                {t('settings.userPassword')}
                            </Label>
                            <Input
                                id="new-password"
                                type="password"
                                value={createForm.data.password}
                                onChange={(e) =>
                                    createForm.setData(
                                        'password',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            {createForm.errors.password && (
                                <p className="text-destructive text-xs">
                                    {createForm.errors.password}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="new-password-confirm">
                                {t('settings.userPassword')}
                            </Label>
                            <Input
                                id="new-password-confirm"
                                type="password"
                                value={createForm.data.password_confirmation}
                                onChange={(e) =>
                                    createForm.setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                required
                            />
                        </div>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={createForm.processing}
                        >
                            {createForm.processing
                                ? t('common.saving')
                                : t('common.save')}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

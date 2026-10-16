import { router, usePage } from '@inertiajs/react';
import { Bell, Check, Inbox } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import {
    edit as editNotifications,
    read as readNotification,
    readAll as readAllNotifications,
} from '@/routes/notifications';
import type { NotificationItem } from '@/types';

export function NotificationBell() {
    const { notifications } = usePage().props;
    const { t } = useI18n();

    if (!notifications) {
        return null;
    }

    const { items, unread_count: unreadCount } = notifications;

    const markAllAsRead = () => {
        router.post(
            readAllNotifications().url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['notifications'],
            },
        );
    };

    const openNotification = (item: NotificationItem) => {
        const visit = () => router.visit(item.url, { preserveScroll: true });

        if (item.read_at) {
            visit();

            return;
        }

        router.post(
            readNotification(item.id).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['notifications'],
                onSuccess: visit,
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={t('notifications.title')}
                >
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span className="bg-primary text-primary-foreground absolute top-1.5 right-1.5 flex min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-4 font-semibold">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between border-b px-3 py-2">
                    <span className="text-sm font-semibold">
                        {t('notifications.title')}
                    </span>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllAsRead}
                            className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs font-medium"
                        >
                            <Check className="size-3.5" />
                            {t('notifications.markAllRead')}
                        </button>
                    )}
                </div>

                <div className="max-h-96 overflow-y-auto">
                    {items.length === 0 ? (
                        <div className="text-muted-foreground flex flex-col items-center gap-2 px-4 py-10 text-center text-sm">
                            <Inbox className="size-6" />
                            {t('notifications.empty')}
                        </div>
                    ) : (
                        <ul className="divide-y">
                            {items.map((item) => (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        onClick={() => openNotification(item)}
                                        className={cn(
                                            'hover:bg-muted/60 flex w-full items-start gap-3 px-3 py-3 text-left transition-colors',
                                            !item.read_at && 'bg-muted/30',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'mt-1.5 size-2 shrink-0 rounded-full',
                                                item.read_at
                                                    ? 'bg-transparent'
                                                    : 'bg-primary',
                                            )}
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-medium">
                                                {item.title}
                                            </span>
                                            <span className="text-muted-foreground block text-xs">
                                                {item.body}
                                            </span>
                                            {item.created_at_diff && (
                                                <span className="text-muted-foreground/70 mt-1 block text-[11px]">
                                                    {item.created_at_diff}
                                                </span>
                                            )}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="border-t">
                    <button
                        type="button"
                        onClick={() => router.visit(editNotifications().url)}
                        className="text-muted-foreground hover:text-foreground w-full px-3 py-2 text-center text-xs font-medium"
                    >
                        {t('notifications.manage')}
                    </button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

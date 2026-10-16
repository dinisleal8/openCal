import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import {
    store as storePushSubscription,
    destroy as destroyPushSubscription,
} from '@/routes/pushSubscriptions';

type PushPermission = NotificationPermission | 'unsupported';

type UsePushNotifications = {
    supported: boolean;
    permission: PushPermission;
    subscribed: boolean;
    busy: boolean;
    subscribe: () => Promise<void>;
    unsubscribe: () => Promise<void>;
};

function isSupported(): boolean {
    return (
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window
    );
}

function urlBase64ToUint8Array(base64String: string): Uint8Array<ArrayBuffer> {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const output = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i++) {
        output[i] = rawData.charCodeAt(i);
    }

    return output;
}

export function usePushNotifications(): UsePushNotifications {
    const { push } = usePage().props;
    const publicKey = push.publicKey;

    const [supported, setSupported] = useState(false);
    const [permission, setPermission] = useState<PushPermission>('unsupported');
    const [subscribed, setSubscribed] = useState(false);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (!isSupported()) {
            return;
        }

        setSupported(true);
        setPermission(Notification.permission);

        let cancelled = false;

        navigator.serviceWorker
            .getRegistration()
            .then(async (registration) => {
                const subscription =
                    await registration?.pushManager.getSubscription();

                if (!cancelled) {
                    setSubscribed(Boolean(subscription));
                }
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, []);

    const subscribe = useCallback(async () => {
        if (!isSupported() || !publicKey) {
            return;
        }

        setBusy(true);

        try {
            const result = await Notification.requestPermission();
            setPermission(result);

            if (result !== 'granted') {
                return;
            }

            const registration =
                await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey),
            });

            const json = subscription.toJSON();

            router.post(
                storePushSubscription().url,
                {
                    endpoint: json.endpoint ?? '',
                    keys: {
                        pwh: json.keys?.p256dh ?? '',
                        auth: json.keys?.auth ?? '',
                    },
                    content_encoding: 'aes128gcm',
                },
                { preserveScroll: true, preserveState: true },
            );

            setSubscribed(true);
        } catch {
            setSubscribed(false);
        } finally {
            setBusy(false);
        }
    }, [publicKey]);

    const unsubscribe = useCallback(async () => {
        if (!isSupported()) {
            return;
        }

        setBusy(true);

        try {
            const registration =
                await navigator.serviceWorker.getRegistration();
            const subscription =
                await registration?.pushManager.getSubscription();

            if (subscription) {
                router.delete(destroyPushSubscription().url, {
                    data: { endpoint: subscription.endpoint },
                    preserveScroll: true,
                    preserveState: true,
                });

                await subscription.unsubscribe();
            }

            setSubscribed(false);
        } finally {
            setBusy(false);
        }
    }, []);

    return {
        supported,
        permission,
        subscribed,
        busy,
        subscribe,
        unsubscribe,
    };
}

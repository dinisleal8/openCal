/* openCal service worker — handles Web Push reminders. */

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch {
            payload = { title: 'openCal', body: event.data.text() };
        }
    }

    const data = payload.data || {};
    const title = payload.title || 'openCal';

    event.waitUntil(
        self.registration.showNotification(title, {
            body: payload.body || '',
            icon: payload.icon || '/icons/notification-icon.png',
            badge: payload.badge || '/icons/notification-badge.png',
            tag: payload.tag,
            data: { url: data.url || payload.url || '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                for (const client of windowClients) {
                    if ('focus' in client) {
                        client.navigate(url);

                        return client.focus();
                    }
                }

                if (self.clients.openWindow) {
                    return self.clients.openWindow(url);
                }

                return undefined;
            }),
    );
});

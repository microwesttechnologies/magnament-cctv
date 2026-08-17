const SHELL_CACHE = 'tecnico-shell-v3';
const SHELL_URLS = ['/tecnico/offline.html', '/manifest-tecnico.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL_CACHE);
        await Promise.all(SHELL_URLS.map((url) => cache.add(url).catch(() => undefined)));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== SHELL_CACHE).map((key) => caches.delete(key)),
        )).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname === '/tecnico/offline.html' || url.pathname === '/manifest-tecnico.webmanifest') {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/tecnico/offline.html')),
        );
    }
});

self.addEventListener('push', (event) => {
    let payload = { title: 'Management CCTV', body: 'Tienes una actualización de orden.', url: '/tecnico?source=pwa' };
    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (error) {
            payload.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            data: { url: payload.url || '/tecnico?source=pwa' },
            icon: '/images/pwa/icon-192.png',
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/tecnico?source=pwa';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const existing = clients.find((client) => 'focus' in client);
            if (existing) {
                existing.navigate(target);
                return existing.focus();
            }
            return self.clients.openWindow(target);
        }),
    );
});

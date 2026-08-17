window.deferredPwaPrompt = window.deferredPwaPrompt || null;

function isStandalonePwa() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function markStandaloneCookie() {
    if (!isStandalonePwa() && !new URLSearchParams(window.location.search).has('source')) {
        return;
    }
    document.cookie = 'pwa_standalone=1; path=/; max-age=31536000; samesite=lax';
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        markStandaloneCookie();
        navigator.serviceWorker.register('/tecnico/sw.js', { scope: '/tecnico/' }).catch(() => {});
        if (document.querySelector('meta[name="csrf-token"]')) {
            subscribePush();
        }
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    if (localStorage.getItem('pwaInstallDismissed') === '1') {
        return;
    }
    window.deferredPwaPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa:installable', { detail: event }));
});

async function subscribePush() {
    if (!('Notification' in window) || !('PushManager' in window) || !navigator.serviceWorker) {
        return;
    }
    if (Notification.permission !== 'granted') {
        return;
    }

    try {
        const vapid = await fetch('/tecnico/push/vapid', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!vapid.ok) {
            return;
        }
        const payload = await vapid.json();
        if (!payload.key) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(payload.key),
        });

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await fetch('/tecnico/push/subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            body: JSON.stringify(subscription.toJSON()),
        });
    } catch (error) {
        // Sin VAPID o sin HTTPS el push queda deshabilitado; la bandeja interna sigue activa.
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

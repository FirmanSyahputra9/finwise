const CACHE_NAME = 'finwise-shell-v5';
const APP_SHELL = ['/offline.html', '/offline.js', '/manifest.webmanifest', '/favicon.ico', '/icons/finwise.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key.startsWith('finwise-shell-') && key !== CACHE_NAME).map((key) => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => (await caches.match('/offline.html')) || Response.error()));
        return;
    }

    if (APP_SHELL.includes(url.pathname)) {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
    }
});

self.addEventListener('sync', (event) => {
    if (event.tag === 'finwise-transaction-sync') {
        event.waitUntil(syncPendingTransactions());
    }
});

self.addEventListener('message', (event) => {
    if (event.data?.type !== 'FINWISE_SHOW_NOTIFICATION' || Notification.permission !== 'granted') return;
    event.waitUntil(self.registration.showNotification(event.data.title, {
        body: event.data.body,
        icon: '/icons/finwise.svg',
        badge: '/icons/finwise.svg',
        data: { url: '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
        const app = clients.find((client) => new URL(client.url).origin === self.location.origin);
        return app ? app.focus() : self.clients.openWindow(event.notification.data?.url || '/');
    }));
});

async function readStore(storeName, requestName) {
    const db = await new Promise((resolve, reject) => {
        const request = indexedDB.open('finwise-offline', 2);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, 'readonly');
        const request = transaction.objectStore(storeName)[requestName]();
        request.onsuccess = () => resolve({ value: request.result, db });
        request.onerror = () => { db.close(); reject(request.error); };
    });
}

async function syncPendingTransactions() {
    try {
        const { value: meta, db: metaDb } = await readStore('meta', 'getAll');
        metaDb.close();
        const owner = meta.find((item) => item.id === 'finance');
        if (!owner) return;

        const { value: queue, db } = await readStore('queue', 'getAll');
        const pending = queue.filter((item) => item.owner_id === owner.userId).slice(0, 50);
        db.close();
        if (!pending.length) return;

        const response = await fetch('/transactions/sync', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': owner.csrfToken },
            body: JSON.stringify({ owner_id: owner.userId, transactions: pending }),
        });
        if (!response.ok) throw new Error('Sync not accepted');
        const result = await response.json();
        if (result.synced?.length) {
            const { db: writeDb } = await readStore('queue', 'getAll');
            const transaction = writeDb.transaction('queue', 'readwrite');
            result.synced.forEach((id) => transaction.objectStore('queue').delete(id));
            await new Promise((resolve, reject) => { transaction.oncomplete = resolve; transaction.onerror = () => reject(transaction.error); });
            writeDb.close();
        }
        const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        clients.forEach((client) => client.postMessage({ type: 'FINWISE_SYNC' }));
    } catch (_) {
        // The browser retries background sync when the network or session becomes available.
    }
}

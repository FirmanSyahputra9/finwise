(() => {
if (window.__finwiseOfflineInitialized) return;
window.__finwiseOfflineInitialized = true;

const DB_NAME = 'finwise-offline';
const DB_VERSION = 1;
const openDb = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('queue')) db.createObjectStore('queue', { keyPath: 'client_id' });
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});
const storeRequest = async (store, mode, action) => {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(store, mode);
        const result = action(transaction.objectStore(store));
        transaction.oncomplete = () => { db.close(); resolve(result?.result); };
        transaction.onerror = () => { db.close(); reject(transaction.error); };
    });
};
const getMeta = () => storeRequest('meta', 'readonly', (store) => store.get('finance'));
const getQueue = () => storeRequest('queue', 'readonly', (store) => store.getAll());
const saveQueueItem = (item) => storeRequest('queue', 'readwrite', (store) => store.put(item));
const deleteQueueItem = (id) => storeRequest('queue', 'readwrite', (store) => store.delete(id));
const setMeta = (data) => storeRequest('meta', 'readwrite', (store) => store.put({ ...data, id: 'finance' }));
const uuid = () => crypto.randomUUID();
const localToday = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
async function registerBackgroundSync() {
    if (!('serviceWorker' in navigator)) return;
    const registration = await navigator.serviceWorker.ready;
    registration.sync?.register('finwise-transaction-sync').catch(() => {});
}
let syncing = false;
let syncWarning = '';

async function refreshQueueStatus() {
    const meta = await getMeta();
    const queue = meta?.userId ? (await getQueue()).filter((item) => item.owner_id === meta.userId) : [];
    const status = document.getElementById('offline-queue');
    const appStatus = document.getElementById('offline-sync-status');
    const failedCount = queue.filter((item) => item.sync_error).length;
    const message = queue.length ? `${queue.length} transaksi menunggu sinkronisasi${failedCount ? ` (${failedCount} perlu diperiksa)` : ''}.` : 'Semua transaksi sudah tersinkron.';
    if (status) status.textContent = message;
    const pendingList = document.getElementById('offline-pending-list');
    if (pendingList) {
        pendingList.replaceChildren();
        for (const item of queue) {
            const row = document.createElement('div'); row.className = 'pending-item';
            const details = document.createElement('span');
            details.textContent = `${item.type} · Rp ${Number(item.amount).toLocaleString('id-ID')} · ${item.date}${item.sync_error ? ` — ${item.sync_error}` : ''}`;
            const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Hapus'; remove.setAttribute('aria-label', 'Hapus transaksi offline');
            remove.style.cssText = 'width:auto;margin:0;padding:6px 10px;background:#fff;color:#b42318';
            remove.addEventListener('click', async () => { await deleteQueueItem(item.client_id); await refreshQueueStatus(); });
            row.append(details, remove); pendingList.append(row);
        }
    }
    if (appStatus) {
        const visible = !navigator.onLine || queue.length > 0;
        appStatus.classList.toggle('hidden', !visible);
        if (!visible) return;
        appStatus.innerHTML = syncWarning || (navigator.onLine
            ? `Online. ${message} <a class="font-semibold text-blue underline" href="/offline.html">Kelola antrean</a>`
            : `Offline. ${message} <a class="font-semibold text-blue underline" href="/offline.html">Buka formulir offline</a>`);
    }
}

async function syncQueue() {
    if (!navigator.onLine || syncing) return;
    const meta = await getMeta();
    const queue = (await getQueue()).filter((item) => item.owner_id === meta?.userId);
    if (!meta || !queue.length) return refreshQueueStatus();
    syncing = true;
    try {
        const response = await fetch('/transactions/sync', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta.csrfToken },
            body: JSON.stringify({ owner_id: meta.userId, transactions: queue.slice(0, 50) }),
        });
        if (response.ok) {
            syncWarning = '';
            const result = await response.json();
            for (const id of result.synced ?? []) await deleteQueueItem(id);
            for (const failure of result.failed ?? []) {
                const item = queue.find((entry) => entry.client_id === failure.client_id);
                if (item) await saveQueueItem({ ...item, sync_error: failure.message });
            }
        } else if (response.status === 409) {
            syncWarning = 'Sinkronisasi dijeda karena akun aktif berbeda. Masuk dengan akun yang digunakan saat mencatat transaksi offline.';
        }
    } catch (_) {
        // Queue remains in IndexedDB and will retry when the connection returns.
    } finally {
        syncing = false;
        await refreshQueueStatus();
    }
}

function option(value, label) {
    const item = document.createElement('option'); item.value = value; item.textContent = label; return item;
}

async function setupOfflineForm() {
    const form = document.getElementById('offline-transaction-form');
    if (!form) return;
    const meta = await getMeta();
    const accountSelect = form.elements.account_id;
    const destination = form.elements.to_account_id;
    const categorySelect = form.elements.category_id;
    for (const account of meta?.accounts ?? []) {
        accountSelect.append(option(account.id, account.name));
        destination.append(option(account.id, account.name));
    }
    function setType(type) {
        form.dataset.type = type;
        form.querySelectorAll('[data-type]').forEach((button) => button.setAttribute('aria-pressed', button.dataset.type === type ? 'true' : 'false'));
        document.getElementById('category-wrap').hidden = type === 'transfer';
        document.getElementById('to-account-wrap').hidden = type !== 'transfer';
        categorySelect.replaceChildren(option('', 'Pilih kategori'));
        for (const category of (meta?.categories ?? []).filter((item) => item.type === type)) categorySelect.append(option(category.id, category.name));
        categorySelect.required = type !== 'transfer';
        destination.required = type === 'transfer';
    }
    form.querySelectorAll('[data-type]').forEach((button) => button.addEventListener('click', () => setType(button.dataset.type)));
    setType('expense');
    form.elements.date.value = localToday();
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const values = new FormData(form);
        const type = form.dataset.type;
        if (!meta?.userId) { alert('Buka aplikasi saat online terlebih dahulu agar data akun tersimpan aman di perangkat.'); return; }
        const item = {
            client_id: uuid(), type, amount: Number(values.get('amount')), date: values.get('date'),
            owner_id: meta.userId,
            account_id: Number(values.get('account_id')),
            to_account_id: type === 'transfer' ? Number(values.get('to_account_id')) : null,
            category_id: type === 'transfer' ? null : Number(values.get('category_id')),
            note: values.get('note') || null,
        };
        await saveQueueItem(item);
        registerBackgroundSync();
        form.reset(); setType('expense');
        form.elements.date.value = localToday();
        await refreshQueueStatus();
        if (navigator.onLine) await syncQueue();
    });
}

async function captureOnlineData() {
    const element = document.getElementById('offline-finance-data');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!element || !csrfToken) return;
    try { await setMeta({ ...JSON.parse(element.textContent), csrfToken }); }
    catch (_) { return; }
    registerBackgroundSync();
    syncQueue();
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('#transaction-form');
    if (!form || form.dataset.pwaPassThrough === 'true') { if (form) delete form.dataset.pwaPassThrough; return; }
    event.preventDefault(); event.stopImmediatePropagation();
    let reachable = navigator.onLine;
    if (reachable) {
        try { const response = await fetch('/up', { cache: 'no-store', credentials: 'same-origin' }); reachable = response.ok; }
        catch (_) { reachable = false; }
    }
    if (reachable) {
        form.dataset.pwaPassThrough = 'true'; form.requestSubmit(event.submitter); return;
    }
    const data = Object.fromEntries([...form.querySelectorAll('[data-offline-field]')].map((input) => [input.dataset.offlineField, input.value]));
    const meta = await getMeta();
    if (!meta?.userId) { alert('Buka aplikasi saat online terlebih dahulu agar data akun tersimpan aman di perangkat.'); return; }
    data.client_id = uuid(); data.owner_id = meta.userId; data.amount = Number(data.amount); data.account_id = Number(data.account_id);
    data.to_account_id = data.type === 'transfer' ? Number(form.querySelector('[data-offline-field="to_account_id"]')?.value) : null;
    data.category_id = data.type === 'transfer' ? null : Number(form.querySelector('[data-offline-field="category_id"]')?.value);
    data.note ||= null;
    if (!data.amount || !data.date || !data.account_id || (data.type === 'transfer' ? !data.to_account_id : !data.category_id)) {
        alert('Lengkapi jumlah, tanggal, akun, dan kategori atau akun tujuan.'); return;
    }
    await saveQueueItem(data);
    registerBackgroundSync();
    form.querySelector('[data-offline-field="amount"]').value = '';
    form.querySelector('[data-offline-field="note"]').value = '';
    await refreshQueueStatus();
}, true);

document.addEventListener('click', async (event) => {
    const button = event.target.closest('#transaction-form [data-offline-type]');
    if (!button || navigator.onLine) return;
    event.preventDefault(); event.stopImmediatePropagation();
    const form = button.closest('#transaction-form');
    const type = button.dataset.offlineType;
    form.querySelector('[data-offline-field="type"]').value = type;
    form.querySelectorAll('[data-offline-type]').forEach((item) => {
        item.classList.toggle('bg-main', item === button);
        item.classList.toggle('text-ink', item === button);
        item.classList.toggle('bg-light', item !== button);
        item.classList.toggle('text-dark', item !== button);
    });
    const categoryWrap = document.getElementById('transaction-category-wrap');
    const destinationWrap = document.getElementById('transaction-to-account-wrap');
    if (categoryWrap) categoryWrap.hidden = type === 'transfer';
    if (destinationWrap) destinationWrap.hidden = type !== 'transfer';
    const categorySelect = form.querySelector('[data-offline-field="category_id"]');
    if (categorySelect) {
        const meta = await getMeta();
        const previous = categorySelect.value;
        categorySelect.replaceChildren(option('', 'Kategori...'));
        for (const category of (meta?.categories ?? []).filter((item) => item.type === type)) categorySelect.append(option(category.id, category.name));
        if ([...categorySelect.options].some((item) => item.value === previous)) categorySelect.value = previous;
    }
}, true);

if ('serviceWorker' in navigator) navigator.serviceWorker.register('/service-worker.js').catch(() => {});
let installPrompt;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault(); installPrompt = event;
    document.getElementById('pwa-install-button')?.classList.remove('hidden');
});
document.getElementById('pwa-install-button')?.addEventListener('click', async (event) => {
    if (!installPrompt) return;
    await installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null; event.currentTarget.classList.add('hidden');
});
document.addEventListener('DOMContentLoaded', () => { captureOnlineData(); setupOfflineForm(); refreshQueueStatus(); });
document.addEventListener('livewire:navigated', () => { captureOnlineData(); setupOfflineForm(); refreshQueueStatus(); });
window.addEventListener('online', () => { registerBackgroundSync(); syncQueue(); });
window.addEventListener('offline', refreshQueueStatus);
navigator.serviceWorker?.addEventListener('message', (event) => { if (event.data?.type === 'FINWISE_SYNC') syncQueue(); });
})();

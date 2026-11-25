(() => {
if (window.__finwiseOfflineInitialized) return;
window.__finwiseOfflineInitialized = true;

const DB_NAME = 'finwise-offline';
const DB_VERSION = 2;
const text = {
    id: { income:'Pemasukan', expense:'Pengeluaran', transfer:'Transfer', waiting:'transaksi menunggu sinkronisasi', review:'perlu diperiksa', all:'Semua transaksi sudah tersinkron.', remove:'Hapus', removeOffline:'Hapus transaksi offline', online:'Online.', offline:'Anda sedang offline.', queue:'Kelola antrean', offlineForm:'Buka formulir offline', wrongUser:'Sinkronisasi dijeda karena akun aktif berbeda. Masuk dengan akun yang digunakan saat mencatat transaksi offline.', title:'Mode offline - finWise', heading:'Mode offline', intro:'Transaksi disimpan di perangkat ini dan akan dikirim saat koneksi kembali. Jangan hapus data browser sebelum sinkronisasi selesai.', pending:'Transaksi menunggu sinkronisasi', kind:'Jenis transaksi', amount:'Jumlah (Rp)', from:'Dari akun', category:'Kategori', to:'Ke akun', date:'Tanggal', note:'Catatan', save:'Simpan di perangkat', tryAgain:'Coba buka aplikasi lagi', choose:'Pilih kategori', onlineFirst:'Buka aplikasi saat online terlebih dahulu agar data akun tersimpan aman di perangkat.', complete:'Lengkapi jumlah, tanggal, akun, dan kategori atau akun tujuan.', invalidFormat:'Format transaksi offline tidak valid.', invalidData:'Data transaksi tidak valid. Periksa akun dan kategori.', expenseLimit:'Pengeluaran melewati batas budget bulanan. Sisa yang boleh dipakai Rp :amount.' },
    en: { income:'Income', expense:'Expense', transfer:'Transfer', waiting:'transactions waiting to sync', review:'need review', all:'All transactions are synced.', remove:'Remove', removeOffline:'Remove offline transaction', online:'Online.', offline:'Offline.', queue:'Manage queue', offlineForm:'Open offline form', wrongUser:'Sync paused because a different account is active. Sign in with the account used to record the offline transaction.', title:'Offline mode - finWise', heading:'Offline mode', intro:'Transactions are saved on this device and sent when you reconnect. Do not clear browser data before syncing is complete.', pending:'Transactions waiting to sync', kind:'Transaction type', amount:'Amount (Rp)', from:'From account', category:'Category', to:'To account', date:'Date', note:'Note', save:'Save on device', tryAgain:'Try opening the app again', choose:'Choose a category', onlineFirst:'Open the app while online first so account data can be safely saved on this device.', complete:'Enter the amount, date, account, and category or destination account.', invalidFormat:'The offline transaction format is invalid.', invalidData:'The transaction data is invalid. Check the account and category.', expenseLimit:'This expense exceeds the monthly budget. Remaining amount: Rp :amount.' },
    ja: { income:'収入', expense:'支出', transfer:'振替', waiting:'件の取引が同期待ちです', review:'件の確認が必要', all:'すべての取引が同期されました。', remove:'削除', removeOffline:'オフライン取引を削除', online:'オンライン。', offline:'オフライン。', queue:'キューを管理', offlineForm:'オフライン入力を開く', wrongUser:'別のアカウントが使用中のため同期を一時停止しました。オフライン取引を記録したアカウントでログインしてください。', title:'オフラインモード - finWise', heading:'オフラインモード', intro:'取引はこの端末に保存され、接続が戻ると送信されます。同期が完了する前にブラウザーのデータを消去しないでください。', pending:'同期待ちの取引', kind:'取引の種類', amount:'金額 (Rp)', from:'振替元口座', category:'カテゴリー', to:'振替先口座', date:'日付', note:'メモ', save:'端末に保存', tryAgain:'もう一度アプリを開く', choose:'カテゴリーを選択', onlineFirst:'口座データを安全に保存するため、オンラインでアプリを開いてください。', complete:'金額、日付、口座、カテゴリーまたは振替先口座を入力してください。', invalidFormat:'オフライン取引の形式が正しくありません。', invalidData:'取引データが正しくありません。口座とカテゴリーを確認してください。', expenseLimit:'支出が月間予算を超えます。残りの使用可能額: Rp :amount。' },
};
const offlineUi = {
    id: { home:'Beranda', transactions:'Transaksi', accounts:'Akun', categories:'Kategori', budgets:'Budget', goals:'Tabungan', analysis:'Analisis', more:'Lainnya', add_transaction:'Tambah transaksi', offlineBanner:'Anda sedang offline · Menampilkan data terakhir yang tersimpan', balance:'Total saldo', incomeTotal:'Pemasukan bulan ini', expenseTotal:'Pengeluaran bulan ini', recent:'Transaksi terbaru', noData:'Belum ada snapshot. Buka finWise saat online terlebih dahulu.', captured:'Data tersimpan di perangkat', noRows:'Belum ada data.', monthBudget:'Batas budget bulanan', remaining:'Sisa', spent:'Terpakai', target:'Target', saved:'Terkumpul', type:'Jenis', amount:'Nominal', date:'Tanggal', categoryName:'Kategori', accountName:'Akun', pending:'Menunggu sinkronisasi', transfer:'Transfer', cash:'Tunai', bank:'Bank', ewallet:'E-Wallet', goalProgress:'Progres target', analysisHint:'Ringkasan dihitung dari transaksi yang tersimpan di perangkat (maksimal 200 terakhir).' },
    en: { home:'Home', transactions:'Transactions', accounts:'Accounts', categories:'Categories', budgets:'Budgets', goals:'Savings', analysis:'Analysis', more:'More', add_transaction:'Add transaction', offlineBanner:'You are offline · Showing the latest data saved on this device', balance:'Total balance', incomeTotal:'Income this month', expenseTotal:'Expenses this month', recent:'Recent transactions', noData:'No snapshot yet. Open finWise while online first.', captured:'Data saved on this device', noRows:'No data yet.', monthBudget:'Monthly budget limit', remaining:'Remaining', spent:'Spent', target:'Target', saved:'Saved', type:'Type', amount:'Amount', date:'Date', categoryName:'Category', accountName:'Account', pending:'Waiting to sync', transfer:'Transfer', cash:'Cash', bank:'Bank', ewallet:'E-Wallet', goalProgress:'Goal progress', analysisHint:'Summary uses transactions saved on this device (up to the latest 200).' },
    ja: { home:'\u30db\u30fc\u30e0', transactions:'\u53d6\u5f15', accounts:'\u53e3\u5ea7', categories:'\u30ab\u30c6\u30b4\u30ea\u30fc', budgets:'\u4e88\u7b97', goals:'\u8caf\u91d1', analysis:'\u5206\u6790', more:'\u305d\u306e\u4ed6', add_transaction:'\u53d6\u5f15\u3092\u8ffd\u52a0', offlineBanner:'\u30aa\u30d5\u30e9\u30a4\u30f3\u3067\u3059 · \u7aef\u672b\u306b\u4fdd\u5b58\u3057\u305f\u6700\u65b0\u30c7\u30fc\u30bf\u3092\u8868\u793a\u3057\u3066\u3044\u307e\u3059', balance:'\u7dcf\u6b8b\u9ad8', incomeTotal:'\u4eca\u6708\u306e\u53ce\u5165', expenseTotal:'\u4eca\u6708\u306e\u652f\u51fa', recent:'\u6700\u8fd1\u306e\u53d6\u5f15', noData:'\u30aa\u30d5\u30e9\u30a4\u30f3\u30c7\u30fc\u30bf\u304c\u3042\u308a\u307e\u305b\u3093\u3002\u5148\u306b\u30aa\u30f3\u30e9\u30a4\u30f3\u3067\u958b\u3044\u3066\u304f\u3060\u3055\u3044\u3002', captured:'\u30c7\u30fc\u30bf\u3092\u7aef\u672b\u306b\u4fdd\u5b58', noRows:'\u30c7\u30fc\u30bf\u306f\u3042\u308a\u307e\u305b\u3093\u3002', monthBudget:'\u6708\u9593\u4e88\u7b97\u4e0a\u9650', remaining:'\u6b8b\u308a', spent:'\u4f7f\u7528\u6e08\u307f', target:'\u76ee\u6a19', saved:'\u8caf\u91d1\u6e08\u307f', type:'\u7a2e\u985e', amount:'\u91d1\u984d', date:'\u65e5\u4ed8', categoryName:'\u30ab\u30c6\u30b4\u30ea\u30fc', accountName:'\u53e3\u5ea7', pending:'\u540c\u671f\u5f85\u3061', transfer:'\u632f\u66ff', cash:'\u73fe\u91d1', bank:'\u9280\u884c', ewallet:'\u96fb\u5b50\u30de\u30cd\u30fc', goalProgress:'\u76ee\u6a19\u306e\u9032\u6357', analysisHint:'\u7aef\u672b\u306b\u4fdd\u5b58\u3055\u308c\u305f\u6700\u65b0\u306e200\u4ef6\u307e\u3067\u306e\u53d6\u5f15\u3092\u96c6\u8a08\u3057\u307e\u3059\u3002' },
};
let activeLocale = document.documentElement.lang?.slice(0, 2) || 'id';
const t = (key) => (text[activeLocale] || text.id)[key] || text.id[key] || key;
const formatMessage = (key, params = {}) => Object.entries(params).reduce((message, [name, value]) => message.replaceAll(`:${name}`, value), t(key));
function localizeOfflinePage(locale) {
    activeLocale = locale || activeLocale;
    document.documentElement.lang = activeLocale;
    document.querySelectorAll('[data-i18n]').forEach((element) => {
        const key = element.dataset.i18n;
        element.textContent = ui(key) !== key ? ui(key) : t(translationAliases[key] || key);
    });
    document.querySelectorAll('[data-i18n-aria]').forEach((element) => {
        const key = element.dataset.i18nAria;
        element.setAttribute('aria-label', ui(key) !== key ? ui(key) : t(translationAliases[key] || key));
    });
}
const ui = (key) => (offlineUi[activeLocale] || offlineUi.id)[key] || offlineUi.id[key] || key;
const translationAliases = { amount_label: 'amount', from_account: 'from', to_account: 'to', save_offline: 'save', try_again: 'tryAgain', offline_pending: 'pending', transaction_kind: 'kind' };
const money = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
const node = (tag, className, value) => { const element = document.createElement(tag); if (className) element.className = className; if (value !== undefined) element.textContent = value; return element; };
function renderRow(container, title, detail, amount = '') {
    const row = node('div', 'data-row');
    const left = node('span', '', title); left.append(node('small', '', detail));
    row.append(left, node('strong', '', amount)); container.append(row);
}
function inCurrentMonth(date) { const value = new Date(`${date}T00:00:00`); const now = new Date(); return value.getMonth() === now.getMonth() && value.getFullYear() === now.getFullYear(); }
function signedAmount(item) { return `${item.type === 'income' ? '+' : item.type === 'expense' ? '−' : '↔'}${money(item.amount)}`; }
async function renderOfflineApp() {
    const snapshot = await getSnapshot();
    const tabs = document.getElementById('offline-tabs');
    if (!tabs) return;
    const names = ['dashboard', 'analysis', 'transactions', 'goals', 'more'];
    tabs.replaceChildren();
    const icons = { dashboard: '⌂', analysis: '◷', transactions: '＋', goals: '☆', more: '•••' };
    for (const name of names) {
        const button = node('button'); button.type = 'button'; button.dataset.view = name; button.setAttribute('aria-selected', name === 'dashboard' ? 'true' : 'false');
        button.append(node('span', 'nav-icon', icons[name]), node('span', 'nav-label', ui(name)));
        button.addEventListener('click', () => {
            showOfflineView(name);
        });
        tabs.append(button);
    }
    const status = document.getElementById('offline-snapshot-status');
    const viewNames = ['dashboard', 'transactions', 'accounts', 'categories', 'budgets', 'goals', 'analysis'];
    viewNames.forEach((name) => document.getElementById(`view-${name}`).replaceChildren());
    document.querySelectorAll('#view-more [data-view]').forEach((button) => button.addEventListener('click', () => showOfflineView(button.dataset.view)));
    if (!snapshot) {
        status.textContent = ui('noData');
        document.getElementById('view-dashboard').append(node('p', 'muted', ui('noData')));
        return;
    }
    activeLocale = snapshot.locale || activeLocale;
    status.textContent = `${ui('captured')} · ${new Date(snapshot.capturedAt).toLocaleString()}`;
    const queue = (await getQueue()).filter((item) => item.owner_id === snapshot.userId);
    const queuedIncome = queue.filter((item) => item.type === 'income').reduce((sum, item) => sum + Number(item.amount), 0);
    const queuedExpense = queue.filter((item) => item.type === 'expense').reduce((sum, item) => sum + Number(item.amount), 0);
    const summary = snapshot.summary || {};
    const dashboard = document.getElementById('view-dashboard');
    const metric = node('div', 'metric'); metric.textContent = ui('balance'); metric.append(node('strong', '', money(Number(summary.balance) + queuedIncome - queuedExpense))); dashboard.append(metric);
    const totals = node('div', 'card');
    renderRow(totals, ui('incomeTotal'), '', money(Number(summary.income) + queuedIncome));
    renderRow(totals, ui('expenseTotal'), '', money(Number(summary.expense) + queuedExpense));
    const monthLimit = Number(summary.monthly_budget || 0);
    if (monthLimit) renderRow(totals, ui('monthBudget'), `${ui('remaining')}: ${money(monthLimit - Number(summary.expense) - queuedExpense)}`, money(monthLimit));
    dashboard.append(totals, node('h2', '', ui('recent')));
    const sorted = [...(snapshot.transactions || [])].sort((a, b) => b.date.localeCompare(a.date));
    const recent = node('div', 'card');
    for (const item of sorted.slice(0, 5)) renderRow(recent, item.category || t(item.type), `${item.date} · ${item.account || ''}`, signedAmount(item));
    if (!sorted.length) recent.textContent = ui('noRows');
    dashboard.append(recent);

    const transactions = document.getElementById('offline-transactions');
    transactions.replaceChildren(node('h2', '', ui('transactions')));
    for (const item of [...queue.map((entry) => ({ ...entry, isPending: true })), ...(snapshot.transactions || [])].sort((a, b) => b.date.localeCompare(a.date))) {
        renderRow(transactions, item.category || t(item.type), `${item.date} · ${item.account || snapshot.accounts.find((account) => account.id === item.account_id)?.name || ''}${item.isPending ? ` · ${ui('pending')}` : ''}`, signedAmount(item));
    }
    if (!snapshot.transactions?.length && !queue.length) transactions.append(node('p', 'muted', ui('noRows')));

    const accounts = document.getElementById('view-accounts'); accounts.append(node('h2', '', ui('accounts')));
    for (const account of snapshot.accounts || []) {
        const delta = queue.reduce((sum, item) => sum + (item.account_id === account.id ? (item.type === 'income' ? Number(item.amount) : -Number(item.amount)) : 0) + (item.type === 'transfer' && item.to_account_id === account.id ? Number(item.amount) : 0), 0);
        renderRow(accounts, account.name, t(account.type), money(Number(account.balance) + delta));
    }
    const categories = document.getElementById('view-categories'); categories.append(node('h2', '', ui('categories')));
    for (const type of ['expense', 'income']) {
        categories.append(node('h3', 'mt-4 font-semibold', t(type)));
        const values = (snapshot.categories || []).filter((category) => category.type === type);
        values.forEach((category) => renderRow(categories, category.name, '', ''));
        if (!values.length) categories.append(node('p', 'muted', ui('noRows')));
    }
    const budgets = document.getElementById('view-budgets'); budgets.append(node('h2', '', ui('budgets')));
    if (monthLimit) renderRow(budgets, ui('monthBudget'), `${ui('spent')}: ${money(Number(summary.expense) + queuedExpense)} · ${ui('remaining')}: ${money(monthLimit - Number(summary.expense) - queuedExpense)}`, money(monthLimit));
    for (const budget of snapshot.budgets || []) {
        const pendingSpend = queue.filter((item) => item.type === 'expense' && item.category_id === budget.category_id && inCurrentMonth(item.date)).reduce((sum, item) => sum + Number(item.amount), 0);
        const spent = Number(budget.spent) + pendingSpend; const pct = Math.min(100, Math.round(spent / Math.max(1, Number(budget.amount)) * 100));
        const block = node('div', 'mt-4'); renderRow(block, budget.category, `${ui('spent')}: ${money(spent)}`, `${money(budget.amount)}`);
        const meter = node('div', 'meter'); const fill = node('span'); fill.style.width = `${pct}%`; meter.append(fill); block.append(meter); budgets.append(block);
    }
    const goals = document.getElementById('view-goals'); goals.append(node('h2', '', ui('goals')));
    for (const goal of snapshot.goals || []) {
        const pct = Math.min(100, Math.round(Number(goal.saved_amount) / Math.max(1, Number(goal.target_amount)) * 100));
        const block = node('div', 'mt-4'); renderRow(block, goal.name, `${ui('saved')}: ${money(goal.saved_amount)} · ${goal.target_date || ''}`, `${money(goal.target_amount)}`);
        const meter = node('div', 'meter'); const fill = node('span'); fill.style.width = `${pct}%`; meter.append(fill); block.append(meter); goals.append(block);
    }
    const analysis = document.getElementById('view-analysis'); analysis.append(node('h2', '', ui('analysis')), node('p', 'muted', ui('analysisHint')));
    const spending = new Map();
    for (const item of [...(snapshot.transactions || []), ...queue]) if (item.type === 'expense' && inCurrentMonth(item.date)) spending.set(item.category || snapshot.categories.find((category) => category.id === item.category_id)?.name || ui('expense'), (spending.get(item.category || snapshot.categories.find((category) => category.id === item.category_id)?.name || ui('expense')) || 0) + Number(item.amount));
    [...spending.entries()].sort((a, b) => b[1] - a[1]).forEach(([name, amount]) => renderRow(analysis, name, ui('expenseTotal'), money(amount)));
    if (!spending.size) analysis.append(node('p', 'muted', ui('noRows')));
}
function showOfflineView(name) {
    document.querySelectorAll('.offline-view').forEach((view) => view.classList.toggle('active', view.id === `view-${name}`));
    const activeTab = ['accounts', 'categories', 'budgets'].includes(name) ? 'more' : name;
    document.querySelectorAll('#offline-tabs button').forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.view === activeTab ? 'true' : 'false'));
    const title = document.getElementById('offline-page-title');
    if (title) title.textContent = ui(name);
}
const openDb = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('queue')) db.createObjectStore('queue', { keyPath: 'client_id' });
        if (!db.objectStoreNames.contains('snapshot')) db.createObjectStore('snapshot', { keyPath: 'id' });
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
const getSnapshot = () => storeRequest('snapshot', 'readonly', (store) => store.get('finance'));
const getQueue = () => storeRequest('queue', 'readonly', (store) => store.getAll());
const saveQueueItem = (item) => storeRequest('queue', 'readwrite', (store) => store.put(item));
const deleteQueueItem = (id) => storeRequest('queue', 'readwrite', (store) => store.delete(id));
const setMeta = (data) => storeRequest('meta', 'readwrite', (store) => store.put({ ...data, id: 'finance' }));
const setSnapshot = (data) => storeRequest('snapshot', 'readwrite', (store) => store.put({ ...data, id: 'finance' }));
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
    const message = queue.length ? `${queue.length} ${t('waiting')}${failedCount ? ` (${failedCount} ${t('review')})` : ''}.` : t('all');
    if (status) status.textContent = message;
    const pendingList = document.getElementById('offline-pending-list');
    if (pendingList) {
        pendingList.replaceChildren();
        for (const item of queue) {
            const row = document.createElement('div'); row.className = 'pending-item';
            const details = document.createElement('span');
            const syncError = item.sync_error_key ? formatMessage(item.sync_error_key, item.sync_error_params) : item.sync_error;
            details.textContent = `${t(item.type)} · Rp ${Number(item.amount).toLocaleString('id-ID')} · ${item.date}${syncError ? ` — ${syncError}` : ''}`;
            const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = t('remove'); remove.setAttribute('aria-label', t('removeOffline'));
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
            ? `${t('online')} ${message} <a class="font-semibold text-blue underline" href="/offline.html">${t('queue')}</a>`
            : `${t('offline')} ${message} <a class="font-semibold text-blue underline" href="/offline.html">${t('offlineForm')}</a>`);
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
                if (item) await saveQueueItem({ ...item, sync_error: failure.message, sync_error_key: failure.message_key, sync_error_params: failure.message_params });
            }
        } else if (response.status === 409) {
            syncWarning = t('wrongUser');
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
    const meta = await getMeta();
    if (meta?.locale) localizeOfflinePage(meta.locale);
    await renderOfflineApp();
    if (!form) return;
    const accountSelect = form.elements.account_id;
    const destination = form.elements.to_account_id;
    const categorySelect = form.elements.category_id;
    const amountDisplay = form.querySelector('#offline-amount-display');
    const amountValue = form.elements.amount;
    amountDisplay.addEventListener('input', () => {
        const digits = amountDisplay.value.replace(/\D/g, '');
        amountDisplay.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        amountValue.value = digits;
    });
    for (const account of meta?.accounts ?? []) {
        accountSelect.append(option(account.id, account.name));
        destination.append(option(account.id, account.name));
    }
    function setType(type) {
        form.dataset.type = type;
        form.querySelectorAll('[data-type]').forEach((button) => button.setAttribute('aria-pressed', button.dataset.type === type ? 'true' : 'false'));
        document.getElementById('category-wrap').hidden = type === 'transfer';
        document.getElementById('to-account-wrap').hidden = type !== 'transfer';
        categorySelect.replaceChildren(option('', t('choose')));
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
        if (!meta?.userId) { alert(t('onlineFirst')); return; }
        const item = {
            client_id: uuid(), type, amount: values.get('amount'), date: values.get('date'),
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
    try {
        const snapshot = JSON.parse(element.textContent);
        await setMeta({ userId: snapshot.userId, locale: snapshot.locale, accounts: snapshot.accounts, categories: snapshot.categories, csrfToken });
        await setSnapshot(snapshot);
    }
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
    if (!meta?.userId) { alert(t('onlineFirst')); return; }
    data.client_id = uuid(); data.owner_id = meta.userId; data.account_id = Number(data.account_id);
    data.to_account_id = data.type === 'transfer' ? Number(form.querySelector('[data-offline-field="to_account_id"]')?.value) : null;
    data.category_id = data.type === 'transfer' ? null : Number(form.querySelector('[data-offline-field="category_id"]')?.value);
    data.note ||= null;
    if (!data.amount || !data.date || !data.account_id || (data.type === 'transfer' ? !data.to_account_id : !data.category_id)) {
        alert(t('complete')); return;
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
        item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
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
        categorySelect.replaceChildren(option('', t('category')));
        for (const category of (meta?.categories ?? []).filter((item) => item.type === type)) categorySelect.append(option(category.id, category.name));
        if ([...categorySelect.options].some((item) => item.value === previous)) categorySelect.value = previous;
    }
}, true);

if ('serviceWorker' in navigator) navigator.serviceWorker.register('/service-worker.js').catch(() => {});
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-enable-notifications]');
    if (!button) return;
    if (!('Notification' in window) || !('serviceWorker' in navigator)) return;
    if (Notification.permission === 'default') await Notification.requestPermission();
    syncNotificationButton(button);
});
function syncNotificationButton(button = document.querySelector('[data-enable-notifications]')) {
    if (!button) return;
    if (!('Notification' in window) || !('serviceWorker' in navigator) || !window.isSecureContext) { button.hidden = true; return; }
    if (Notification.permission === 'granted') button.textContent = button.dataset.grantedLabel;
    if (Notification.permission === 'denied') button.textContent = button.dataset.deniedLabel;
    button.disabled = Notification.permission !== 'default';
}
window.addEventListener('finance-notification', async (event) => {
    if (!('Notification' in window) || Notification.permission !== 'granted' || !('serviceWorker' in navigator)) return;
    const registration = await navigator.serviceWorker.ready;
    registration.active?.postMessage({ type: 'FINWISE_SHOW_NOTIFICATION', title: event.detail.title, body: event.detail.body });
});
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
document.addEventListener('DOMContentLoaded', () => { captureOnlineData(); setupOfflineForm(); refreshQueueStatus(); syncNotificationButton(); });
document.addEventListener('livewire:navigated', () => { captureOnlineData(); setupOfflineForm(); refreshQueueStatus(); syncNotificationButton(); });
window.addEventListener('locale-changed', (event) => { activeLocale = event.detail?.locale || activeLocale; document.documentElement.lang = activeLocale; refreshQueueStatus(); });
window.addEventListener('online', () => { registerBackgroundSync(); syncQueue(); });
window.addEventListener('offline', refreshQueueStatus);
navigator.serviceWorker?.addEventListener('message', (event) => { if (event.data?.type === 'FINWISE_SYNC') syncQueue(); });
})();

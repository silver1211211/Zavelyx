<script setup>
import ServiceLogo from '@/Components/ServiceLogo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle, Check, CheckCircle2, ChevronRight, Clock,
    Copy, CreditCard, Globe, Loader2, Phone, Search,
    ShoppingCart, X, Zap,
} from 'lucide-vue-next';
import { computed, nextTick, onErrorCaptured, onMounted, onUnmounted, ref, watch } from 'vue';

const { symbol, convertAmount } = useCurrency();
const page = usePage();

// ── Live wallet balance ────────────────────────────────────────────────────────
const walletBalance = ref(parseFloat(page.props.auth?.user?.wallet?.balance ?? 0));
let balanceRefreshHandler = null;
onMounted(() => {
    balanceRefreshHandler = async () => {
        try {
            const res = await fetch('/wallet/balance', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.ok) { const d = await res.json(); walletBalance.value = parseFloat(d.balance) || 0; }
        } catch {}
    };
    window.addEventListener('balance-refresh', balanceRefreshHandler);
});

// ── Debounce ──────────────────────────────────────────────────────────────────
function debounce(fn, ms = 250) {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── Safe string coercion — prevents (s ?? "").toLowerCase TypeError ─────────
const ss = v => String(v == null ? '' : v);

// ── Category mapping ──────────────────────────────────────────────────────────
const CATEGORY_MAP = {
    Popular:       ['telegram','whatsapp','google','facebook','instagram','tiktok','twitter','discord','viber','youtube','reddit'],
    Crypto:        ['coinbase','binance','bybit','okx','kraken','kucoin','gate','mexc','bitget','huobi','bingx','phemex','lbank','bitmart','poloniex','gemini','crypto','trustwallet','metamask','blockchain','bitcoin','ethereum','btc','eth'],
    Finance:       ['paypal','wise','revolut','cashapp','stripe','venmo','zelle','klarna','monzo','n26','chime','cash','paysend','webmoney','skrill','neteller','qiwi','yandex','bank','neobank','paysafecard'],
    Social:        ['snapchat','linkedin','pinterest','vk','ok','line','wechat','kakao','tumblr','imo','skype','kik','signal','zalo','naver','weibo','tinder','bumble','hinge','badoo','meetup'],
    Shopping:      ['amazon','ebay','airbnb','uber','lazada','shopee','aliexpress','walmart','etsy','nike','shein','wish','shopify','ozon','wildberries','avito','grab','gojek','lyft','mercado','allegro'],
    Gaming:        ['steam','epicgames','blizzard','roblox','twitch','ubisoft','activision','riot','genshin','warzone','pubg','minecraft','xbox','playstation','gamepass','origin','ea'],
    Entertainment: ['netflix','spotify','apple','disney','hulu','hbo','prime','deezer','tidal','mubi','paramount','peacock'],
    Email:         ['yahoo','microsoft','proton','zoho','gmx','aol','mail','outlook','hotmail','icloud'],
    AI:            ['openai','chatgpt','anthropic','midjourney','claude','perplexity','poe','jasper','runway','replicate'],
};
const CAT_ORDER = ['All','Popular','Crypto','Finance','Social','Shopping','Gaming','Entertainment','Email','AI','Other'];

function getCategory(id, label = '') {
    const key = ss(id).toLowerCase();
    for (const [cat, ids] of Object.entries(CATEGORY_MAP)) {
        if (ids.some(k => key === k || key.startsWith(k))) return cat;
    }
    const lbl = ss(label).toLowerCase();
    if (lbl) {
        for (const [cat, ids] of Object.entries(CATEGORY_MAP)) {
            if (ids.some(k => k.length >= 5 && lbl.includes(k))) return cat;
        }
    }
    return 'Other';
}

// ── Service labels ────────────────────────────────────────────────────────────
const SERVICE_LABELS = {
    telegram:'Telegram',whatsapp:'WhatsApp',google:'Google',discord:'Discord',
    openai:'OpenAI',tiktok:'TikTok',instagram:'Instagram',facebook:'Facebook',
    twitter:'Twitter / X',amazon:'Amazon',microsoft:'Microsoft',binance:'Binance',
    bybit:'Bybit',okx:'OKX',uber:'Uber',netflix:'Netflix',apple:'Apple',
    paypal:'PayPal',ebay:'eBay',airbnb:'Airbnb',linkedin:'LinkedIn',
    snapchat:'Snapchat',steam:'Steam',coinbase:'Coinbase',viber:'Viber',
    wise:'Wise',revolut:'Revolut',stripe:'Stripe',vk:'VK',
};
function svcLabel(id) {
    const k = ss(id).toLowerCase();
    return SERVICE_LABELS[k] ?? ss(id).replace(/[-_]/g,' ').replace(/\b\w/g, c => c.toUpperCase());
}

// ── Flags + country labels ────────────────────────────────────────────────────
// Keyed by lowercase ISO2 codes (SMSPVA uses uppercase; flag() lowercases input)
const FLAGS = {
    us:'🇺🇸', uk:'🇬🇧', gb:'🇬🇧', fr:'🇫🇷', de:'🇩🇪', es:'🇪🇸', it:'🇮🇹',
    au:'🇦🇺', mx:'🇲🇽', br:'🇧🇷', ph:'🇵🇭', id:'🇮🇩', jp:'🇯🇵', ro:'🇷🇴',
    pt:'🇵🇹', ca:'🇨🇦', ar:'🇦🇷', pl:'🇵🇱', gr:'🇬🇷', al:'🇦🇱', at:'🇦🇹',
    bd:'🇧🇩', be:'🇧🇪', bo:'🇧🇴', ba:'🇧🇦', bg:'🇧🇬', kh:'🇰🇭', cm:'🇨🇲',
    cl:'🇨🇱', co:'🇨🇴', cr:'🇨🇷', hr:'🇭🇷', cy:'🇨🇾', cz:'🇨🇿', dk:'🇩🇰',
    ee:'🇪🇪', fi:'🇫🇮', ge:'🇬🇪', gi:'🇬🇮', hk:'🇭🇰', hu:'🇭🇺',
    ie:'🇮🇪', il:'🇮🇱', kz:'🇰🇿', ke:'🇰🇪', kg:'🇰🇬', lv:'🇱🇻', lt:'🇱🇹',
    mk:'🇲🇰', my:'🇲🇾', mt:'🇲🇹', md:'🇲🇩', ma:'🇲🇦', nl:'🇳🇱', nz:'🇳🇿',
    pk:'🇵🇰', py:'🇵🇾', rs:'🇷🇸', sg:'🇸🇬', sk:'🇸🇰', si:'🇸🇮', za:'🇿🇦',
    se:'🇸🇪', ch:'🇨🇭', tz:'🇹🇿', th:'🇹🇭', tr:'🇹🇷', ua:'🇺🇦', vn:'🇻🇳',
    in:'🇮🇳', ru:'🇷🇺', cn:'🇨🇳', kr:'🇰🇷', ng:'🇳🇬', eg:'🇪🇬', sa:'🇸🇦',
};
function flag(c) { return FLAGS[ss(c).toLowerCase()] ?? '🌐'; }
function cLabel(c) { return ss(c).replace(/_/g,' ').replace(/\b\w/g, x => x.toUpperCase()); }

// ── Success / quality helpers ─────────────────────────────────────────────────
function rateColor(rate) {
    if (rate >= 95) return 'text-emerald-500 dark:text-emerald-400';
    if (rate >= 80) return 'text-amber-500 dark:text-amber-400';
    return 'text-rose-500 dark:text-rose-400';
}
function rateBarColor(rate) {
    if (rate >= 95) return '#10b981';
    if (rate >= 80) return '#f59e0b';
    return '#ef4444';
}
function estimateSpeed(qty) {
    if (qty >= 50000) return '~15 sec';
    if (qty >= 10000) return '~30 sec';
    if (qty >= 1000)  return '~1 min';
    if (qty >= 100)   return '~3 min';
    return '~5 min';
}
function stockBarWidth(qty) {
    if (qty >= 50000) return 100;
    if (qty >= 10000) return 75;
    if (qty >= 1000)  return 45;
    return 20;
}
function stockBarColor(qty) {
    if (qty >= 10000) return '#10b981';
    if (qty >= 1000)  return '#f59e0b';
    return '#ef4444';
}

// Human-readable operator names
const OPERATOR_NAMES = {
    any: 'Best Available',
    virtual1: 'Virtual 1', virtual2: 'Virtual 2', virtual3: 'Virtual 3',
    virtual4: 'Virtual 4', virtual5: 'Virtual 5', virtual6: 'Virtual 6',
    virtual7: 'Virtual 7', virtual8: 'Virtual 8', virtual9: 'Virtual 9',
    telkomsel: 'Telkomsel', beeline: 'Beeline', megafon: 'MegaFon',
    tele2: 'Tele 2', mts: 'MTS', yota: 'Yota', tinkoff: 'Tinkoff',
    tmobile: 'T-Mobile', att: 'AT&T', verizon: 'Verizon',
    lyca: 'Lyca Mobile', lebara: 'Lebara', three: 'Three',
    orange: 'Orange', vodafone: 'Vodafone', o2: 'O2',
};
function operatorLabel(name) {
    if (name == null || name === '') return 'Best Available';
    const n = ss(name).toLowerCase();
    return OPERATOR_NAMES[n] ?? ss(name).replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// Real rate for a country (null if API didn't return one)
function countryRealRate(country) {
    if (!country.operators?.length) return null;
    const max = Math.max(...country.operators.map(op => op.rate ?? 0));
    return max > 0 ? Math.round(max) : null;
}

// ── Service priority order ────────────────────────────────────────────────────
const PRIORITY_SERVICES = [
    'telegram','whatsapp','google','gmail','discord','tiktok',
    'instagram','facebook','snapchat','twitter','x','openai',
    'uber','binance','amazon','microsoft',
];
function svcPriority(id) {
    const idx = PRIORITY_SERVICES.indexOf(ss(id).toLowerCase());
    return idx >= 0 ? idx : PRIORITY_SERVICES.length;
}

// ── Core state ────────────────────────────────────────────────────────────────
const allServices      = ref([]);
const loadingSvcs      = ref(true); // true until first seed, prevents "Select a service" flash
const svcsError        = ref(null);
const svcsRefreshing   = ref(false); // true while background-fetching live prices (seed shown)
const activeCat        = ref('All');
const selectedSvc      = ref(null);
const selectedCountry  = ref(null);

const countryStockList  = ref([]);
const loadingCountries  = ref(false);
const countriesError    = ref(null);

const buying    = ref(false);
const buyError  = ref(null);
const fatalError = ref(null); // error boundary — caught by onErrorCaptured

onErrorCaptured((err) => {
    console.error('[Zavelyx] Buy page error captured:', err);
    fatalError.value = err?.message || 'An unexpected error occurred. Please refresh the page.';
    return false; // stop propagation
});

// ── Debounced search state ────────────────────────────────────────────────────
const svcSearchRaw           = ref('');
const countrySearchRaw       = ref('');
const debouncedSvcSearch     = ref('');
const debouncedCountrySearch = ref('');
const updateSvcSearch     = debounce(v => { debouncedSvcSearch.value = v; }, 200);
const updateCountrySearch = debounce(v => { debouncedCountrySearch.value = v; }, 150);

// ── Mobile sidebar ────────────────────────────────────────────────────────────
const sidebarOpen = ref(false);
const isSmallScreen = ref(false);
const mobileCarrierView = computed(() => isSmallScreen.value && !!selectedCountry.value);

onMounted(() => {
    isSmallScreen.value = window.matchMedia('(max-width: 1023px)').matches;
    if (isSmallScreen.value) sidebarOpen.value = true;
});

// ── Insufficient balance modal ────────────────────────────────────────────────
const showInsufficientModal = ref(false);
const insufficientNeeded    = ref(0);

function checkBalance(cost) {
    if (walletBalance.value < cost) {
        insufficientNeeded.value = cost;
        showInsufficientModal.value = true;
        return false;
    }
    return true;
}

// ── Purchase modal + polling ──────────────────────────────────────────────────
const purchaseOrder     = ref(null);
const showPurchaseModal = ref(false);
const expiryCountdown   = ref(null);
const copied            = ref(null);
const smsPrev           = ref(null); // track SMS arrival for animation
let pollTimer      = null;
let countdownTimer = null;

function isTerminal(s) {
    return ['FINISHED','CANCELLED','BANNED','EXPIRED','TIMEOUT'].includes(s?.toUpperCase());
}

function openPurchaseModal(order) {
    purchaseOrder.value     = order;
    showPurchaseModal.value = true;
    smsPrev.value           = order.otp_code ?? null;
    startCountdown(order.expires_at);
    startSmsPolling(order);
}

function closePurchaseModal() {
    showPurchaseModal.value = false;
    stopSmsPolling();
    stopCountdown();
    purchaseOrder.value   = null;
    expiryCountdown.value = null;
    copied.value          = null;
    smsPrev.value         = null;
}

function startCountdown(expiresAt) {
    stopCountdown();
    if (!expiresAt) return;
    function tick() {
        const diff = Math.max(0, Math.floor((new Date(expiresAt) - Date.now()) / 1000));
        if (diff === 0) { expiryCountdown.value = null; stopCountdown(); return; }
        const m = Math.floor(diff / 60), s = diff % 60;
        expiryCountdown.value = `${m}:${String(s).padStart(2,'0')}`;
    }
    tick();
    countdownTimer = setInterval(tick, 1000);
}
function stopCountdown() { if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; } }

function startSmsPolling(order) {
    stopSmsPolling();
    if (!order || isTerminal(order.status)) return;
    pollTimer = setInterval(pollOrder, 5000);
}
function stopSmsPolling() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

async function pollOrder() {
    const o = purchaseOrder.value;
    if (!o || isTerminal(o.status)) { stopSmsPolling(); return; }
    try {
        const res = await fetch(`/sms/orders/${o.id}/poll`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.order) {
            const prev = purchaseOrder.value?.otp_code;
            purchaseOrder.value = data.order;
            if (!prev && data.order.otp_code) smsPrev.value = null; // triggers animation
            if (isTerminal(data.order.status)) stopSmsPolling();
        }
    } catch {}
}

async function copyText(text, key) {
    try {
        await navigator.clipboard.writeText(String(text));
        copied.value = key;
        setTimeout(() => { if (copied.value === key) copied.value = null; }, 2000);
    } catch {}
}

// ── Cancel from success modal ─────────────────────────────────────────────────
const cancelling = ref(false);

async function cancelFromModal() {
    const o = purchaseOrder.value;
    if (!o || cancelling.value || isTerminal(o.status) || o.otp_code) return;
    cancelling.value = true;
    try {
        const res = await fetch(`/sms/orders/${o.id}/cancel`, {
            method: 'POST', credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
            },
        });
        const data = await res.json();
        if (res.ok && data.order) {
            purchaseOrder.value = data.order;
            window.dispatchEvent(new Event('balance-refresh'));
            stopSmsPolling();
        }
    } catch {}
    finally { cancelling.value = false; }
}

onUnmounted(() => {
    stopSmsPolling();
    stopCountdown();
    if (balanceRefreshHandler) window.removeEventListener('balance-refresh', balanceRefreshHandler);
});

// ── Computed ──────────────────────────────────────────────────────────────────
const availableCategories = computed(() => {
    if (!allServices.value.length) return CAT_ORDER;
    const present = new Set(allServices.value.map(s => getCategory(s.id, s.label)));
    return CAT_ORDER.filter(c => c === 'All' || c === 'Other' || present.has(c));
});

const filteredServices = computed(() => {
    const q = debouncedSvcSearch.value.toLowerCase().trim();
    let list = allServices.value;
    if (q) return list.filter(s => ss(s.label).toLowerCase().includes(q) || ss(s.id).toLowerCase().includes(q));
    if (activeCat.value !== 'All') list = list.filter(s => getCategory(s.id, s.label) === activeCat.value);
    // Priority services first, then by stock descending
    return [...list].sort((a, b) => {
        const pa = svcPriority(a.id), pb = svcPriority(b.id);
        if (pa !== pb) return pa - pb;
        return (b.qty ?? 0) - (a.qty ?? 0);
    });
});

const filteredCountries = computed(() => {
    const q = debouncedCountrySearch.value.toLowerCase().trim();
    if (!q) return countryStockList.value;
    return countryStockList.value.filter(c =>
        ss(c.name).toLowerCase().includes(q) || ss(c.code).toLowerCase().includes(q)
    );
});

// Real per-country operators from /guest/prices response
const operatorOptions = computed(() => {
    if (!selectedCountry.value) return [];
    const c = selectedCountry.value;

    if (c.operators?.length) {
        return c.operators.map(op => ({
            operator: op.name,
            name:     operatorLabel(op.name),
            qty:      op.count ?? 0,
            cost:     op.cost  ?? 0,
            price:    op.price ?? 0,
            rate:     (op.rate ?? 0) > 0 ? Math.round(op.rate) : null, // null = no real data
        }));
    }

    // Fallback: synthesize 'any' from country aggregates
    return [{
        operator: 'any',
        name:     'Best Available',
        qty:      c.qty   ?? 0,
        cost:     c.cost  ?? 0,
        price:    c.price ?? 0,
        rate:     null,
    }];
});

// ── HTTP helpers ──────────────────────────────────────────────────────────────
async function fetchTimeout(url, opts = {}, ms = 20000) {
    const ctrl = new AbortController();
    const tid  = setTimeout(() => ctrl.abort(), ms);
    try {
        return await fetch(url, { ...opts, signal: ctrl.signal });
    } finally {
        clearTimeout(tid);
    }
}
function getCsrf() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
}

// ── Data normalizers — ensure API data is always properly typed ────────────────
function normalizeSvc(s) {
    return {
        ...s,
        id:       ss(s.id),
        label:    ss(s.label || s.id),
        category: ss(s.category || 'Other'),
        qty:      Number(s.qty) || 0,
        price:    Number(s.price) || 0,
    };
}
function normalizeCountry(c) {
    return {
        ...c,
        code:  ss(c.code),
        name:  ss(c.name || c.code),
        qty:   Number(c.qty) || 0,
        cost:  Number(c.cost) || 0,
        price: Number(c.price) || 0,
        operators: Array.isArray(c.operators) ? c.operators.map(op => ({
            ...op,
            name:  ss(op.name || 'any'),
            count: Number(op.count) || 0,
            cost:  Number(op.cost) || 0,
            price: Number(op.price) || 0,
            rate:  Number(op.rate) || 0,
        })) : [],
    };
}

// ── LocalStorage cache ────────────────────────────────────────────────────────
const SVCS_CACHE_KEY = 'nexahub_svc_list_v5';
const SVCS_CACHE_TTL = 10 * 60 * 1000;
function readSvcsCache() {
    try {
        const raw = localStorage.getItem(SVCS_CACHE_KEY);
        if (!raw) return null;
        const { ts, data } = JSON.parse(raw);
        if (Date.now() - ts > SVCS_CACHE_TTL) { localStorage.removeItem(SVCS_CACHE_KEY); return null; }
        if (!Array.isArray(data) || data.length < 10) return null;
        return data.map(normalizeSvc);
    } catch { return null; }
}
function writeSvcsCache(data) {
    try { localStorage.setItem(SVCS_CACHE_KEY, JSON.stringify({ ts: Date.now(), data })); } catch {}
}

const FALLBACK_SERVICES = [
    { id: 'telegram',  label: 'Telegram',    qty: 0, price: 0 },
    { id: 'whatsapp',  label: 'WhatsApp',    qty: 0, price: 0 },
    { id: 'google',    label: 'Google',      qty: 0, price: 0 },
    { id: 'instagram', label: 'Instagram',   qty: 0, price: 0 },
    { id: 'facebook',  label: 'Facebook',    qty: 0, price: 0 },
    { id: 'tiktok',    label: 'TikTok',      qty: 0, price: 0 },
    { id: 'discord',   label: 'Discord',     qty: 0, price: 0 },
    { id: 'twitter',   label: 'Twitter / X', qty: 0, price: 0 },
    { id: 'openai',    label: 'OpenAI',      qty: 0, price: 0 },
    { id: 'amazon',    label: 'Amazon',      qty: 0, price: 0 },
    { id: 'microsoft', label: 'Microsoft',   qty: 0, price: 0 },
    { id: 'uber',      label: 'Uber',        qty: 0, price: 0 },
];

// ── API: Services ─────────────────────────────────────────────────────────────
async function loadServices() {
    svcsError.value = null;

    // 1. Seed immediately so the UI renders without waiting for the API
    const cached = readSvcsCache();
    const seed   = cached ?? FALLBACK_SERVICES;
    allServices.value = seed;
    loadingSvcs.value = false; // have seed data — hide skeleton immediately

    if (!selectedSvc.value && !isSmallScreen.value) {
        const popular = seed.find(s => getCategory(s.id, s.label) === 'Popular');
        selectService(popular ?? seed[0]); // watch fires → loadCountryStock
    }

    // 2. Background-fetch the live service list (no loading indicator needed — sidebar silently refreshes)
    svcsRefreshing.value = true;
    try {
        console.log('[Zavelyx] loadServices: fetching /sms/services…');
        const res = await fetchTimeout('/sms/services', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }, 30000);
        let data;
        try   { data = await res.json(); }
        catch { throw new Error('Server returned invalid JSON.'); }
        console.log(`[Zavelyx] /sms/services → HTTP ${res.status}`, Array.isArray(data) ? `${data.length} services` : data);
        if (!res.ok) throw new Error(data?.error ?? `HTTP ${res.status}`);
        const list = (Array.isArray(data) ? data : [])
            .filter(s => s.id != null && s.id !== '')
            .map(normalizeSvc);
        if (!list.length) throw new Error('Empty service list.');
        writeSvcsCache(list);
        allServices.value = list;

        // Update selected service with fresh qty/price data.
        // Writing the ref directly (same ID) does NOT trigger the watch → no duplicate country load.
        if (selectedSvc.value) {
            const fresh = list.find(s => s.id === selectedSvc.value.id);
            if (fresh) selectedSvc.value = fresh;
        } else if (!isSmallScreen.value) {
            const popular = list.find(s => getCategory(s.id, s.label) === 'Popular');
            selectService(popular ?? list[0]);
        }
    } catch (e) {
        if (!allServices.value.length) {
            svcsError.value = e.name === 'AbortError'
                ? 'Timed out. Showing cached services.'
                : (e.message || 'Could not load services.');
        }
    } finally {
        svcsRefreshing.value = false;
    }
}

// ── API: Country stock ────────────────────────────────────────────────────────
// loadId prevents stale responses from a superseded request overwriting fresh state
let countryLoadId = 0;

async function loadCountryStock(serviceId) {
    if (!serviceId) return;
    const myId = ++countryLoadId;
    loadingCountries.value       = true;
    countriesError.value         = null;
    countryStockList.value       = [];
    selectedCountry.value        = null;
    countrySearchRaw.value       = '';
    debouncedCountrySearch.value = '';
    buyError.value               = null;

    console.log(`[Zavelyx] loadCountryStock(${serviceId}) id=${myId}`);

    try {
        const url = `/sms/country-stock?service=${encodeURIComponent(serviceId)}`;
        const res = await fetchTimeout(
            url,
            { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } },
            35000,
        );
        if (myId !== countryLoadId) { console.log(`[Zavelyx] loadCountryStock(${serviceId}) superseded — discarding`); return; }

        let data;
        try { data = await res.json(); } catch (e) { throw new Error(`Invalid server response: ${e.message}`); }

        console.log(`[Zavelyx] /sms/country-stock?service=${serviceId} → HTTP ${res.status}`, data);

        if (!res.ok) throw new Error(data?.error ?? `Server error ${res.status}`);

        const list = (Array.isArray(data) ? data : []).map(normalizeCountry);
        console.log(`[Zavelyx] Got ${list.length} countries for ${serviceId}`);
        countryStockList.value = list;
    } catch (e) {
        if (myId !== countryLoadId) return;
        console.error(`[Zavelyx] loadCountryStock(${serviceId}) failed:`, e);
        countriesError.value = e.name === 'AbortError'
            ? 'Country lookup timed out. Use Best Available above, or retry below.'
            : (e.message || 'Could not load country options.');
    } finally {
        if (myId === countryLoadId) loadingCountries.value = false;
    }
}

// ── Selection logic ───────────────────────────────────────────────────────────
function selectService(svc) {
    if (!svc) return;
    if (selectedSvc.value?.id === svc.id) return; // already selected — no-op
    console.log(`[Zavelyx] selectService(${svc.id})`);
    // Pre-set loading state synchronously so the skeleton shows before the watch fires
    loadingCountries.value  = true;
    countriesError.value    = null;
    countryStockList.value  = [];
    selectedCountry.value   = null;
    buyError.value          = null;
    selectedSvc.value       = svc;
    sidebarOpen.value       = false;
    // Country loading is triggered by the watch below (avoids double-load races)
}

// When the selected service ID changes → load countries for it
watch(
    () => selectedSvc.value?.id,
    (newId) => { if (newId) loadCountryStock(newId); },
);

function selectCat(cat) {
    activeCat.value    = cat === activeCat.value ? 'All' : cat;
    svcSearchRaw.value = ''; debouncedSvcSearch.value = '';
}

function selectCountry(country) {
    if (selectedCountry.value?.code === country.code) {
        selectedCountry.value = null;
    } else {
        selectedCountry.value = country;
        buyError.value        = null;
        nextTick(() => {
            const el = document.getElementById('ops-section');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: isSmallScreen.value ? 'start' : 'nearest' });
        });
    }
}

function changeCountry() {
    selectedCountry.value = null;
    buyError.value = null;
    nextTick(() => document.getElementById('country-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

// ── Buy ───────────────────────────────────────────────────────────────────────
async function doBuy(country, operator, cost) {
    if (!checkBalance(cost)) return;
    if (buying.value) return;
    buying.value = true;
    buyError.value = null;
    try {
        const res = await fetchTimeout('/sms/buy', {
            method: 'POST', credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ service: selectedSvc.value.id, country, operator, cost }),
        }, 35000);
        let data;
        try { data = await res.json(); } catch { throw new Error('Purchase request failed. Please retry.'); }
        if (!res.ok) throw new Error(data?.error ?? 'Purchase failed.');
        window.dispatchEvent(new Event('balance-refresh'));
        openPurchaseModal(data.order);
    } catch (e) {
        buyError.value = e.name === 'AbortError'
            ? 'Request timed out. Please retry.'
            : (e.message || 'Purchase failed.');
    } finally {
        buying.value = false;
    }
}

function buyBest() {
    if (!selectedSvc.value) return;
    // Use service price if available; otherwise derive from cheapest loaded country
    let price = selectedSvc.value.price ?? 0;
    if (!price && countryStockList.value.length > 0) {
        const prices = countryStockList.value.map(c => c.price).filter(p => p > 0);
        if (prices.length > 0) price = Math.min(...prices);
    }
    doBuy('any', 'any', price);
}

onMounted(() => { loadServices(); });
</script>

<template>
    <Head title="Buy Number — Zavelyx" />
    <AuthenticatedLayout>

        <!-- ── Fatal error boundary ─────────────────────────────────────────── -->
        <div v-if="fatalError"
            class="flex flex-col items-center justify-center py-24 gap-4 text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2)">
                <AlertCircle class="w-7 h-7 text-rose-400" :stroke-width="1.5" />
            </div>
            <div>
                <p class="text-[15px] font-bold text-slate-800 dark:text-white mb-1">Something went wrong</p>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 max-w-sm">{{ fatalError }}</p>
            </div>
            <button @click="fatalError = null; loadServices()"
                class="h-9 px-4 rounded-xl text-[13px] font-bold text-white transition-all"
                style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                Retry
            </button>
        </div>

        <template v-else>

        <!-- ── Page header ──────────────────────────────────────────────────── -->
        <div class="mb-5 flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-[20px] font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                        style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                        <Phone class="w-4 h-4 text-white" :stroke-width="2" />
                    </div>
                    Buy Virtual Number
                </h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                    Instant OTP numbers for 500+ services · Valid 20 min · Full refund if cancelled
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('sms.numbers')"
                    class="flex items-center gap-1.5 h-9 px-3 rounded-xl border text-[12px] font-bold transition-all
                        border-slate-200 dark:border-white/[0.1] text-slate-600 dark:text-slate-300 bg-white dark:bg-white/[0.04]
                        hover:border-sky-300 dark:hover:border-sky-500/30">
                    My Numbers
                </Link>
                <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden flex items-center gap-1.5 h-9 px-3 rounded-xl border text-[12px] font-bold transition-all"
                    :class="sidebarOpen
                        ? 'bg-sky-500 border-sky-500 text-white shadow-sm shadow-sky-500/30'
                        : 'border-slate-200 dark:border-white/[0.1] text-slate-600 dark:text-slate-300 bg-white dark:bg-white/[0.04]'">
                    <Search class="w-3.5 h-3.5" />
                    Services
                    <span v-if="allServices.length" class="text-[10px] opacity-75">{{ allServices.length }}</span>
                </button>
            </div>
        </div>

        <!-- ── Step indicator ───────────────────────────────────────────────── -->
        <div v-if="selectedSvc" class="mb-5 flex items-center gap-2 overflow-x-auto scrollbar-none pb-0.5">

            <!-- Step 1: Service -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white flex-shrink-0"
                    style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">1</div>
                <ServiceLogo :service="selectedSvc.id" :size="18" class="rounded-md flex-shrink-0" />
                <span class="text-[12px] font-bold text-slate-800 dark:text-white truncate max-w-[100px]">{{ selectedSvc.label }}</span>
            </div>

            <ChevronRight class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 flex-shrink-0" />

            <!-- Step 2: Country -->
            <div class="flex items-center gap-2 flex-shrink-0"
                :class="selectedCountry ? '' : 'opacity-50'">
                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black flex-shrink-0"
                    :class="selectedCountry ? 'text-white' : 'bg-slate-200 dark:bg-white/[0.1] text-slate-500'"
                    :style="selectedCountry ? 'background:linear-gradient(135deg,#10b981,#0ea5e9)' : ''">2</div>
                <span class="text-[12px] font-bold"
                    :class="selectedCountry ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                    {{ selectedCountry ? `${flag(selectedCountry.code)} ${selectedCountry.name}` : 'Pick Country' }}
                </span>
            </div>

            <ChevronRight class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 flex-shrink-0" />

            <!-- Step 3: Buy -->
            <div class="flex items-center gap-2 flex-shrink-0"
                :class="selectedCountry ? '' : 'opacity-50'">
                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black flex-shrink-0"
                    :class="selectedCountry ? 'text-white' : 'bg-slate-200 dark:bg-white/[0.1] text-slate-500'"
                    :style="selectedCountry ? 'background:linear-gradient(135deg,#f59e0b,#ef4444)' : ''">3</div>
                <span class="text-[12px] font-bold"
                    :class="selectedCountry ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                    Select Carrier
                </span>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- MAIN LAYOUT                                                        -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="flex flex-col lg:flex-row gap-4 items-start">

            <!-- ══ SERVICE SIDEBAR ═════════════════════════════════════════ -->
            <div :class="[
                    'w-full lg:w-[260px] flex-shrink-0 lg:sticky lg:top-[76px]',
                    'rounded-2xl border border-slate-200 dark:border-white/[0.07]',
                    'bg-white dark:bg-[#0c1829] lg:block',
                    sidebarOpen ? 'block' : 'hidden',
                ]">

                <!-- Header -->
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/[0.05] flex items-center justify-between">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400 dark:text-slate-400">Services</p>
                    <div class="flex items-center gap-2">
                        <span v-if="allServices.length" class="text-[10.5px] font-black text-sky-500 tabular-nums">
                            {{ allServices.length.toLocaleString() }}
                        </span>
                        <button @click="sidebarOpen = false"
                            class="lg:hidden w-5 h-5 flex items-center justify-center rounded-lg
                                text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="px-2.5 py-2 border-b border-slate-100 dark:border-white/[0.05]">
                    <div class="relative">
                        <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400 pointer-events-none" />
                        <input
                            :value="svcSearchRaw"
                            @input="e => { svcSearchRaw = e.target.value; updateSvcSearch(e.target.value); }"
                            type="text" placeholder="Search services…"
                            class="w-full h-8 pl-7 pr-2.5 text-[12px] rounded-lg
                                border border-slate-200 dark:border-white/[0.08]
                                bg-slate-50 dark:bg-white/[0.03]
                                text-slate-700 dark:text-slate-300
                                placeholder:text-slate-400 dark:placeholder:text-slate-600
                                focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                    </div>
                </div>

                <!-- Category chips -->
                <div v-if="!svcSearchRaw && availableCategories.length > 0"
                    class="px-2 py-2 border-b border-slate-100 dark:border-white/[0.05] flex flex-wrap gap-1">
                    <button v-for="cat in availableCategories" :key="cat" @click="selectCat(cat)"
                        class="px-2 py-0.5 rounded-full text-[9.5px] font-bold transition-all"
                        :class="activeCat === cat
                            ? 'bg-sky-500 text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/[0.1]'">
                        {{ cat }}
                    </button>
                </div>

                <!-- Service list -->
                <div style="overflow-y:auto;min-height:200px" :style="{ maxHeight:'calc(100vh - 280px)' }">
                    <div v-if="loadingSvcs" class="p-2 space-y-1">
                        <div v-for="i in 12" :key="i" class="h-10 rounded-xl bg-slate-100 dark:bg-white/[0.04] animate-pulse" />
                    </div>
                    <div v-else-if="svcsError && !allServices.length" class="p-4 text-center">
                        <AlertCircle class="w-6 h-6 text-rose-400 mx-auto mb-2" />
                        <p class="text-[11.5px] text-rose-500 mb-2">{{ svcsError }}</p>
                        <button @click="loadServices" class="text-[11px] font-bold text-sky-500 underline">Retry</button>
                    </div>
                    <div v-else-if="filteredServices.length === 0" class="py-8 text-center px-4">
                        <p class="text-[12px] text-slate-400 dark:text-slate-600">No services match</p>
                        <button @click="svcSearchRaw=''; debouncedSvcSearch=''; activeCat='All'"
                            class="mt-2 text-[11px] font-bold text-sky-500 underline">Show all</button>
                    </div>
                    <button v-else v-for="svc in filteredServices" :key="svc.id"
                        type="button" @click="selectService(svc)"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left transition-colors relative"
                        :class="selectedSvc?.id === svc.id
                            ? 'bg-sky-50 dark:bg-sky-500/[0.09]'
                            : 'hover:bg-slate-50 dark:hover:bg-white/[0.025]'">
                        <div v-if="selectedSvc?.id === svc.id"
                            class="absolute left-0 inset-y-0 w-[3px] rounded-r-full my-1.5"
                            style="background:linear-gradient(180deg,#38bdf8,#6366f1)" />
                        <ServiceLogo :service="svc.id" :size="26" class="rounded-[7px] flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold truncate leading-tight"
                                :class="selectedSvc?.id === svc.id ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 dark:text-slate-200'">
                                {{ svc.label }}
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600 tabular-nums">
                                <template v-if="svc.qty">{{ svc.qty.toLocaleString() }} in stock</template>
                                <template v-else-if="svcsRefreshing">loading…</template>
                                <template v-else>0 in stock</template>
                            </p>
                        </div>
                        <div v-if="selectedSvc?.id === svc.id"
                            class="w-1.5 h-1.5 rounded-full bg-sky-400 flex-shrink-0" />
                    </button>
                    <div class="h-3" />
                </div>
            </div>
            <!-- END SIDEBAR -->

            <!-- ══ RIGHT PANEL ════════════════════════════════════════════ -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Initial skeleton -->
                <div v-if="!selectedSvc && loadingSvcs"
                    class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#0c1829] p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/[0.05] animate-pulse" />
                        <div class="flex-1 space-y-2.5">
                            <div class="h-5 bg-slate-100 dark:bg-white/[0.05] rounded-full animate-pulse w-40" />
                            <div class="h-3.5 bg-slate-100 dark:bg-white/[0.05] rounded-full animate-pulse w-60" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div v-for="i in 6" :key="i" class="h-28 rounded-2xl bg-slate-100 dark:bg-white/[0.05] animate-pulse" />
                    </div>
                </div>

                <!-- No service -->
                <div v-else-if="!selectedSvc && !loadingSvcs"
                    class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-[#0c1829] p-16 text-center">
                    <Phone class="w-8 h-8 text-slate-300 dark:text-slate-700 mx-auto mb-3" :stroke-width="1.5" />
                    <p class="text-[13px] text-slate-400 dark:text-slate-600">Select a service to see available numbers.</p>
                </div>

                <template v-if="selectedSvc">

                    <!-- ── Service hero card ──────────────────────────────── -->
                    <div v-if="!mobileCarrierView" class="relative rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#0c1829] p-4 sm:p-5 overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full blur-3xl opacity-[0.07] dark:opacity-[0.12] pointer-events-none"
                            style="background:radial-gradient(circle,#0ea5e9,transparent 70%)" />
                        <div class="relative flex items-center gap-4 flex-wrap">
                            <ServiceLogo :service="selectedSvc.id" :size="52" class="rounded-xl flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <h2 class="text-[18px] font-black text-slate-800 dark:text-white leading-tight">
                                        {{ selectedSvc.label }}
                                    </h2>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest
                                        bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                                        Live
                                    </span>
                                </div>
                                <p class="text-[12.5px] text-slate-500 dark:text-slate-400">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 tabular-nums">
                                        {{ (selectedSvc.qty ?? 0).toLocaleString() }}
                                    </span> globally in stock ·
                                    from
                                    <span v-if="svcsRefreshing && !(selectedSvc.price ?? 0)"
                                        class="inline-flex items-center gap-1 text-slate-400 dark:text-slate-400 font-medium">
                                        <Loader2 class="w-3 h-3 animate-spin" /> loading…
                                    </span>
                                    <span v-else class="font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                        {{ symbol }}{{ convertAmount(selectedSvc.price ?? 0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4}) }}
                                    </span>
                                </p>
                            </div>
                            <button @click="buyBest" :disabled="buying"
                                class="flex-shrink-0 flex items-center gap-1.5 h-9 px-4 rounded-xl font-bold text-[12px] text-white
                                    transition-all active:scale-95 disabled:opacity-60 whitespace-nowrap"
                                style="background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 4px 16px rgba(16,185,129,0.3)">
                                <Loader2 v-if="buying" class="w-3.5 h-3.5 animate-spin" />
                                <Zap v-else class="w-3.5 h-3.5" :stroke-width="2.5" />
                                {{ buying ? 'Buying…' : 'Best Available' }}
                            </button>
                        </div>
                    </div>

                    <!-- Buy error banner -->
                    <div v-if="buyError && !buying"
                        class="flex items-center gap-2.5 px-4 py-3 rounded-xl
                            bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/15">
                        <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
                        <p class="text-[12.5px] text-rose-700 dark:text-rose-400 flex-1">{{ buyError }}</p>
                        <button @click="buyError = null" class="text-rose-400 hover:text-rose-600 transition-colors flex-shrink-0">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- ── Country section header ─────────────────────────── -->
                    <div v-if="!mobileCarrierView" id="country-section" class="flex items-center gap-3">
                        <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                        <span class="text-[10.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 flex items-center gap-1.5 flex-shrink-0">
                            <Globe class="w-3 h-3" />
                            <template v-if="loadingCountries">Loading countries…</template>
                            <template v-else-if="countryStockList.length">
                                {{ filteredCountries.length }} {{ filteredCountries.length === 1 ? 'country' : 'countries' }} · select to see operators
                            </template>
                            <template v-else>Choose country</template>
                        </span>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                    </div>

                    <!-- Country search -->
                    <div v-if="!mobileCarrierView && countryStockList.length > 0" class="relative">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                        <input
                            :value="countrySearchRaw"
                            @input="e => { countrySearchRaw = e.target.value; updateCountrySearch(e.target.value); }"
                            type="text" placeholder="Search country…"
                            class="w-full h-10 pl-10 pr-4 text-[13px] rounded-xl
                                border border-slate-200 dark:border-white/[0.07]
                                bg-white dark:bg-[#0c1829]
                                text-slate-700 dark:text-slate-300
                                placeholder:text-slate-400 dark:placeholder:text-slate-600
                                focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                    </div>

                    <!-- Countries loading skeleton -->
                    <div v-if="!mobileCarrierView && loadingCountries">
                        <div class="flex items-center gap-2 mb-3">
                            <Loader2 class="w-4 h-4 animate-spin text-sky-500 flex-shrink-0" />
                            <p class="text-[12px] text-slate-400 dark:text-slate-400">Checking availability across regions…</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            <div v-for="i in 9" :key="i" class="h-[100px] rounded-2xl bg-slate-100 dark:bg-white/[0.04] animate-pulse" />
                        </div>
                    </div>

                    <!-- Countries error -->
                    <div v-else-if="!mobileCarrierView && countriesError"
                        class="rounded-2xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-500/[0.05] p-5">
                        <div class="flex items-start gap-3">
                            <AlertCircle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-[12.5px] font-bold text-amber-700 dark:text-amber-400 mb-1">Country data unavailable</p>
                                <p class="text-[11.5px] text-amber-600 dark:text-amber-500 mb-3">{{ countriesError }}</p>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button @click="buyBest" :disabled="buying"
                                        class="flex items-center gap-1.5 h-8 px-3 rounded-lg font-bold text-[12px] text-white transition-all active:scale-95 disabled:opacity-60"
                                        style="background:linear-gradient(135deg,#10b981,#0ea5e9)">
                                        <Zap class="w-3.5 h-3.5" :stroke-width="2.5" />
                                        Buy Best Available
                                    </button>
                                    <button @click="loadCountryStock(selectedSvc?.id)"
                                        class="text-[11.5px] font-bold text-amber-600 dark:text-amber-400 underline">Retry</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No countries -->
                    <div v-else-if="!mobileCarrierView && !loadingCountries && countryStockList.length === 0 && !countriesError"
                        class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-[#0c1829] p-8 text-center">
                        <Globe class="w-7 h-7 text-slate-300 dark:text-slate-700 mx-auto mb-2" :stroke-width="1.5" />
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-600 mb-2">No country-specific stock found for this service</p>
                        <p class="text-[11.5px] text-slate-400 dark:text-slate-600">
                            Use <strong class="text-emerald-500">Best Available</strong> above, or
                            <button @click="loadCountryStock(selectedSvc?.id)" class="text-sky-500 underline font-bold">retry</button>.
                        </p>
                    </div>

                    <!-- No search results -->
                    <div v-else-if="!mobileCarrierView && !loadingCountries && filteredCountries.length === 0 && countrySearchRaw"
                        class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-[#0c1829] p-8 text-center">
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-600">No results for "{{ countrySearchRaw }}"</p>
                        <button @click="countrySearchRaw=''; debouncedCountrySearch=''"
                            class="mt-2 text-[11px] font-bold text-sky-500 underline">Clear search</button>
                    </div>

                    <!-- ── Country grid ────────────────────────────────────── -->
                    <div v-else-if="!mobileCarrierView && !loadingCountries && filteredCountries.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">

                        <button v-for="country in filteredCountries" :key="country.code"
                            type="button" @click="selectCountry(country)"
                            class="text-left rounded-2xl border transition-all duration-200 overflow-hidden group"
                            :class="selectedCountry?.code === country.code
                                ? 'border-sky-400 dark:border-sky-500/50 shadow-lg shadow-sky-500/10 ring-2 ring-sky-400/20'
                                : 'border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#0c1829] hover:border-sky-300 dark:hover:border-sky-500/40 hover:shadow-md hover:-translate-y-0.5'">

                            <div class="p-4"
                                :class="selectedCountry?.code === country.code
                                    ? 'bg-sky-50/80 dark:bg-sky-500/[0.07]'
                                    : 'bg-white dark:bg-[#0c1829]'">

                                <!-- Country header -->
                                <div class="flex items-start gap-3 mb-3">
                                    <span class="text-[30px] leading-none flex-shrink-0">{{ flag(country.code) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <p class="text-[13px] font-bold leading-tight truncate"
                                                :class="selectedCountry?.code === country.code
                                                    ? 'text-sky-700 dark:text-sky-300'
                                                    : 'text-slate-800 dark:text-white'">
                                                {{ country.name }}
                                            </p>
                                            <CheckCircle2 v-if="selectedCountry?.code === country.code"
                                                class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" :stroke-width="2.5" />
                                        </div>
                                        <p class="text-[10.5px] text-slate-400 dark:text-slate-400 tabular-nums">
                                            {{ (country.qty ?? 0).toLocaleString() }} in stock
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-[9px] text-slate-400 dark:text-slate-400 mb-px">from</p>
                                        <p class="text-[14px] font-black tabular-nums font-mono leading-none"
                                            :class="selectedCountry?.code === country.code
                                                ? 'text-sky-600 dark:text-sky-400'
                                                : 'text-emerald-600 dark:text-emerald-400'">
                                            {{ symbol }}{{ convertAmount(country.price).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4}) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Stock bar -->
                                <div class="h-1.5 rounded-full bg-slate-100 dark:bg-white/[0.06] overflow-hidden mb-2.5">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :style="{ width: stockBarWidth(country.qty) + '%', background: stockBarColor(country.qty) }" />
                                </div>

                                <!-- Stats row -->
                                <div class="flex items-center justify-between gap-2">
                                    <div v-if="countryRealRate(country) !== null" class="flex items-center gap-1">
                                        <span class="text-[10px] font-black tabular-nums"
                                            :class="rateColor(countryRealRate(country))">
                                            {{ countryRealRate(country) }}%
                                        </span>
                                        <span class="text-[9.5px] text-slate-400 dark:text-slate-600">success</span>
                                    </div>
                                    <div v-else class="flex-1" />
                                    <div class="flex items-center gap-1 text-[9.5px] text-slate-400 dark:text-slate-600">
                                        {{ country.operators?.length ?? 1 }}
                                        {{ (country.operators?.length ?? 1) === 1 ? 'carrier' : 'carriers' }}
                                    </div>
                                    <span class="text-[9.5px] px-1.5 py-0.5 rounded-full font-bold"
                                        :class="selectedCountry?.code === country.code
                                            ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400'
                                            : 'bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400'">
                                        {{ selectedCountry?.code === country.code ? 'Selected' : 'Select' }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- ── OPERATOR SECTION (appears after country selected) ── -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-3"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-2">

                        <div v-if="selectedCountry" id="ops-section">

                            <button v-if="isSmallScreen" type="button" @click="changeCountry"
                                class="mb-3 inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border text-[12px] font-bold
                                    border-slate-200 dark:border-white/[0.1] bg-white dark:bg-white/[0.04]
                                    text-slate-600 dark:text-slate-300 active:scale-95 transition-all">
                                <ChevronRight class="w-3.5 h-3.5 rotate-180" />
                                Change country
                            </button>

                            <!-- Section label -->
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                                <span class="text-[10.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 flex items-center gap-1.5 flex-shrink-0">
                                    {{ flag(selectedCountry.code) }} {{ selectedCountry.name }} —
                                    {{ operatorOptions.length === 1 ? 'Confirm Purchase' : `${operatorOptions.length} Carriers Available` }}
                                </span>
                                <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                            </div>

                            <!-- Operator cards -->
                            <div class="space-y-3">
                                <div v-for="op in operatorOptions" :key="op.operator"
                                    class="rounded-2xl border overflow-hidden transition-all"
                                    style="border-color:rgba(14,165,233,0.2)">

                                    <!-- Operator header -->
                                    <div class="px-5 py-3.5 flex items-center justify-between gap-4 flex-wrap"
                                        style="background:linear-gradient(135deg,rgba(14,165,233,0.06),rgba(99,102,241,0.03))">
                                        <div>
                                            <p class="text-[14px] font-black text-slate-800 dark:text-white">{{ op.name }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-px">
                                                {{ op.operator === 'any'
                                                    ? `Auto-selects the best carrier in ${selectedCountry.name}`
                                                    : `${selectedCountry.name} · ${op.name} network` }}
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold
                                            bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 flex-shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                                            {{ op.qty.toLocaleString() }} in stock
                                        </span>
                                    </div>

                                    <!-- Stats grid -->
                                    <div class="divide-x divide-slate-100 dark:divide-white/[0.05] flex"
                                        style="background:rgba(255,255,255,0.01)">

                                        <!-- Stock -->
                                        <div class="px-4 py-3.5 bg-white dark:bg-[#0c1829] flex-1">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Stock</p>
                                            <p class="text-[18px] font-black tabular-nums text-slate-800 dark:text-white font-mono leading-none">
                                                {{ op.qty.toLocaleString() }}
                                            </p>
                                            <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5">numbers</p>
                                        </div>

                                        <!-- Price -->
                                        <div class="px-4 py-3.5 bg-white dark:bg-[#0c1829] flex-1">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Price</p>
                                            <p class="text-[18px] font-black tabular-nums text-emerald-600 dark:text-emerald-400 font-mono leading-none">
                                                {{ symbol }}{{ convertAmount(op.price).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4}) }}
                                            </p>
                                            <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5">per number</p>
                                        </div>

                                        <!-- Success rate — only when API returned real data -->
                                        <div v-if="op.rate !== null" class="px-4 py-3.5 bg-white dark:bg-[#0c1829] flex-1">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Success Rate</p>
                                            <p class="text-[18px] font-black tabular-nums font-mono leading-none" :class="rateColor(op.rate)">
                                                {{ op.rate }}%
                                            </p>
                                            <div class="mt-1.5 h-1 rounded-full bg-slate-100 dark:bg-white/[0.06] overflow-hidden">
                                                <div class="h-full rounded-full" :style="{ width: op.rate + '%', background: rateBarColor(op.rate) }" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Buy button -->
                                    <div class="p-4" style="background:rgba(14,165,233,0.03)">
                                        <button
                                            @click="doBuy(selectedCountry.code, op.operator, op.price)"
                                            :disabled="buying"
                                            class="w-full flex items-center justify-center gap-2.5 font-black text-[15px] rounded-2xl
                                                text-white transition-all active:scale-[0.98] disabled:opacity-60 relative overflow-hidden"
                                            style="height:54px;background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 8px 24px rgba(16,185,129,0.3)">
                                            <div class="absolute inset-0 bg-white/10 opacity-0 hover:opacity-100 transition-opacity" />
                                            <Loader2 v-if="buying" class="w-5 h-5 animate-spin relative" />
                                            <ShoppingCart v-else class="w-5 h-5 relative" />
                                            <span class="relative">
                                                {{ buying
                                                    ? 'Processing…'
                                                    : `Buy Now — ${symbol}${convertAmount(op.price).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4})}` }}
                                            </span>
                                        </button>
                                        <p class="text-[10.5px] text-slate-400 dark:text-slate-600 text-center mt-2">
                                            Valid 20 min · Full refund if cancelled · Instant activation
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                </template>
            </div>
            <!-- END RIGHT PANEL -->
        </div>

        <!-- ══ PURCHASE SUCCESS MODAL ════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-250 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">

                <div v-if="showPurchaseModal && purchaseOrder"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="closePurchaseModal" />

                    <div class="relative w-full sm:max-w-[440px] rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden"
                        style="background:linear-gradient(145deg,#0f1f3d 0%,#0c1829 100%);border:1px solid rgba(255,255,255,0.07)">

                        <div class="absolute -top-24 -right-24 w-56 h-56 rounded-full blur-3xl opacity-[0.12] pointer-events-none"
                            style="background:radial-gradient(circle,#10b981,transparent 70%)" />
                        <div class="absolute -bottom-16 -left-16 w-40 h-40 rounded-full blur-3xl opacity-[0.07] pointer-events-none"
                            style="background:radial-gradient(circle,#6366f1,transparent 70%)" />

                        <!-- Close -->
                        <button @click="closePurchaseModal"
                            class="absolute right-4 top-4 z-10 w-8 h-8 flex items-center justify-center rounded-xl
                                text-slate-400 hover:text-white hover:bg-white/[0.08] transition-all active:scale-90">
                            <X class="w-4 h-4" />
                        </button>

                        <!-- Mobile drag handle -->
                        <div class="sm:hidden w-10 h-1 rounded-full bg-white/20 mx-auto mt-3 mb-1" />

                        <div class="relative px-5 pb-6 pt-4 overflow-y-auto max-h-[92vh] sm:max-h-[85vh]">

                            <!-- Header -->
                            <div class="flex items-center gap-3.5 mb-5 pr-6">
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden"
                                    style="background:linear-gradient(135deg,rgba(16,185,129,0.2),rgba(14,165,233,0.15));border:1px solid rgba(16,185,129,0.25)">
                                    <ServiceLogo :service="purchaseOrder.service" :size="28" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[15px] font-black text-white leading-tight">Number Purchased!</h3>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ svcLabel(purchaseOrder.service) }}
                                        <template v-if="purchaseOrder.country === 'any'"> · Best Available</template>
                                        <template v-else>
                                            · {{ flag(purchaseOrder.country) }} {{ cLabel(purchaseOrder.country) }}<template v-if="purchaseOrder.operator && purchaseOrder.operator !== 'any'"> · {{ operatorLabel(purchaseOrder.operator) }}</template>
                                        </template>
                                    </p>
                                </div>
                                <span class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9.5px] font-bold border"
                                    :class="{
                                        'bg-amber-500/20 text-amber-400 border-amber-500/25': purchaseOrder.status === 'PENDING',
                                        'bg-sky-500/20 text-sky-400 border-sky-500/25': purchaseOrder.status === 'RECEIVED',
                                        'bg-emerald-500/20 text-emerald-400 border-emerald-500/25': purchaseOrder.status === 'FINISHED',
                                        'bg-rose-500/20 text-rose-400 border-rose-500/25': isTerminal(purchaseOrder.status) && purchaseOrder.status !== 'FINISHED',
                                    }">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                        :class="{
                                            'bg-amber-400 animate-pulse': purchaseOrder.status === 'PENDING',
                                            'bg-sky-400 animate-pulse': purchaseOrder.status === 'RECEIVED',
                                            'bg-emerald-400': purchaseOrder.status === 'FINISHED',
                                            'bg-rose-400': isTerminal(purchaseOrder.status) && purchaseOrder.status !== 'FINISHED',
                                        }" />
                                    {{ purchaseOrder.status }}
                                </span>
                            </div>

                            <!-- Phone number -->
                            <div class="rounded-2xl p-4 mb-4"
                                style="background:rgba(14,165,233,0.07);border:1px solid rgba(14,165,233,0.18)">
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-sky-400 mb-2">Your Number</p>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[22px] sm:text-[26px] font-black text-white font-mono tracking-wider leading-none break-all">
                                        {{ purchaseOrder.phone_number }}
                                    </p>
                                    <button @click="copyText(purchaseOrder.phone_number, 'phone')"
                                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11.5px] font-bold transition-all active:scale-95"
                                        :class="copied === 'phone'
                                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/25'
                                            : 'bg-white/[0.06] text-slate-300 hover:bg-white/[0.1] border border-white/[0.08]'">
                                        <Check v-if="copied === 'phone'" class="w-3.5 h-3.5" :stroke-width="2.5" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        {{ copied === 'phone' ? 'Copied!' : 'Copy' }}
                                    </button>
                                </div>
                            </div>

                            <!-- OTP code (animated entrance) -->
                            <Transition
                                enter-active-class="transition-all duration-500 ease-out"
                                enter-from-class="opacity-0 scale-90"
                                enter-to-class="opacity-100 scale-100">
                                <div v-if="purchaseOrder.otp_code"
                                    class="rounded-2xl p-5 mb-4 text-center relative overflow-hidden"
                                    style="background:linear-gradient(135deg,rgba(16,185,129,0.12),rgba(16,185,129,0.06));border:1px solid rgba(16,185,129,0.3)">
                                    <div class="absolute -top-8 -right-8 w-28 h-28 rounded-full blur-2xl opacity-20 pointer-events-none"
                                        style="background:radial-gradient(circle,#10b981,transparent)" />
                                    <p class="text-[9px] font-black uppercase tracking-[0.25em] text-emerald-400 mb-2">SMS Received</p>
                                    <p class="text-[52px] font-black text-white font-mono tracking-[0.18em] leading-none mb-3 relative">
                                        {{ purchaseOrder.otp_code }}
                                    </p>
                                    <button @click="copyText(purchaseOrder.otp_code, 'otp')"
                                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-[12px] font-bold transition-all active:scale-95"
                                        :class="copied === 'otp'
                                            ? 'bg-emerald-500/25 text-emerald-300'
                                            : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'">
                                        <Check v-if="copied === 'otp'" class="w-3.5 h-3.5" :stroke-width="2.5" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        {{ copied === 'otp' ? 'Copied!' : 'Copy Code' }}
                                    </button>
                                </div>
                            </Transition>

                            <!-- Waiting for SMS -->
                            <div v-if="!purchaseOrder.otp_code && !isTerminal(purchaseOrder.status)"
                                class="rounded-2xl px-4 py-3.5 mb-4 flex items-center gap-3"
                                style="background:rgba(14,165,233,0.06);border:1px solid rgba(14,165,233,0.15)">
                                <Loader2 class="w-4 h-4 text-sky-500 animate-spin flex-shrink-0" />
                                <div>
                                    <p class="text-[12.5px] font-bold text-sky-400">Waiting for SMS…</p>
                                    <p class="text-[10.5px] text-sky-600 mt-px">Auto-checking every 5 seconds</p>
                                </div>
                            </div>

                            <!-- SMS messages log -->
                            <div v-if="purchaseOrder.sms_messages?.length" class="mb-4">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2">
                                    Messages ({{ purchaseOrder.sms_messages.length }})
                                </p>
                                <div class="space-y-2">
                                    <div v-for="msg in purchaseOrder.sms_messages" :key="msg.id"
                                        class="rounded-xl px-3.5 py-3"
                                        style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06)">
                                        <div class="flex items-center justify-between mb-1 gap-2">
                                            <span class="text-[10.5px] font-bold text-slate-400 truncate">{{ msg.sender ?? 'Unknown' }}</span>
                                            <span class="text-[9.5px] text-slate-600 flex-shrink-0">{{ new Date(msg.received_at).toLocaleTimeString() }}</span>
                                        </div>
                                        <p class="text-[12px] text-slate-300 leading-relaxed break-words">{{ msg.message }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Charge confirmation strip -->
                            <div class="rounded-2xl mb-4 overflow-hidden"
                                style="background:rgba(14,165,233,0.06);border:1px solid rgba(14,165,233,0.15)">
                                <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-sky-500 mb-0.5">Amount Charged</p>
                                        <p class="text-[22px] font-black text-white font-mono tabular-nums leading-none">
                                            {{ symbol }}{{ convertAmount(purchaseOrder.amount).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4}) }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0 space-y-1.5">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="text-[9.5px] text-slate-500">New Balance</span>
                                            <span class="text-[11px] font-black text-sky-400 tabular-nums font-mono">
                                                {{ symbol }}{{ convertAmount(walletBalance).toFixed(2) }}
                                            </span>
                                        </div>
                                        <div v-if="expiryCountdown" class="flex items-center justify-end gap-1.5">
                                            <Clock class="w-2.5 h-2.5 text-amber-400 flex-shrink-0" />
                                            <span class="text-[11px] font-black text-amber-400 tabular-nums">
                                                {{ expiryCountdown }} left
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order meta row -->
                            <div class="flex items-center gap-2 mb-5 px-1">
                                <p class="text-[10px] text-slate-600 font-mono">
                                    #{{ purchaseOrder.activation_id ?? purchaseOrder.id }}
                                </p>
                                <span class="flex-1 border-b border-dashed border-white/[0.05]" />
                                <p class="text-[10px] text-slate-500">
                                    {{ (purchaseOrder.country === 'any' || !purchaseOrder.operator || purchaseOrder.operator === 'any')
                                        ? 'Best Available'
                                        : operatorLabel(purchaseOrder.operator) }}
                                </p>
                            </div>

                            <!-- Action buttons -->
                            <div class="flex gap-2.5">
                                <!-- Lock notice when OTP received but not yet terminal -->
                                <div v-if="purchaseOrder.otp_code && !isTerminal(purchaseOrder.status)"
                                    class="flex-1 flex items-center justify-center gap-2 h-11 rounded-xl text-[12px] font-semibold
                                        bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                    <Check class="w-3.5 h-3.5 flex-shrink-0" />
                                    SMS Received · Order Locked
                                </div>
                                <!-- Cancel button: only when no OTP and not terminal -->
                                <button
                                    v-if="!isTerminal(purchaseOrder.status) && !purchaseOrder.otp_code"
                                    @click="cancelFromModal"
                                    :disabled="cancelling"
                                    class="flex-1 h-11 rounded-xl font-semibold text-[12.5px] transition-all active:scale-[0.98] disabled:opacity-50
                                        text-rose-400 hover:text-rose-300 border border-rose-500/20 hover:border-rose-500/40 hover:bg-rose-500/[0.06]">
                                    <Loader2 v-if="cancelling" class="w-3.5 h-3.5 animate-spin mx-auto" />
                                    <span v-else>Cancel & Refund</span>
                                </button>
                                <!-- Buy Another: when terminal -->
                                <button v-if="isTerminal(purchaseOrder.status)" @click="closePurchaseModal"
                                    class="flex-1 h-11 rounded-xl font-semibold text-[12.5px] text-slate-400 hover:text-white transition-all
                                        border border-white/[0.08] hover:border-white/[0.15] hover:bg-white/[0.04]">
                                    Buy Another
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══ INSUFFICIENT BALANCE MODAL ════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95">

                <div v-if="showInsufficientModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-sm"
                        @click="showInsufficientModal = false" />

                    <div class="relative w-full max-w-[380px] rounded-3xl shadow-2xl overflow-hidden"
                        style="background:linear-gradient(145deg,#0f1f3d 0%,#0c1829 100%);border:1px solid rgba(255,255,255,0.07)">

                        <div class="absolute -top-16 -right-16 w-40 h-40 rounded-full blur-3xl opacity-20 pointer-events-none"
                            style="background:radial-gradient(circle,#ef4444,transparent 70%)" />

                        <button @click="showInsufficientModal = false"
                            class="absolute right-4 top-4 z-10 w-8 h-8 flex items-center justify-center rounded-xl
                                text-slate-400 hover:text-white hover:bg-white/[0.08] transition-all">
                            <X class="w-4 h-4" />
                        </button>

                        <div class="relative p-6">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                                style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.25)">
                                <CreditCard class="w-7 h-7 text-rose-400" :stroke-width="1.5" />
                            </div>

                            <h3 class="text-[17px] font-black text-white text-center mb-2">Insufficient Balance</h3>
                            <p class="text-[12.5px] text-slate-400 text-center mb-5 leading-relaxed">
                                You need
                                <span class="text-white font-bold">
                                    {{ symbol }}{{ convertAmount(insufficientNeeded).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:4}) }}
                                </span>
                                but your balance is only
                                <span class="text-rose-400 font-bold">
                                    {{ symbol }}{{ convertAmount(walletBalance).toFixed(2) }}
                                </span>.
                            </p>

                            <div class="flex gap-3">
                                <Link :href="route('deposit.index')"
                                    class="flex-1 flex items-center justify-center gap-2 h-11 rounded-xl font-bold text-[13px] text-white
                                        transition-all active:scale-[0.98]"
                                    style="background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 4px 16px rgba(16,185,129,0.25)"
                                    @click="showInsufficientModal = false">
                                    <CreditCard class="w-4 h-4" />
                                    Deposit Funds
                                </Link>
                                <button @click="showInsufficientModal = false"
                                    class="flex-1 h-11 rounded-xl font-bold text-[13px] text-slate-400 hover:text-white transition-all
                                        border border-white/[0.08] hover:border-white/[0.15] hover:bg-white/[0.04]">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        </template><!-- end v-else error boundary -->

    </AuthenticatedLayout>
</template>

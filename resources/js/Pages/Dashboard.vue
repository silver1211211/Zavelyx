<script setup>
import OrderSuccessModal from '@/Components/OrderSuccessModal.vue';
import PlatformLogo from '@/Components/PlatformLogo.vue';
import ServiceLogo from '@/Components/ServiceLogo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowUpRight, CheckCircle2, ChevronDown, Clock,
    Loader2, Phone, Search, ShoppingBag, ShoppingCart,
    TrendingUp, Wallet, X, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    normalizeQuery, groupCategoriesByQuery, servicesInCategory, resolveSelection,
} from '@/composables/useServiceCategorySearch';

const props = defineProps({
    balance:         { type: Number, default: 0 },
    stats:           { type: Object, default: () => ({ total: 0, completed: 0, processing: 0, pending: 0 }) },
    platforms:       { type: Array,  default: () => [] },
    recentDeposits:  { type: Array,  default: () => [] },
    popularServices: { type: Array,  default: () => [] },
    activeNumbers:   { type: Array,  default: () => [] },
    recentOrders:    { type: Array,  default: () => [] },
});

const { current, symbol, convertAmount, formatMoney } = useCurrency();
const authUser = computed(() => usePage().props.auth.user);

// ── Animated balance — re-animates on currency or balance change ──────────────
const displayed = ref(0);
const target    = computed(() => convertAmount(props.balance));
let   animFrame = null;

function animateTo(toValue) {
    if (animFrame) cancelAnimationFrame(animFrame);
    const from = displayed.value;
    const diff = toValue - from;
    if (Math.abs(diff) < 0.000001) { displayed.value = toValue; return; }
    const t0  = Date.now();
    const dur = Math.min(800, 200 + Math.abs(diff) * 0.5); // faster for small changes
    const go = () => {
        const p  = Math.min((Date.now() - t0) / dur, 1);
        const e  = 1 - Math.pow(1 - p, 3); // ease-out cubic
        displayed.value = from + diff * e;
        if (p < 1) { animFrame = requestAnimationFrame(go); }
        else { displayed.value = toValue; animFrame = null; }
    };
    animFrame = requestAnimationFrame(go);
}

onMounted(() => animateTo(target.value));
watch(target, (newVal) => animateTo(newVal));
onUnmounted(() => { if (animFrame) cancelAnimationFrame(animFrame); });

const fmtBal = computed(() =>
    displayed.value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 })
);

const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
});

// ── Platform map (colors + labels) ───────────────────────────────────────────
const PLATFORM_MAP = [
    { key: 'tiktok',     label: 'TikTok',      brand: '#fe2c55', from: '#be123c', to: '#f43f5e', glow: '#f43f5e' },
    { key: 'youtube',    label: 'YouTube',     brand: '#ff0000', from: '#991b1b', to: '#ef4444', glow: '#ef4444' },
    { key: 'telegram',   label: 'Telegram',    brand: '#26a5e4', from: '#0369a1', to: '#2563eb', glow: '#38bdf8' },
    { key: 'spotify',    label: 'Spotify',     brand: '#1db954', from: '#14532d', to: '#16a34a', glow: '#4ade80' },
    { key: 'crypto',     label: 'Crypto',      brand: '#f7931a', from: '#854d0e', to: '#ca8a04', glow: '#fbbf24' },
    { key: 'google',     label: 'Google',      brand: '#4285f4', from: '#1d4ed8', to: '#4f46e5', glow: '#818cf8' },
    { key: 'instagram',  label: 'Instagram',   brand: '#e1306c', from: '#7c3aed', to: '#db2777', glow: '#a855f7' },
    { key: 'facebook',   label: 'Facebook',    brand: '#1877f2', from: '#1e40af', to: '#3b82f6', glow: '#60a5fa' },
    { key: 'twitter',    label: 'X / Twitter', brand: '#a1a1aa', from: '#27272a', to: '#52525b', glow: '#a1a1aa' },
    { key: 'x',          label: 'X / Twitter', brand: '#a1a1aa', from: '#27272a', to: '#52525b', glow: '#a1a1aa' },
    { key: 'twitch',     label: 'Twitch',      brand: '#9146ff', from: '#6d28d9', to: '#8b5cf6', glow: '#a78bfa' },
    { key: 'website',    label: 'Website',     brand: '#38bdf8', from: '#0369a1', to: '#0891b2', glow: '#38bdf8' },
    { key: 'linkedin',   label: 'LinkedIn',    brand: '#0a66c2', from: '#1d4ed8', to: '#3b82f6', glow: '#60a5fa' },
    { key: 'soundcloud', label: 'SoundCloud',  brand: '#ff5500', from: '#c2410c', to: '#f97316', glow: '#fb923c' },
    { key: 'traffic',    label: 'Traffic',     brand: '#38bdf8', from: '#0369a1', to: '#0891b2', glow: '#38bdf8' },
    { key: 'threads',    label: 'Threads',     brand: '#94a3b8', from: '#1e293b', to: '#475569', glow: '#94a3b8' },
    { key: 'discord',    label: 'Discord',     brand: '#5865f2', from: '#3730a3', to: '#6366f1', glow: '#818cf8' },
    { key: 'seo',        label: 'SEO',         brand: '#34d399', from: '#065f46', to: '#059669', glow: '#34d399' },
    { key: 'reddit',     label: 'Reddit',      brand: '#ff4500', from: '#c2410c', to: '#f97316', glow: '#fb923c' },
    { key: 'pinterest',  label: 'Pinterest',   brand: '#e60023', from: '#9f1239', to: '#e11d48', glow: '#fb7185' },
    { key: 'whatsapp',   label: 'WhatsApp',    brand: '#25d366', from: '#166534', to: '#16a34a', glow: '#4ade80' },
    { key: 'kwai',       label: 'Kwai',        brand: '#ff6b00', from: '#c2410c', to: '#f97316', glow: '#fb923c' },
    { key: 'kick',       label: 'Kick',        brand: '#53fc18', from: '#166534', to: '#22c55e', glow: '#4ade80' },
    { key: 'rutube',     label: 'Rutube',      brand: '#e11d48', from: '#9f1239', to: '#e11d48', glow: '#fb7185' },
    { key: 'rednote',    label: 'Red Note',    brand: '#ef4444', from: '#991b1b', to: '#ef4444', glow: '#f87171' },
    { key: 'jaco',       label: 'Jaco',        brand: '#8b5cf6', from: '#5b21b6', to: '#8b5cf6', glow: '#a78bfa' },
    { key: 'quora',      label: 'Quora',       brand: '#b92b27', from: '#7f1d1d', to: '#dc2626', glow: '#f87171' },
    { key: 'coinmarketcap', label: 'CoinMarketCap', brand: '#3861fb', from: '#1d4ed8', to: '#4f46e5', glow: '#818cf8' },
    { key: 'other',      label: 'Other',       brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' },
];

// Merge platform counts from backend with color info
const enrichedPlatforms = computed(() =>
    props.platforms.map(p => {
        const meta = PLATFORM_MAP.find(m => m.key === p.key)
            ?? { key: p.key, label: p.key, brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
        return { ...meta, count: p.count };
    })
);

const totalServiceCount = computed(() =>
    props.platforms.reduce((s, p) => s + p.count, 0)
);

function platformInfo(name) {
    if (typeof name !== 'string' || !name) return { key: '', brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
    const n = name.toLowerCase();
    for (const p of PLATFORM_MAP) {
        if (n.includes(p.key)) return p;
    }
    return { key: '', brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
}

// ── Order form ────────────────────────────────────────────────────────────────
const form = useForm({ service_id: null, link: '', quantity: 100 });

// ── AJAX service loading ──────────────────────────────────────────────────────
const activeServices  = ref([]);
const loadingServices = ref(false);
const loadError       = ref(null);
const platformCache   = new Map();

// ── Debounce utility ──────────────────────────────────────────────────────────
function debounce(fn, ms = 250) {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── UI state ──────────────────────────────────────────────────────────────────
const activePlatform = ref(null);
const activeCat      = ref(null);
const catOpen        = ref(false);
const svcOpen        = ref(false);
const searchRaw      = ref('');
const search         = ref('');
const selected       = ref(null);

const updateSearch = debounce(v => { search.value = v; }, 200);
const normalizedSearch = computed(() => normalizeQuery(search.value));

// ── Categories derived from loaded services ───────────────────────────────────
// Unfiltered — used to decide whether the category section renders at all.
const platformCats = computed(() => groupCategoriesByQuery(activeServices.value, ''));

// Filtered by the current search query (matches service name OR category name).
const filteredCats = computed(() => groupCategoriesByQuery(activeServices.value, normalizedSearch.value));

const categoryServices = computed(() =>
    servicesInCategory(activeServices.value, activeCat.value?.id ?? null, normalizedSearch.value)
);

function autoSelectFirst() {
    const { category, service } = resolveSelection({ services: activeServices.value, query: normalizedSearch.value });
    activeCat.value = category;
    if (service?.id) {
        selected.value  = service;
        form.service_id = service.id;
        form.quantity   = parseInt(service.min_amount ?? 100, 10);
        form.link       = '';
    } else {
        selected.value  = null;
        form.service_id = null;
    }
}

async function loadPlatformServices(platform) {
    loadError.value      = null;
    activeServices.value = [];
    if (!platform) return;
    if (platformCache.has(platform)) {
        activeServices.value = platformCache.get(platform);
        autoSelectFirst();
        return;
    }
    loadingServices.value = true;
    try {
        const res = await fetchTimeout(
            `/orders/services?platform=${encodeURIComponent(platform)}`,
            { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } },
            12000
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error('Unexpected response');
        platformCache.set(platform, data);
        activeServices.value = data;
        autoSelectFirst();
    } catch {
        loadError.value = 'Could not load services. Please try again.';
    } finally {
        loadingServices.value = false;
    }
}

function retryLoad() {
    if (activePlatform.value) {
        platformCache.delete(activePlatform.value);
        loadPlatformServices(activePlatform.value);
    }
}

function selectCategory(cat) {
    activeCat.value = cat;
    catOpen.value   = false;
    svcOpen.value   = false;
    const firstSvc = servicesInCategory(activeServices.value, cat.id, normalizedSearch.value)[0] ?? null;
    if (firstSvc) {
        selected.value  = firstSvc;
        form.service_id = firstSvc.id;
        form.quantity   = parseInt(firstSvc.min_amount ?? 100, 10);
        form.link       = '';
    } else {
        selected.value  = null;
        form.service_id = null;
    }
}

function selectService(svc) {
    selected.value  = svc;
    form.service_id = svc.id;
    form.quantity   = parseInt(svc.min_amount ?? 100, 10);
    form.link       = '';
    svcOpen.value   = false;
}

// When the query changes, keep the current category/service if they still
// match; otherwise jump to the first match, or clear selection entirely if
// nothing matches ("No services found"). This is the piece that was
// previously missing — without it, the selected category/service stayed
// stale (e.g. still showing "Spotify ..." after typing "views").
watch(normalizedSearch, (q) => {
    catOpen.value = false;
    svcOpen.value = false;

    const { category, service } = resolveSelection({
        services: activeServices.value,
        query: q,
        currentCategoryId: activeCat.value?.id ?? null,
        currentServiceId: selected.value?.id ?? null,
    });

    activeCat.value = category;
    selected.value  = service;
    form.service_id = service?.id ?? null;
    if (service) form.quantity = parseInt(service.min_amount ?? 100, 10);
});

watch(activePlatform, (platform) => {
    activeCat.value = null;
    selected.value  = null;
    searchRaw.value = '';
    search.value    = '';
    catOpen.value   = false;
    svcOpen.value   = false;
    form.service_id = null;
    form.link       = '';
    form.quantity   = 100;
    form.clearErrors();
    loadPlatformServices(platform);
});

// ── Order totals ──────────────────────────────────────────────────────────────
const orderTotal = computed(() => {
    if (!selected.value || !form.quantity) return 0;
    return ((form.quantity || 0) / 1000) * (selected.value.selling_price ?? 0);
});

const qtyValid = computed(() => {
    if (!selected.value) return true;
    const q = Number(form.quantity);
    return q >= (selected.value.min_amount ?? 1) && q <= (selected.value.max_amount ?? 1e9);
});

const linkError = computed(() => {
    if (!form.link) return null;
    return form.link.startsWith('https://') ? null : 'Must start with https://';
});

// ── Success modal ─────────────────────────────────────────────────────────────
const showSuccess      = ref(false);
const successData      = ref(null);
const _pendingSnapshot = ref(null);

function openSuccessModal(order) {
    successData.value = order;
    showSuccess.value = true;
    selected.value    = null;
    activeCat.value   = null;
    form.reset();
}

function placeAnother() {
    if (activePlatform.value && activeServices.value.length) autoSelectFirst();
}

function submit() {
    if (linkError.value) return;

    _pendingSnapshot.value = {
        service_name:      selected.value?.name ?? '',
        category_name:     selected.value?.category?.name ?? '',
        link:              form.link,
        quantity:          form.quantity,
        amount:            orderTotal.value,
        status:            'pending',
        order_id:          null,
        provider_order_id: null,
        provider_error:    null,
        remaining_balance: null,
    };

    form.post(route('orders.store'), {
        preserveScroll: true,
        preserveState:  true,
        onFlash: (flash) => {
            const order = flash?.order_placed ?? null;
            if (order) { openSuccessModal(order); _pendingSnapshot.value = null; }
        },
        onSuccess: (page) => {
            if (showSuccess.value) { _pendingSnapshot.value = null; return; }
            const flashOrder = page?.flash?.order_placed ?? null;
            if (flashOrder) { openSuccessModal(flashOrder); _pendingSnapshot.value = null; return; }
            const snap = _pendingSnapshot.value;
            if (snap) {
                const bal = page?.props?.auth?.user?.wallet?.balance ?? null;
                openSuccessModal({ ...snap, remaining_balance: bal });
                _pendingSnapshot.value = null;
            }
        },
        onError: () => { _pendingSnapshot.value = null; },
    });
}

function onKey(e) { if (e.key === 'Escape') showSuccess.value = false; }

function clearPersistedQuickOrderState() {
    const keys = [
        'nexahub-dashboard-platform',
        'nexahub-dashboard-search',
        'nexahub-quick-order-platform',
        'nexahub-quick-order-search',
        'dashboard.activePlatform',
        'dashboard.search',
        'quickOrder.activePlatform',
        'quickOrder.search',
    ];

    try {
        keys.forEach(key => localStorage.removeItem(key));
        keys.forEach(key => sessionStorage.removeItem(key));
    } catch {}

    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    const staleParams = ['platform', 'category', 'category_id', 'service', 'service_id', 'search', 'q'];
    let changed = false;
    staleParams.forEach(param => {
        if (url.searchParams.has(param)) {
            url.searchParams.delete(param);
            changed = true;
        }
    });
    if (changed) window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
}

function resetQuickOrderInitialState() {
    clearPersistedQuickOrderState();
    activeCat.value = null;
    selected.value  = null;
    searchRaw.value = '';
    search.value    = '';
    catOpen.value   = false;
    svcOpen.value   = false;
    form.service_id = null;
    form.link       = '';
    form.quantity   = 100;
    form.clearErrors();
    platformCache.clear();
    if (activePlatform.value === 'all') {
        loadPlatformServices('all');
    } else {
        activePlatform.value = 'all';
    }
}

// Dashboard always starts Quick Order from the All view. Manual platform
// selections after mount still flow through the normal activePlatform watcher.
onMounted(() => {
    resetQuickOrderInitialState();
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => document.removeEventListener('keydown', onKey));

// ── Deposit helpers ───────────────────────────────────────────────────────────
const DEPOSIT_STATUS = {
    finished:       { label: 'Completed',  dot: '#10b981', text: 'text-emerald-600 dark:text-emerald-400', bg: 'rgba(16,185,129,0.1)',  border: 'rgba(16,185,129,0.2)' },
    confirmed:      { label: 'Confirmed',  dot: '#38bdf8', text: 'text-sky-600 dark:text-sky-400',         bg: 'rgba(14,165,233,0.1)',  border: 'rgba(14,165,233,0.2)' },
    confirming:     { label: 'Confirming', dot: '#38bdf8', text: 'text-sky-600 dark:text-sky-400',         bg: 'rgba(14,165,233,0.1)',  border: 'rgba(14,165,233,0.2)' },
    sending:        { label: 'Sending',    dot: '#818cf8', text: 'text-indigo-600 dark:text-indigo-400',   bg: 'rgba(99,102,241,0.1)',  border: 'rgba(99,102,241,0.2)' },
    partially_paid: { label: 'Partial',    dot: '#f97316', text: 'text-orange-600 dark:text-orange-400',   bg: 'rgba(249,115,22,0.1)',  border: 'rgba(249,115,22,0.2)' },
    waiting:        { label: 'Waiting',    dot: '#f59e0b', text: 'text-amber-600 dark:text-amber-400',     bg: 'rgba(245,158,11,0.1)',  border: 'rgba(245,158,11,0.2)' },
    failed:         { label: 'Failed',     dot: '#ef4444', text: 'text-red-600 dark:text-red-400',         bg: 'rgba(239,68,68,0.1)',   border: 'rgba(239,68,68,0.2)' },
    expired:        { label: 'Expired',    dot: '#f43f5e', text: 'text-rose-600 dark:text-rose-400',       bg: 'rgba(244,63,94,0.1)',   border: 'rgba(244,63,94,0.2)' },
    refunded:       { label: 'Refunded',   dot: '#94a3b8', text: 'text-slate-500 dark:text-slate-400',     bg: 'rgba(148,163,184,0.1)', border: 'rgba(148,163,184,0.2)' },
};
function depositStatus(s) {
    return DEPOSIT_STATUS[s] ?? { label: s ?? '—', dot: '#94a3b8', text: 'text-slate-500', bg: 'rgba(148,163,184,0.1)', border: 'rgba(148,163,184,0.2)' };
}
function formatGateway(g) {
    const map = { nowpayments: 'NOWPayments', manual: 'Manual', stripe: 'Stripe', paypal: 'PayPal', crypto: 'Crypto' };
    return map[g?.toLowerCase?.()] ?? (g ?? 'Unknown');
}
function timeAgo(iso) {
    if (!iso) return '';
    const sec = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (sec < 60)    return sec + 's ago';
    if (sec < 3600)  return Math.floor(sec / 60) + 'm ago';
    if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const maxOrders = computed(() =>
    Math.max(1, ...props.popularServices.map(s => s.total_orders))
);

// ── SMS service showcase ──────────────────────────────────────────────────────
const SMS_SERVICES = [
    { id: 'telegram',  label: 'Telegram',  color: '#26a5e4', glow: 'rgba(38,165,228,0.25)' },
    { id: 'whatsapp',  label: 'WhatsApp',  color: '#25d366', glow: 'rgba(37,211,102,0.25)' },
    { id: 'google',    label: 'Google',    color: '#4285f4', glow: 'rgba(66,133,244,0.25)' },
    { id: 'openai',    label: 'OpenAI',    color: '#10a37f', glow: 'rgba(16,163,127,0.25)' },
    { id: 'discord',   label: 'Discord',   color: '#5865f2', glow: 'rgba(88,101,242,0.25)' },
    { id: 'tiktok',    label: 'TikTok',    color: '#fe2c55', glow: 'rgba(254,44,85,0.25)'  },
    { id: 'instagram', label: 'Instagram', color: '#e1306c', glow: 'rgba(225,48,108,0.25)' },
    { id: 'facebook',  label: 'Facebook',  color: '#1877f2', glow: 'rgba(24,119,242,0.25)' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- HERO CARD                                                    -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="relative rounded-2xl overflow-hidden mb-5 p-5 sm:p-6"
            style="background: linear-gradient(135deg, var(--hero-from) 0%, var(--hero-mid) 50%, var(--hero-to) 100%); border: 1px solid var(--hero-border)">

            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full blur-3xl pointer-events-none opacity-[0.06] dark:opacity-[0.15]"
                style="background: radial-gradient(circle, #38bdf8, transparent 70%)" />
            <div class="absolute -bottom-20 -left-20 w-60 h-60 rounded-full blur-3xl pointer-events-none opacity-[0.04] dark:opacity-[0.08]"
                style="background: radial-gradient(circle, #818cf8, transparent 70%)" />
            <div class="absolute inset-0 opacity-[0.5] dark:opacity-[0.015] pointer-events-none"
                style="background-image: linear-gradient(var(--hero-grid) 1px, transparent 1px), linear-gradient(90deg, var(--hero-grid) 1px, transparent 1px); background-size: 32px 32px" />

            <div class="relative flex flex-col sm:flex-row sm:items-center gap-5 sm:gap-6">

                <!-- Left: Greeting + stats -->
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] mb-2 select-none"
                        style="color: rgba(56,189,248,0.6)">
                        {{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) }}
                    </p>
                    <h1 class="text-[24px] sm:text-[28px] font-black tracking-tight leading-tight text-slate-800 dark:text-white mb-4">
                        {{ greeting }},&nbsp;<span
                            style="background: linear-gradient(90deg, #38bdf8 0%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text">
                            {{ authUser?.name?.split(' ')[0] }}
                        </span>
                    </h1>
                    <div class="flex flex-wrap gap-2">
                        <div class="inline-flex items-center gap-2 h-8 px-3 rounded-xl select-none"
                            style="background: var(--hero-pill-bg); border: 1px solid var(--hero-pill-border)">
                            <span class="w-[5px] h-[5px] rounded-full bg-slate-400 dark:bg-slate-500 flex-shrink-0" />
                            <span class="text-[12px] font-bold text-slate-700 dark:text-slate-200 tabular-nums">{{ stats.total }}</span>
                            <span class="text-[10.5px] text-slate-500">orders</span>
                        </div>
                        <div class="inline-flex items-center gap-2 h-8 px-3 rounded-xl select-none"
                            style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2)">
                            <span class="w-[5px] h-[5px] rounded-full bg-emerald-400 flex-shrink-0" />
                            <span class="text-[12px] font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ stats.completed }}</span>
                            <span class="text-[10.5px] text-slate-500">completed</span>
                        </div>
                        <div v-if="stats.processing + stats.pending > 0"
                            class="inline-flex items-center gap-2 h-8 px-3 rounded-xl select-none"
                            style="background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.2)">
                            <span class="w-[5px] h-[5px] rounded-full bg-sky-400 animate-pulse flex-shrink-0" />
                            <span class="text-[12px] font-bold tabular-nums text-sky-600 dark:text-sky-400">{{ stats.processing + stats.pending }}</span>
                            <span class="text-[10.5px] text-slate-500">active</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Balance card -->
                <div class="flex-shrink-0 w-full sm:w-auto sm:min-w-[210px]">
                    <div class="relative rounded-2xl overflow-hidden p-4 sm:p-5"
                        style="background: linear-gradient(135deg, rgba(14,165,233,0.18) 0%, rgba(99,102,241,0.14) 100%); border: 1px solid rgba(14,165,233,0.22)">
                        <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full blur-2xl opacity-50 pointer-events-none"
                            style="background: #0ea5e9" />
                        <div class="relative">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="w-[5px] h-[5px] rounded-full bg-emerald-400 animate-pulse flex-shrink-0" />
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] select-none"
                                    style="color: rgba(56,189,248,0.65)">Account Balance</span>
                            </div>
                            <p class="text-[30px] sm:text-[34px] font-black text-slate-800 dark:text-white tabular-nums tracking-tight leading-none">
                                {{ symbol }}{{ fmtBal }}
                            </p>
                            <p v-if="current?.code !== 'USD'" class="text-[10.5px] text-slate-500 mt-1.5">
                                ${{ balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) }} USD
                            </p>
                            <Link :href="route('deposit.index')"
                                class="mt-3 w-full flex items-center justify-center gap-1.5 h-8 px-4 rounded-xl text-[12px] font-bold text-white transition-all active:scale-95"
                                style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%); box-shadow: 0 4px 20px rgba(14,165,233,0.4)">
                                <Wallet class="w-3.5 h-3.5 flex-shrink-0" /> Add Funds
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECTION LABEL: NEW ORDER                                     -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="flex items-center gap-3 mb-4">
            <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                :style="{ color: 'var(--sec-label)' }">New Order</span>
            <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
            <Link :href="route('orders.index')"
                class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                My orders <ArrowUpRight class="w-3 h-3" />
            </Link>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- PLATFORM GRID                                                -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="mb-4">
            <div class="grid gap-1.5" style="grid-template-columns: repeat(auto-fill, minmax(68px, 1fr))">

                <!-- ALL card -->
                <button @click="activePlatform = 'all'"
                    :class="[
                        'group flex flex-col items-center justify-center gap-1 pt-3 pb-2.5 px-1 rounded-xl border transition-all duration-200 min-w-0',
                        activePlatform === 'all'
                            ? 'border-transparent shadow-lg'
                            : 'bg-white dark:bg-white/[0.04] border-slate-200/80 dark:border-white/[0.07] hover:border-slate-300 dark:hover:border-white/[0.14] hover:bg-slate-50 dark:hover:bg-white/[0.07]',
                        'hover:scale-[1.04] active:scale-[0.97]',
                    ]"
                    :style="activePlatform === 'all'
                        ? 'background:linear-gradient(145deg,#0284c7,#0ea5e9);box-shadow:0 6px 20px #0ea5e940'
                        : ''">
                    <svg class="w-5 h-5 transition-colors"
                        :class="activePlatform === 'all' ? 'text-white' : 'text-sky-500 dark:text-sky-400'"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <span class="text-[10px] font-black tracking-tight leading-none"
                        :class="activePlatform === 'all' ? 'text-white' : 'text-slate-700 dark:text-slate-300'">All</span>
                    <span class="text-[8.5px] font-mono tabular-nums px-1 py-0.5 rounded-full leading-none"
                        :class="activePlatform === 'all' ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-white/[0.08] text-slate-500 dark:text-slate-400'">
                        {{ totalServiceCount.toLocaleString() }}
                    </span>
                </button>

                <!-- Platform cards (from backend-computed prop) -->
                <button v-for="p in enrichedPlatforms" :key="p.key"
                    @click="activePlatform = p.key"
                    :class="[
                        'group flex flex-col items-center justify-center gap-1 pt-3 pb-2.5 px-1 rounded-xl border transition-all duration-200 min-w-0',
                        activePlatform === p.key
                            ? 'border-transparent shadow-lg'
                            : 'bg-white dark:bg-white/[0.04] border-slate-200/80 dark:border-white/[0.07] hover:border-slate-300 dark:hover:border-white/[0.14] hover:bg-slate-50 dark:hover:bg-white/[0.07]',
                        'hover:scale-[1.04] active:scale-[0.97]',
                    ]"
                    :style="activePlatform === p.key
                        ? `background:linear-gradient(145deg,${p.from},${p.to});box-shadow:0 6px 20px ${p.glow}40`
                        : ''">
                    <PlatformLogo
                        :platform="p.key"
                        class="w-5 h-5 flex-shrink-0 transition-colors duration-200"
                        :class="activePlatform === p.key ? 'text-white' : ''"
                        :style="activePlatform !== p.key ? `color:${p.brand}` : ''"
                    />
                    <span class="text-[10px] font-black tracking-tight truncate w-full text-center leading-none px-0.5"
                        :class="activePlatform === p.key ? 'text-white' : 'text-slate-700 dark:text-slate-300'">
                        {{ p.label }}
                    </span>
                    <span class="text-[8.5px] font-mono tabular-nums px-1 py-0.5 rounded-full leading-none"
                        :class="activePlatform === p.key ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-white/[0.08] text-slate-500 dark:text-slate-400'">
                        {{ p.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- QUICK ORDER FORM — identical structure to New Order page     -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="rounded-2xl border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05] mb-5">

            <!-- Card header -->
            <div class="px-4 sm:px-5 py-4 border-b border-slate-100 dark:border-white/[0.05]">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 dark:bg-sky-500/15 flex items-center justify-center flex-shrink-0">
                        <Zap class="w-4 h-4 text-sky-500" :stroke-width="2.5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white leading-tight">Quick Order</p>
                        <p class="text-[11.5px] text-slate-400 dark:text-slate-600 mt-0.5">
                            <template v-if="loadingServices">Loading services…</template>
                            <template v-else-if="activeServices.length">
                                {{ activeServices.length.toLocaleString() }} services · {{ platformCats.length }} categories
                            </template>
                            <template v-else-if="!activePlatform">Select a platform above</template>
                            <template v-else>{{ totalServiceCount.toLocaleString() }} services available</template>
                        </p>
                    </div>
                    <Link :href="route('orders.create')"
                        class="hidden sm:flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap flex-shrink-0">
                        Full page <ArrowUpRight class="w-3 h-3" />
                    </Link>
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-4">

                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-[17px] h-[17px] text-slate-400 dark:text-slate-400 pointer-events-none" />
                    <input
                        :value="searchRaw"
                        @input="e => { searchRaw = e.target.value; updateSearch(e.target.value); }"
                        type="search" placeholder="Search services or categories…"
                        class="w-full pl-11 pr-10 text-[13.5px] rounded-2xl border transition-all
                            bg-slate-50 dark:bg-[#0d1f35]
                            text-slate-800 dark:text-slate-100
                            placeholder:text-slate-400 dark:placeholder:text-slate-600
                            border-slate-200 dark:border-white/[0.07]
                            focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30
                            hover:border-slate-300 dark:hover:border-white/[0.12]"
                        style="height: 48px" />
                    <button v-if="searchRaw" @click="searchRaw = ''; search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-lg
                            text-slate-400 hover:text-slate-700 dark:hover:text-white
                            hover:bg-slate-100 dark:hover:bg-white/[0.08] transition-colors">
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Load error -->
                <div v-if="loadError"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl text-[12.5px]
                        bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400">
                    <AlertCircle class="w-4 h-4 flex-shrink-0" />
                    <span class="flex-1">{{ loadError }}</span>
                    <button @click="retryLoad" class="font-bold text-sky-500 hover:text-sky-700 dark:hover:text-sky-300 underline transition-colors">Retry</button>
                </div>

                <!-- Balance error -->
                <div v-if="form.errors.balance"
                    class="flex items-center gap-2.5 px-3.5 py-3 rounded-xl text-[12.5px] font-medium
                        bg-rose-50 dark:bg-rose-500/[0.1] border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400">
                    <AlertCircle class="w-4 h-4 flex-shrink-0" />
                    {{ form.errors.balance }}
                </div>

                <!-- CATEGORY ─────────────────────────────────────────────── -->
                <div v-if="loadingServices || platformCats.length">
                    <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Category</p>

                    <!-- Skeleton -->
                    <div v-if="loadingServices"
                        class="rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06] border border-slate-200 dark:border-white/[0.05]"
                        style="height: 54px" />

                    <div v-else class="relative">
                        <div v-if="catOpen" class="fixed inset-0 z-10" @click="catOpen = false" />
                        <button @click="catOpen = !catOpen"
                            class="relative z-20 w-full flex items-center gap-3 px-4 rounded-2xl border transition-all text-left"
                            :class="activeCat
                                ? 'border-sky-400/50 dark:border-sky-500/30 bg-sky-50/70 dark:bg-sky-500/[0.07]'
                                : 'border-slate-200 dark:border-white/[0.07] bg-slate-50 dark:bg-[#0d1f35] hover:border-slate-300 dark:hover:border-white/[0.12]'"
                            style="height: 54px">
                            <span class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg leading-none"
                                :style="activeCat ? `background:${platformInfo(activeCat.name).from}25` : ''">
                                <PlatformLogo v-if="activeCat" :platform="platformInfo(activeCat.name).key" class="w-4 h-4"
                                    :style="`color:${platformInfo(activeCat.name).brand}`" />
                                <span v-else class="text-[16px]">📂</span>
                            </span>
                            <span class="flex-1 text-[13px] font-semibold truncate"
                                :class="activeCat ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                                {{ activeCat?.name ?? 'Select a category…' }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-slate-400 dark:text-slate-400 flex-shrink-0 transition-transform duration-200"
                                :class="catOpen ? 'rotate-180' : ''" />
                        </button>

                        <div v-if="catOpen"
                            class="absolute left-0 right-0 top-full mt-1.5 z-30 rounded-2xl overflow-hidden
                                bg-white dark:bg-[#0b1d30] border border-slate-200 dark:border-white/[0.09]
                                shadow-2xl shadow-black/10 dark:shadow-black/60">
                            <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-white/[0.04]">
                                <button v-for="cat in filteredCats" :key="cat.id"
                                    @click="selectCategory(cat)"
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                    :class="activeCat?.id === cat.id
                                        ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300'
                                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/[0.05]'">
                                    <PlatformLogo :platform="platformInfo(cat.name).key" class="w-4 h-4 flex-shrink-0"
                                        :style="`color:${platformInfo(cat.name).brand}`" />
                                    <span class="flex-1 text-[12.5px] font-medium truncate">{{ cat.name }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-600 font-mono flex-shrink-0">{{ cat.count }}</span>
                                    <CheckCircle2 v-if="activeCat?.id === cat.id" class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" :stroke-width="2.5" />
                                </button>
                                <div v-if="!filteredCats.length"
                                    class="px-4 py-5 text-[12.5px] text-slate-400 dark:text-slate-600 text-center">
                                    No categories match
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SERVICE ──────────────────────────────────────────────── -->
                <div v-if="activeCat">
                    <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Service</p>
                    <div class="relative">
                        <div v-if="svcOpen" class="fixed inset-0 z-10" @click="svcOpen = false" />
                        <button @click="svcOpen = !svcOpen"
                            class="relative z-20 w-full flex items-center gap-3 px-4 rounded-2xl border transition-all text-left"
                            :class="selected
                                ? 'border-sky-400/50 dark:border-sky-500/30 bg-sky-50/70 dark:bg-sky-500/[0.07]'
                                : 'border-slate-200 dark:border-white/[0.07] bg-slate-50 dark:bg-[#0d1f35] hover:border-slate-300 dark:hover:border-white/[0.12]'"
                            style="height: 54px">
                            <span v-if="selected"
                                class="inline-flex items-center justify-center text-[10px] font-black text-white
                                    px-2 py-1 rounded-lg flex-shrink-0 font-mono min-w-[42px] text-center"
                                style="background:linear-gradient(135deg,#f97316,#ef4444)">
                                {{ selected.id }}
                            </span>
                            <span class="flex-1 text-[13px] font-semibold truncate"
                                :class="selected ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                                {{ selected ? `- ${selected.name}` : 'Select a service…' }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-slate-400 dark:text-slate-400 flex-shrink-0 transition-transform duration-200"
                                :class="svcOpen ? 'rotate-180' : ''" />
                        </button>

                        <div v-if="svcOpen"
                            class="absolute left-0 right-0 top-full mt-1.5 z-30 rounded-2xl overflow-hidden
                                bg-white dark:bg-[#0b1d30] border border-slate-200 dark:border-white/[0.09]
                                shadow-2xl shadow-black/10 dark:shadow-black/60">
                            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-white/[0.04]">
                                <button v-for="svc in categoryServices" :key="svc.id"
                                    @click="selectService(svc)"
                                    class="w-full flex items-center gap-2.5 px-4 py-3 text-left transition-colors"
                                    :class="selected?.id === svc.id
                                        ? 'bg-sky-50 dark:bg-sky-500/15'
                                        : 'hover:bg-slate-50 dark:hover:bg-white/[0.05]'">
                                    <span class="text-[9.5px] font-black text-white px-1.5 py-0.5 rounded-md flex-shrink-0 font-mono min-w-[32px] text-center"
                                        style="background:linear-gradient(135deg,#f97316,#ef4444)">
                                        {{ svc.id }}
                                    </span>
                                    <span class="flex-1 text-[12px] font-medium truncate leading-snug"
                                        :class="selected?.id === svc.id ? 'text-sky-700 dark:text-sky-300' : 'text-slate-700 dark:text-slate-300'">
                                        - {{ svc.name }}
                                    </span>
                                    <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400 font-mono tabular-nums flex-shrink-0">
                                        {{ symbol }}{{ convertAmount(svc.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                                    </span>
                                    <CheckCircle2 v-if="selected?.id === svc.id" class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" :stroke-width="2.5" />
                                </button>
                                <div v-if="!categoryServices.length"
                                    class="px-4 py-5 text-[12.5px] text-slate-400 dark:text-slate-600 text-center">
                                    No services in this category
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skeletons while loading -->
                <template v-if="loadingServices">
                    <div class="space-y-2.5">
                        <div class="h-3.5 w-24 rounded-lg animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                        <div class="h-12 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                    </div>
                    <div class="space-y-2.5">
                        <div class="h-3.5 w-20 rounded-lg animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                        <div class="h-12 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                    </div>
                    <div class="h-36 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                </template>

                <!-- LINK ─────────────────────────────────────────────────── -->
                <div v-if="selected">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[13px] font-bold text-slate-700 dark:text-white">Link</p>
                        <span class="text-[11px] text-slate-400 dark:text-slate-600">Must be public</span>
                    </div>
                    <input v-model="form.link" type="url" placeholder="https://…"
                        :class="[
                            'w-full px-4 text-[13.5px] rounded-2xl border transition-all',
                            'bg-slate-50 dark:bg-[#0d1f35] text-slate-800 dark:text-slate-100',
                            'placeholder:text-slate-400 dark:placeholder:text-slate-600',
                            'focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30',
                            'hover:border-slate-300 dark:hover:border-white/[0.12]',
                            (linkError || form.errors.link)
                                ? 'border-rose-400 dark:border-rose-500/60'
                                : (form.link && !linkError ? 'border-emerald-400 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/[0.07]'),
                        ]"
                        style="height: 52px" />
                    <p v-if="linkError || form.errors.link"
                        class="mt-1.5 text-[11px] text-rose-500 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3 flex-shrink-0" />
                        {{ linkError || form.errors.link }}
                    </p>
                </div>

                <!-- QUANTITY ─────────────────────────────────────────────── -->
                <div v-if="selected">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[13px] font-bold text-slate-700 dark:text-white">Quantity</p>
                        <span class="text-[11px] text-slate-400 dark:text-slate-600 font-mono tabular-nums">
                            {{ (selected.min_amount ?? 1).toLocaleString() }} – {{ (selected.max_amount ?? 1e6).toLocaleString() }}
                        </span>
                    </div>
                    <input v-model.number="form.quantity" type="number"
                        :min="selected.min_amount ?? 1"
                        :max="selected.max_amount ?? 1e6"
                        step="1"
                        :class="[
                            'w-full px-4 text-[14px] font-mono rounded-2xl border transition-all',
                            'bg-slate-50 dark:bg-[#0d1f35] text-slate-800 dark:text-slate-100',
                            'focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30',
                            'hover:border-slate-300 dark:hover:border-white/[0.12]',
                            (!qtyValid || form.errors.quantity)
                                ? 'border-rose-400 dark:border-rose-500/60'
                                : 'border-slate-200 dark:border-white/[0.07]',
                        ]"
                        style="height: 52px" />

                    <!-- Quick presets -->
                    <div class="flex gap-1.5 mt-2.5 flex-wrap">
                        <button
                            v-for="q in [100, 500, 1000, 5000, 10000].filter(q => q >= (selected.min_amount ?? 1) && q <= (selected.max_amount ?? 1e9))"
                            :key="q" type="button" @click="form.quantity = q"
                            :class="[
                                'h-6 px-2.5 rounded-lg text-[10.5px] font-semibold transition-all border',
                                form.quantity === q
                                    ? 'bg-sky-500 text-white border-transparent shadow-sm'
                                    : 'bg-slate-50 dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 hover:border-sky-300 dark:hover:border-white/[0.18] hover:text-slate-700 dark:hover:text-slate-300',
                            ]">
                            {{ q >= 1000 ? (q / 1000) + 'k' : q }}
                        </button>
                    </div>

                    <p v-if="form.errors.quantity" class="mt-1.5 text-[11px] text-rose-500 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3 flex-shrink-0" /> {{ form.errors.quantity }}
                    </p>
                    <p v-else-if="!qtyValid && form.quantity" class="mt-1.5 text-[11px] text-amber-500 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3 flex-shrink-0" />
                        Must be {{ (selected.min_amount ?? 1).toLocaleString() }}–{{ (selected.max_amount ?? 1e6).toLocaleString() }}
                    </p>
                </div>

                <!-- PRICE & DETAILS ──────────────────────────────────────── -->
                <div v-if="selected">
                    <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Price &amp; Details</p>
                    <div class="rounded-2xl border overflow-hidden bg-slate-50 dark:bg-[#0d1f35] border-slate-200 dark:border-white/[0.07]">

                        <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-white/[0.06] border-b border-slate-200 dark:border-white/[0.06]">
                            <div class="px-3 py-3">
                                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Rate / 1K</p>
                                <p class="text-[13px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                    {{ symbol }}{{ convertAmount(selected.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                                </p>
                            </div>
                            <div class="px-3 py-3">
                                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Min</p>
                                <p class="text-[13px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                    {{ (selected.min_amount ?? 1).toLocaleString() }}
                                </p>
                            </div>
                            <div class="px-3 py-3">
                                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Max</p>
                                <p class="text-[13px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                    {{ (selected.max_amount ?? 1e6).toLocaleString() }}
                                </p>
                            </div>
                        </div>

                        <div class="px-4 py-2.5 border-b border-slate-200 dark:border-white/[0.06] flex items-center gap-2 flex-wrap">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Features:</span>
                            <span v-if="selected.metadata?.refill"
                                class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/20">↻ Refill</span>
                            <span v-if="selected.metadata?.cancel"
                                class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/15 border border-sky-200 dark:border-sky-500/20">✕ Cancel</span>
                            <span v-if="selected.metadata?.dripfeed"
                                class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/15 border border-violet-200 dark:border-violet-500/20">⏱ Drip</span>
                            <span v-if="!selected.metadata?.refill && !selected.metadata?.cancel && !selected.metadata?.dripfeed"
                                class="text-[10px] text-slate-400 dark:text-slate-600">None</span>
                        </div>

                        <div class="px-4 py-4 flex items-center justify-between gap-3
                            bg-gradient-to-r from-sky-50/80 to-indigo-50/40 dark:from-sky-500/[0.07] dark:to-indigo-600/[0.04]">
                            <div class="min-w-0">
                                <p class="text-[10px] text-slate-400 dark:text-slate-400 mb-0.5">Estimated total</p>
                                <p class="text-[26px] font-black text-sky-600 dark:text-sky-400 font-mono tabular-nums leading-none">
                                    {{ symbol }}{{ convertAmount(orderTotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-[10px] text-slate-400 dark:text-slate-400 mb-0.5">for</p>
                                <p class="text-[20px] font-black text-slate-800 dark:text-white font-mono tabular-nums leading-none">{{ (form.quantity || 0).toLocaleString() }}</p>
                                <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5">units</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PLACE ORDER ──────────────────────────────────────────── -->
                <button v-if="selected"
                    type="button"
                    @click="submit"
                    :disabled="form.processing || !form.link || !!linkError || !form.quantity || !qtyValid"
                    class="w-full flex items-center justify-center gap-2.5 font-bold text-[14px] rounded-2xl
                        transition-all duration-150 active:scale-[0.98] text-white
                        shadow-lg shadow-sky-500/20 hover:shadow-sky-500/35 hover:brightness-110
                        disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:active:scale-100"
                    style="height: 56px; background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                    <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                    <Zap v-else class="w-5 h-5" :stroke-width="2.5" />
                    {{ form.processing ? 'Placing order…' : 'Place Order' }}
                </button>

            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECTION: SMS & OTP NUMBERS                                   -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="mb-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                    :style="{ color: 'var(--sec-label)' }">SMS &amp; OTP Numbers</span>
                <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
                <Link :href="route('sms.buy')"
                    class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                    Browse <ArrowUpRight class="w-3 h-3" />
                </Link>
            </div>

            <!-- Action cards row -->
            <div class="grid grid-cols-2 gap-3 mb-3">

                <!-- Buy Number card -->
                <Link :href="route('sms.buy')"
                    class="group relative rounded-2xl overflow-hidden p-4 sm:p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl active:scale-[0.99] border"
                    style="background: linear-gradient(135deg, rgba(14,165,233,0.08) 0%, rgba(99,102,241,0.06) 100%); border-color: rgba(14,165,233,0.18)">
                    <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full blur-2xl opacity-20 pointer-events-none transition-opacity group-hover:opacity-40"
                        style="background: #0ea5e9" />
                    <div class="relative">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
                            style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 6px 20px rgba(14,165,233,0.35)">
                            <Phone class="w-5 h-5 text-white" :stroke-width="2" />
                        </div>
                        <p class="text-[14px] font-black text-slate-800 dark:text-white leading-tight mb-1.5">Buy Number</p>
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 leading-relaxed mb-3 hidden sm:block">
                            Temporary virtual numbers for any platform.
                        </p>
                        <div class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-sky-500 group-hover:gap-2.5 transition-all">
                            Get Number <ArrowUpRight class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </Link>

                <!-- My Numbers card -->
                <Link :href="route('sms.numbers')"
                    class="group relative rounded-2xl overflow-hidden p-4 sm:p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl active:scale-[0.99] border"
                    style="background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(20,184,166,0.06) 100%); border-color: rgba(16,185,129,0.18)">
                    <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full blur-2xl opacity-20 pointer-events-none transition-opacity group-hover:opacity-40"
                        style="background: #10b981" />
                    <div class="relative">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
                            style="background: linear-gradient(135deg, #10b981, #14b8a6); box-shadow: 0 6px 20px rgba(16,185,129,0.35)">
                            <ShoppingBag class="w-5 h-5 text-white" :stroke-width="2" />
                        </div>
                        <p class="text-[14px] font-black text-slate-800 dark:text-white leading-tight mb-1.5">My Numbers</p>
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 leading-relaxed mb-3 hidden sm:block">
                            Manage active OTP sessions in real time.
                        </p>
                        <div class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-emerald-500 group-hover:gap-2.5 transition-all">
                            View Numbers <ArrowUpRight class="w-3.5 h-3.5" />
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Service showcase grid — 8 real service logos -->
            <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                <Link v-for="svc in SMS_SERVICES" :key="svc.id"
                    :href="route('sms.buy')"
                    class="group relative flex flex-col items-center gap-1.5 p-3 rounded-2xl border transition-all duration-200
                        hover:-translate-y-0.5 active:scale-[0.97] cursor-pointer overflow-hidden
                        bg-white dark:bg-[#0c1829]
                        border-slate-200/80 dark:border-white/[0.07]
                        hover:border-slate-300 dark:hover:border-white/[0.14]
                        hover:shadow-lg">

                    <!-- Glow blob -->
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none rounded-2xl"
                        :style="`background: radial-gradient(ellipse at 50% 0%, ${svc.glow}, transparent 70%)`" />

                    <div class="relative">
                        <ServiceLogo :service="svc.id" :size="32" class="rounded-xl flex-shrink-0" />
                    </div>
                    <span class="relative text-[9.5px] font-bold text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 transition-colors truncate max-w-full text-center">
                        {{ svc.label }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECTION: ACTIVE NUMBERS + RECENT ORDERS                     -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div v-if="activeNumbers.length || recentOrders.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

            <!-- Active SMS Numbers -->
            <div v-if="activeNumbers.length">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                        :style="{ color: 'var(--sec-label)' }">Active Numbers</span>
                    <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
                    <Link :href="route('sms.numbers')"
                        class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                        All <ArrowUpRight class="w-3 h-3" />
                    </Link>
                </div>
                <div class="rounded-2xl overflow-hidden border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05]">
                    <div v-for="(n, idx) in activeNumbers" :key="n.id"
                        class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                        :class="idx < activeNumbers.length - 1 ? 'border-b border-slate-100 dark:border-white/[0.05]' : ''">
                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center"
                            :style="n.status === 'RECEIVED'
                                ? 'background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.2)'
                                : 'background:rgba(14,165,233,0.1);border:1px solid rgba(14,165,233,0.15)'">
                            <Phone class="w-4 h-4"
                                :class="n.status === 'RECEIVED' ? 'text-emerald-500' : 'text-sky-500'" :stroke-width="2" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold text-slate-700 dark:text-slate-200 font-mono tabular-nums leading-snug">
                                {{ n.phone_number }}
                            </p>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 mt-0.5 capitalize">
                                {{ n.service }} · {{ n.country }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <span v-if="n.otp_code"
                                class="text-[11px] font-black font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/15 px-2 py-0.5 rounded-lg border border-emerald-200 dark:border-emerald-500/25 tracking-widest">
                                {{ n.otp_code }}
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="n.status === 'RECEIVED'
                                    ? 'background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)'
                                    : 'background:rgba(14,165,233,0.1);color:#0ea5e9;border:1px solid rgba(14,165,233,0.2)'">
                                <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                                    :class="n.status === 'RECEIVED' ? 'bg-emerald-400' : 'bg-sky-400'" />
                                {{ n.status === 'RECEIVED' ? 'SMS In' : 'Waiting' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div v-if="recentOrders.length">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                        :style="{ color: 'var(--sec-label)' }">Recent Orders</span>
                    <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
                    <Link :href="route('orders.index')"
                        class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                        All <ArrowUpRight class="w-3 h-3" />
                    </Link>
                </div>
                <div class="rounded-2xl overflow-hidden border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05]">
                    <div v-for="(o, idx) in recentOrders" :key="o.id"
                        class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                        :class="idx < recentOrders.length - 1 ? 'border-b border-slate-100 dark:border-white/[0.05]' : ''">
                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center"
                            :style="o.status === 'completed'
                                ? 'background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.15)'
                                : o.status === 'processing'
                                    ? 'background:rgba(14,165,233,0.1);border:1px solid rgba(14,165,233,0.15)'
                                    : o.status === 'canceled' || o.status === 'failed'
                                        ? 'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.15)'
                                        : 'background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.15)'">
                            <ShoppingCart class="w-4 h-4"
                                :class="o.status === 'completed' ? 'text-emerald-500'
                                    : o.status === 'processing' ? 'text-sky-500'
                                    : o.status === 'canceled' || o.status === 'failed' ? 'text-red-500'
                                    : 'text-amber-500'"
                                :stroke-width="2" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold text-slate-700 dark:text-slate-200 truncate leading-snug">
                                {{ o.service_name }}
                            </p>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 mt-0.5 font-mono">
                                {{ o.quantity.toLocaleString() }} units
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right space-y-0.5">
                            <p class="text-[12px] font-black tabular-nums text-slate-800 dark:text-slate-100 font-mono">
                                {{ symbol }}{{ convertAmount(o.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                            </p>
                            <span class="inline-block text-[9.5px] font-bold capitalize px-1.5 py-px rounded-full"
                                :style="o.status === 'completed'
                                    ? 'background:rgba(16,185,129,0.1);color:#10b981'
                                    : o.status === 'processing'
                                        ? 'background:rgba(14,165,233,0.1);color:#0ea5e9'
                                        : o.status === 'canceled' || o.status === 'failed'
                                            ? 'background:rgba(239,68,68,0.1);color:#ef4444'
                                            : 'background:rgba(245,158,11,0.1);color:#f59e0b'">
                                {{ o.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECTION: RECENT DEPOSITS + POPULAR SERVICES                 -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Recent Deposits -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                        :style="{ color: 'var(--sec-label)' }">Recent Deposits</span>
                    <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
                    <Link :href="route('deposit.index')"
                        class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                        Deposit <ArrowUpRight class="w-3 h-3" />
                    </Link>
                </div>

                <div v-if="recentDeposits.length" class="rounded-2xl overflow-hidden border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05]">
                    <div v-for="(d, idx) in recentDeposits" :key="d.id"
                        class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                        :class="idx < recentDeposits.length - 1 ? 'border-b border-slate-100 dark:border-white/[0.05]' : ''">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-[12px] font-black select-none"
                            :style="`background:${depositStatus(d.status).bg};border:1px solid ${depositStatus(d.status).border}`">
                            <span :class="depositStatus(d.status).text">{{ formatGateway(d.gateway).slice(0, 2).toUpperCase() }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold text-slate-700 dark:text-slate-200 leading-snug">
                                {{ formatGateway(d.gateway) }}
                                <span v-if="d.pay_currency" class="text-[10px] font-mono text-slate-400 dark:text-slate-600 ml-1 uppercase">{{ d.pay_currency }}</span>
                            </p>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 mt-0.5">{{ timeAgo(d.created_at) }}</p>
                        </div>
                        <p class="text-[13px] font-black tabular-nums flex-shrink-0 text-slate-800 dark:text-slate-100 font-mono">
                            {{ symbol }}{{ convertAmount(d.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                        </p>
                        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                            :class="depositStatus(d.status).text"
                            :style="`background: ${depositStatus(d.status).bg}; border: 1px solid ${depositStatus(d.status).border}`">
                            {{ depositStatus(d.status).label }}
                        </span>
                    </div>
                </div>

                <div v-else class="rounded-2xl border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05] px-4 py-10 text-center">
                    <div class="w-10 h-10 rounded-2xl mx-auto mb-3 flex items-center justify-center"
                        style="background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.15)">
                        <Wallet class="w-5 h-5 text-sky-500" />
                    </div>
                    <p class="text-[12.5px] font-semibold text-slate-600 dark:text-slate-400 mb-1">No deposits yet</p>
                    <Link :href="route('deposit.index')" class="text-[11.5px] font-bold text-sky-500 hover:text-sky-400 transition-colors">
                        Add funds to get started
                    </Link>
                </div>
            </div>

            <!-- Popular Services -->
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-[9.5px] font-black uppercase tracking-[0.22em] whitespace-nowrap select-none"
                        :style="{ color: 'var(--sec-label)' }">Popular Services</span>
                    <div class="flex-1 h-px" :style="{ background: 'linear-gradient(to right, var(--sec-line), transparent)' }" />
                    <Link :href="route('orders.create')"
                        class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-sky-500 hover:text-sky-400 transition-colors whitespace-nowrap">
                        Order <ArrowUpRight class="w-3 h-3" />
                    </Link>
                </div>

                <div v-if="popularServices.length" class="rounded-2xl overflow-hidden border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05]">
                    <div v-for="(svc, idx) in popularServices" :key="svc.id"
                        class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                        :class="idx < popularServices.length - 1 ? 'border-b border-slate-100 dark:border-white/[0.05]' : ''">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-[10px] font-black select-none"
                            :style="idx === 0
                                ? 'background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff'
                                : idx === 1
                                    ? 'background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff'
                                    : idx === 2
                                        ? 'background:linear-gradient(135deg,#cd7c3a,#b45309);color:#fff'
                                        : 'background:rgba(100,116,139,0.1);border:1px solid rgba(100,116,139,0.15);color:#64748b'">
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold text-slate-700 dark:text-slate-200 truncate leading-snug">{{ svc.name }}</p>
                            <div class="mt-1.5 h-1 rounded-full overflow-hidden bg-slate-100 dark:bg-white/[0.06]">
                                <div class="h-full rounded-full transition-all duration-700"
                                    :style="`width:${Math.round((svc.total_orders / maxOrders) * 100)}%;background:linear-gradient(90deg,#0ea5e9,#6366f1)`" />
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[13px] font-black tabular-nums text-slate-800 dark:text-slate-100">{{ svc.total_orders.toLocaleString() }}</p>
                            <p class="text-[9px] text-slate-400 dark:text-slate-600">orders</p>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-2xl border bg-white dark:bg-[#0c1829] border-slate-200/80 dark:border-white/[0.05] px-4 py-10 text-center">
                    <div class="w-10 h-10 rounded-2xl mx-auto mb-3 flex items-center justify-center"
                        style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.15)">
                        <TrendingUp class="w-5 h-5 text-indigo-500" />
                    </div>
                    <p class="text-[12.5px] font-semibold text-slate-600 dark:text-slate-400">No orders placed yet</p>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- MOBILE STICKY BAR                                           -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-3">
                <div v-if="selected && !showSuccess"
                    class="sm:hidden fixed bottom-0 inset-x-0 z-40
                        bg-white/96 dark:bg-[#0d1829]/96 backdrop-blur-xl
                        border-t border-slate-200 dark:border-white/[0.08]
                        shadow-[0_-8px_40px_rgba(0,0,0,0.1)] dark:shadow-[0_-8px_40px_rgba(0,0,0,0.6)]">
                    <div class="px-4 py-3 flex items-center gap-3 max-w-lg mx-auto">
                        <div class="w-9 h-9 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0"
                            :style="`background:${platformInfo(selected.category?.name).from}25`">
                            <PlatformLogo :platform="platformInfo(selected.category?.name).key" class="w-4 h-4"
                                :style="`color:${platformInfo(selected.category?.name).brand}`" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-bold text-slate-800 dark:text-white truncate leading-tight">{{ selected.name }}</p>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-400 mt-0.5">
                                {{ symbol }}{{ convertAmount(selected.selling_price).toFixed(6) }}&nbsp;/&nbsp;1,000
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5 flex-shrink-0">
                            <div class="text-right">
                                <p class="text-[14px] font-black tabular-nums leading-tight"
                                    style="background: linear-gradient(90deg, #0ea5e9, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text">
                                    {{ symbol }}{{ convertAmount(orderTotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) }}
                                </p>
                                <p class="text-[9.5px] text-slate-400 dark:text-slate-600">total</p>
                            </div>
                            <button @click="selected = null; form.service_id = null"
                                class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 transition-all active:scale-90">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SUCCESS MODAL (same component as New Order page)            -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <OrderSuccessModal
            v-model:show="showSuccess"
            :order="successData"
            @place-another="placeAnother"
        />

    </AuthenticatedLayout>
</template>

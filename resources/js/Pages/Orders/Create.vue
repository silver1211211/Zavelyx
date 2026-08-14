<script setup>
import OrderSuccessModal from '@/Components/OrderSuccessModal.vue';
import PlatformLogo from '@/Components/PlatformLogo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowLeft, CheckCircle2, ChevronDown, ChevronRight,
    Loader2, Search, X, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import {
    normalizeQuery, groupCategoriesByQuery,
    servicesInCategory, resolveSelection,
} from '@/composables/useServiceCategorySearch';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    platforms: { type: Array, default: () => [] },
});

const { symbol, convertAmount } = useCurrency();

// ── Platform map ──────────────────────────────────────────────────────────────
const PLATFORM_MAP = [
    { key: 'tiktok',     label: 'TikTok',     brand: '#fe2c55', from: '#be123c', to: '#f43f5e', glow: '#f43f5e' },
    { key: 'youtube',    label: 'YouTube',    brand: '#ff0000', from: '#991b1b', to: '#ef4444', glow: '#ef4444' },
    { key: 'telegram',   label: 'Telegram',   brand: '#26a5e4', from: '#0369a1', to: '#2563eb', glow: '#38bdf8' },
    { key: 'spotify',    label: 'Spotify',    brand: '#1db954', from: '#14532d', to: '#16a34a', glow: '#4ade80' },
    { key: 'crypto',     label: 'Crypto',     brand: '#f7931a', from: '#854d0e', to: '#ca8a04', glow: '#fbbf24' },
    { key: 'google',     label: 'Google',     brand: '#4285f4', from: '#1d4ed8', to: '#4f46e5', glow: '#818cf8' },
    { key: 'instagram',  label: 'Instagram',  brand: '#e1306c', from: '#7c3aed', to: '#db2777', glow: '#a855f7' },
    { key: 'facebook',   label: 'Facebook',   brand: '#1877f2', from: '#1e40af', to: '#3b82f6', glow: '#60a5fa' },
    { key: 'twitter',    label: 'X / Twitter', brand: '#a1a1aa', from: '#27272a', to: '#52525b', glow: '#a1a1aa' },
    { key: 'x',          label: 'X / Twitter', brand: '#a1a1aa', from: '#27272a', to: '#52525b', glow: '#a1a1aa' },
    { key: 'twitch',     label: 'Twitch',     brand: '#9146ff', from: '#6d28d9', to: '#8b5cf6', glow: '#a78bfa' },
    { key: 'website',    label: 'Website',    brand: '#38bdf8', from: '#0369a1', to: '#0891b2', glow: '#38bdf8' },
    { key: 'linkedin',   label: 'LinkedIn',   brand: '#0a66c2', from: '#1d4ed8', to: '#3b82f6', glow: '#60a5fa' },
    { key: 'soundcloud', label: 'SoundCloud', brand: '#ff5500', from: '#c2410c', to: '#f97316', glow: '#fb923c' },
    { key: 'traffic',    label: 'Traffic',    brand: '#38bdf8', from: '#0369a1', to: '#0891b2', glow: '#38bdf8' },
    { key: 'threads',    label: 'Threads',    brand: '#94a3b8', from: '#1e293b', to: '#475569', glow: '#94a3b8' },
    { key: 'discord',    label: 'Discord',    brand: '#5865f2', from: '#3730a3', to: '#6366f1', glow: '#818cf8' },
    { key: 'seo',        label: 'SEO',        brand: '#34d399', from: '#065f46', to: '#059669', glow: '#34d399' },
    { key: 'reddit',     label: 'Reddit',     brand: '#ff4500', from: '#c2410c', to: '#f97316', glow: '#fb923c' },
    { key: 'pinterest',  label: 'Pinterest',  brand: '#e60023', from: '#9f1239', to: '#e11d48', glow: '#fb7185' },
];

function platformInfo(name) {
    if (typeof name !== 'string' || !name) return { key: '', brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
    const n = name.toLowerCase();
    for (const p of PLATFORM_MAP) {
        if (n.includes(p.key)) return p;
    }
    return { key: '', brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
}

// ── Platform list derived from categories ─────────────────────────────────────
const dynamicPlatforms = computed(() => {
    if (props.platforms.length) {
        return props.platforms.map(p => {
            const meta = PLATFORM_MAP.find(m => m.key === p.key)
                ?? { key: p.key, label: p.key, brand: '#64748b', from: '#334155', to: '#475569', glow: '#94a3b8' };
            return { ...meta, count: p.count ?? 0 };
        });
    }

    const seen = new Map();
    for (const cat of props.categories) {
        if (typeof cat.name !== 'string') continue;
        const n = cat.name.toLowerCase();
        for (const p of PLATFORM_MAP) {
            if (n.includes(p.key)) {
                if (!seen.has(p.key)) seen.set(p.key, { ...p, count: cat.count ?? 0 });
                else seen.get(p.key).count += (cat.count ?? 0);
                break;
            }
        }
    }
    return Array.from(seen.values()).sort((a, b) => b.count - a.count);
});

const totalServiceCount = computed(() =>
    dynamicPlatforms.value.reduce((sum, p) => sum + p.count, 0)
);

// ── Debounce utility ──────────────────────────────────────────────────────────
function debounce(fn, ms = 250) {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── UI state ──────────────────────────────────────────────────────────────────
const activePlatform = ref(null);
const activeCat      = ref(null);
const selected       = ref(null);
const searchRaw      = ref('');
const search         = ref('');
const catOpen        = ref(false);
const svcOpen        = ref(false);

const updateSearch = debounce(v => { search.value = v; }, 200);
const normalizedSearch = computed(() => normalizeQuery(search.value));

// ── AJAX service loading ──────────────────────────────────────────────────────
const activeServices  = ref([]);
const loadingServices = ref(false);
const loadError       = ref(null);
const platformCache   = new Map();

// ── Auto-select first category + first service ────────────────────────────────
function autoSelectFirst() {
    const { category, service } = resolveSelection({ services: activeServices.value, query: normalizedSearch.value });
    activeCat.value = category;
    if (service?.id) {
        selected.value  = service;
        form.service_id = service.id;
        form.quantity   = service.min_amount ?? 100;
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

watch(activePlatform, (platform) => {
    activeCat.value   = null;
    selected.value    = null;
    searchRaw.value   = '';
    search.value      = '';
    catOpen.value     = false;
    svcOpen.value     = false;
    form.service_id   = null;
    form.link         = '';
    form.quantity     = 100;
    loadPlatformServices(platform);
});

// Safely auto-select ALL after the component is fully mounted and all
// reactive state (including `form`) is initialized.
onMounted(() => {
    if (dynamicPlatforms.value.length > 0) {
        activePlatform.value = 'all';
    }
});

// ── Categories derived from loaded services ───────────────────────────────────
// Unfiltered — used to decide whether the category section renders at all.
const platformCats = computed(() => groupCategoriesByQuery(activeServices.value, ''));

// Filtered by the current search query.
const filteredCats = computed(() => groupCategoriesByQuery(activeServices.value, normalizedSearch.value));

const categoryServices = computed(() =>
    servicesInCategory(activeServices.value, activeCat.value?.id ?? null, normalizedSearch.value)
);

// When the query changes, keep the current category/service if they still
// match; otherwise jump to the first match, or clear selection entirely if
// nothing matches ("No services found"). Prevents showing a stale selected
// category/service that no longer matches the typed query.
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
    if (service) form.quantity = service.min_amount ?? 100;
});

function selectCategory(cat) {
    activeCat.value = cat;
    catOpen.value   = false;
    svcOpen.value   = false;
    // Auto-select first service in this category
    const firstSvc = servicesInCategory(activeServices.value, cat.id, normalizedSearch.value)[0] ?? null;
    if (firstSvc) {
        selected.value  = firstSvc;
        form.service_id = firstSvc.id;
        form.quantity   = firstSvc.min_amount ?? 100;
        form.link       = '';
    } else {
        selected.value  = null;
        form.service_id = null;
    }
}

function selectService(svc) {
    selected.value    = svc;
    form.service_id   = svc.id;
    form.quantity     = svc.min_amount ?? 100;
    form.link         = '';
    svcOpen.value     = false;
}

// ── Order form ────────────────────────────────────────────────────────────────
const form = useForm({ service_id: null, link: '', quantity: 100 });

const orderTotal = computed(() => {
    if (!selected.value || !form.quantity) return 0;
    return ((form.quantity || 0) / 1000) * (selected.value.selling_price ?? 0);
});

const qtyValid = computed(() => {
    if (!selected.value) return true;
    const q = Number(form.quantity);
    return q >= (selected.value.min_amount ?? 1) && q <= (selected.value.max_amount ?? 1e9);
});

// ── Link validation ────────────────────────────────────────────────────────────
const linkError = computed(() => {
    if (!form.link) return null;
    return form.link.startsWith('https://') ? null : 'Please enter a valid HTTPS link.';
});

// ── Success modal ─────────────────────────────────────────────────────────────
const showSuccess = ref(false);
const successData = ref(null);

function openSuccessModal(order) {
    successData.value = order;
    showSuccess.value = true;
    selected.value    = null;
    activeCat.value   = null;
    form.reset();
}

function closeModal() { showSuccess.value = false; }
function placeAnother() {
    if (activePlatform.value && activeServices.value.length) autoSelectFirst();
}

// Snapshot of what the user is ordering, captured just before submit so the
// success modal can display instantly without relying on server-side flash.
const _pendingSnapshot = ref(null);

function submit() {
    if (linkError.value) return;

    _pendingSnapshot.value = {
        service_name:   selected.value?.name ?? '',
        category_name:  selected.value?.category?.name ?? '',
        link:           form.link,
        quantity:       form.quantity,
        amount:         orderTotal.value,
        status:         'pending',
        order_id:       null,
        provider_order_id: null,
        provider_error: null,
        remaining_balance: null,
    };

    form.post(route('orders.store'), {
        preserveScroll: true,
        preserveState:  true,
        onFlash: (flash) => {
            const order = flash?.order_placed ?? null;
            if (order) {
                openSuccessModal(order);
                _pendingSnapshot.value = null;
            }
        },
        onSuccess: (page) => {
            // If onFlash already opened the modal, do nothing
            if (showSuccess.value) { _pendingSnapshot.value = null; return; }

            // Try server flash data first
            const flashOrder = page?.flash?.order_placed ?? null;
            if (flashOrder) {
                openSuccessModal(flashOrder);
                _pendingSnapshot.value = null;
                return;
            }

            // Fall back to pre-captured snapshot + fresh wallet balance from props
            const snap = _pendingSnapshot.value;
            if (snap) {
                const freshBalance = page.props?.auth?.user?.wallet?.balance ?? null;
                openSuccessModal({ ...snap, remaining_balance: freshBalance });
                _pendingSnapshot.value = null;
            }
        },
        onError: () => {
            _pendingSnapshot.value = null;
        },
    });
}

</script>

<template>
    <Head title="New Order" />
    <AuthenticatedLayout>

        <!-- ── Page header ────────────────────────────────────────────────── -->
        <div class="mb-5 flex items-center gap-3">
            <Link :href="route('orders.index')"
                class="w-8 h-8 flex items-center justify-center rounded-xl flex-shrink-0
                    border border-slate-200 dark:border-white/[0.07]
                    text-slate-500 dark:text-slate-400
                    hover:border-sky-300 dark:hover:border-sky-500/30
                    hover:text-sky-600 dark:hover:text-sky-400
                    hover:bg-sky-50 dark:hover:bg-sky-500/[0.08] transition-all">
                <ArrowLeft class="w-3.5 h-3.5" />
            </Link>
            <div class="flex-1 min-w-0">
                <h1 class="text-[18px] font-black text-slate-900 dark:text-white tracking-tight leading-none">New Order</h1>
                <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5">
                    {{ totalServiceCount.toLocaleString() }} services · {{ dynamicPlatforms.length }} platforms
                </p>
            </div>
            <Link :href="route('orders.index')"
                class="hidden sm:flex items-center gap-1 text-[12px] font-semibold text-sky-600 dark:text-sky-400
                    hover:text-sky-700 dark:hover:text-sky-300 transition-colors">
                My Orders <ChevronRight class="w-3.5 h-3.5" />
            </Link>
        </div>

        <!-- ── STEP 1: Platform grid ──────────────────────────────────────── -->
        <div class="mb-5">
            <!-- Responsive auto-fill grid: ~5 per row on 360px, more on wider screens -->
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
                        : ''"
                >
                    <!-- Globe icon for ALL -->
                    <svg class="w-5 h-5 transition-colors"
                        :class="activePlatform === 'all' ? 'text-white' : 'text-sky-500 dark:text-sky-400'"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <span class="text-[10px] font-black tracking-tight leading-none"
                        :class="activePlatform === 'all' ? 'text-white' : 'text-slate-700 dark:text-slate-300'">
                        All
                    </span>
                    <span class="text-[8.5px] font-mono tabular-nums px-1 py-0.5 rounded-full leading-none"
                        :class="activePlatform === 'all' ? 'bg-white/25 text-white' : 'bg-slate-100 dark:bg-white/[0.08] text-slate-500 dark:text-slate-400'">
                        {{ totalServiceCount.toLocaleString() }}
                    </span>
                </button>

                <!-- Platform cards -->
                <button
                    v-for="p in dynamicPlatforms" :key="p.key"
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
                        : ''"
                >
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

        <!-- ── STEP 2+: Order form ────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
        >
        <div v-if="activePlatform" class="space-y-5">

            <!-- SEARCH ─────────────────────────────────────────────────────── -->
            <div class="relative">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-slate-400 dark:text-slate-400 pointer-events-none" />
                <input
                    :value="searchRaw"
                    @input="e => { searchRaw = e.target.value; updateSearch(e.target.value); }"
                    type="search"
                    placeholder="Search services or categories..."
                    class="w-full pl-11 pr-10 text-[14px] rounded-2xl border transition-all
                        bg-slate-50 dark:bg-[#0d1f35]
                        text-slate-800 dark:text-slate-100
                        placeholder:text-slate-400 dark:placeholder:text-slate-600
                        border-slate-200 dark:border-white/[0.07]
                        focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30
                        hover:border-slate-300 dark:hover:border-white/[0.12]"
                    style="height: 52px"
                />
                <button v-if="searchRaw" @click="searchRaw = ''; search = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded-lg
                        text-slate-400 hover:text-slate-700 dark:hover:text-white
                        hover:bg-slate-100 dark:hover:bg-white/[0.08] transition-colors">
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- Error state ──────────────────────────────────────────────────── -->
            <div v-if="loadError"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[12.5px]
                    bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                <span class="flex-1">{{ loadError }}</span>
                <button @click="retryLoad" class="font-bold text-sky-500 hover:text-sky-700 dark:hover:text-sky-300 underline transition-colors">
                    Retry
                </button>
            </div>

            <!-- CATEGORY ───────────────────────────────────────────────────── -->
            <div v-if="loadingServices || platformCats.length">
                <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Category</p>

                <!-- Loading skeleton -->
                <div v-if="loadingServices"
                    class="rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06] border border-slate-200 dark:border-white/[0.05]"
                    style="height: 60px" />

                <div v-else class="relative">
                    <div v-if="catOpen" class="fixed inset-0 z-10" @click="catOpen = false" />

                    <!-- Trigger -->
                    <button
                        @click="catOpen = !catOpen"
                        class="relative z-20 w-full flex items-center gap-3 px-4 rounded-2xl border transition-all text-left"
                        :class="activeCat
                            ? 'border-sky-400/50 dark:border-sky-500/30 bg-sky-50/70 dark:bg-sky-500/[0.07]'
                            : 'border-slate-200 dark:border-white/[0.07] bg-slate-50 dark:bg-[#0d1f35] hover:border-slate-300 dark:hover:border-white/[0.12]'"
                        style="height: 60px"
                    >
                        <span class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-[16px] leading-none"
                            :style="activeCat ? `background:${platformInfo(activeCat.name).from}25` : ''">
                            <PlatformLogo v-if="activeCat" :platform="platformInfo(activeCat.name).key" class="w-4 h-4"
                                :style="`color:${platformInfo(activeCat.name).brand}`" />
                            <span v-else class="text-[16px]">📂</span>
                        </span>
                        <span class="flex-1 text-[13.5px] font-semibold truncate"
                            :class="activeCat ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                            {{ activeCat?.name ?? 'Select a category...' }}
                        </span>
                        <ChevronDown class="w-4 h-4 text-slate-400 dark:text-slate-400 flex-shrink-0 transition-transform duration-200"
                            :class="catOpen ? 'rotate-180' : ''" />
                    </button>

                    <!-- Dropdown -->
                    <div v-if="catOpen"
                        class="absolute left-0 right-0 top-full mt-1.5 z-30 rounded-2xl overflow-hidden
                            bg-white dark:bg-[#0b1d30]
                            border border-slate-200 dark:border-white/[0.09]
                            shadow-2xl shadow-black/10 dark:shadow-black/60">
                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <button
                                v-for="cat in filteredCats" :key="cat.id"
                                @click="selectCategory(cat)"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors"
                                :class="activeCat?.id === cat.id
                                    ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300'
                                    : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/[0.05]'"
                            >
                                <PlatformLogo :platform="platformInfo(cat.name).key" class="w-4 h-4 flex-shrink-0"
                                    :style="`color:${platformInfo(cat.name).brand}`" />
                                <span class="flex-1 text-[13px] font-medium truncate">{{ cat.name }}</span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-600 font-mono flex-shrink-0">{{ cat.count }}</span>
                                <CheckCircle2 v-if="activeCat?.id === cat.id" class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" :stroke-width="2.5" />
                            </button>
                            <div v-if="!filteredCats.length"
                                class="px-4 py-5 text-[12.5px] text-slate-400 dark:text-slate-600 text-center">
                                No services found
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SERVICE ─────────────────────────────────────────────────────── -->
            <div v-if="activeCat">
                <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Service</p>

                <div class="relative">
                    <div v-if="svcOpen" class="fixed inset-0 z-10" @click="svcOpen = false" />

                    <!-- Trigger -->
                    <button
                        @click="svcOpen = !svcOpen"
                        class="relative z-20 w-full flex items-center gap-3 px-4 rounded-2xl border transition-all text-left"
                        :class="selected
                            ? 'border-sky-400/50 dark:border-sky-500/30 bg-sky-50/70 dark:bg-sky-500/[0.07]'
                            : 'border-slate-200 dark:border-white/[0.07] bg-slate-50 dark:bg-[#0d1f35] hover:border-slate-300 dark:hover:border-white/[0.12]'"
                        style="height: 60px"
                    >
                        <span v-if="selected"
                            class="inline-flex items-center justify-center text-[10px] font-black text-white
                                px-2 py-1 rounded-lg flex-shrink-0 font-mono min-w-[42px] text-center"
                            style="background:linear-gradient(135deg,#f97316,#ef4444)">
                            {{ selected.id }}
                        </span>
                        <span class="flex-1 text-[13.5px] font-semibold truncate"
                            :class="selected ? 'text-slate-800 dark:text-white' : 'text-slate-400 dark:text-slate-600'">
                            {{ selected ? `- ${selected.name}` : 'Select a service...' }}
                        </span>
                        <ChevronDown class="w-4 h-4 text-slate-400 dark:text-slate-400 flex-shrink-0 transition-transform duration-200"
                            :class="svcOpen ? 'rotate-180' : ''" />
                    </button>

                    <!-- Dropdown -->
                    <div v-if="svcOpen"
                        class="absolute left-0 right-0 top-full mt-1.5 z-30 rounded-2xl overflow-hidden
                            bg-white dark:bg-[#0b1d30]
                            border border-slate-200 dark:border-white/[0.09]
                            shadow-2xl shadow-black/10 dark:shadow-black/60">
                        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <button
                                v-for="svc in categoryServices" :key="svc.id"
                                @click="selectService(svc)"
                                class="w-full flex items-center gap-2.5 px-4 py-3 text-left transition-colors"
                                :class="selected?.id === svc.id
                                    ? 'bg-sky-50 dark:bg-sky-500/15'
                                    : 'hover:bg-slate-50 dark:hover:bg-white/[0.05]'"
                            >
                                <span class="text-[9.5px] font-black text-white px-1.5 py-0.5 rounded-md flex-shrink-0 font-mono min-w-[32px] text-center"
                                    style="background:linear-gradient(135deg,#f97316,#ef4444)">
                                    {{ svc.id }}
                                </span>
                                <span class="flex-1 text-[12.5px] font-medium truncate leading-snug"
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
                                {{ normalizedSearch ? 'No services found' : 'No services in this category' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading skeleton for form body ──────────────────────────────── -->
            <div v-if="normalizedSearch && !loadingServices && !activeCat"
                class="px-4 py-5 rounded-2xl text-[12.5px] text-slate-400 dark:text-slate-600 text-center
                    bg-slate-50 dark:bg-[#0d1f35]
                    border border-slate-200 dark:border-white/[0.07]">
                No services found
            </div>

            <template v-if="loadingServices">
                <div class="space-y-3">
                    <div class="h-4 w-24 rounded-lg animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                    <div class="h-14 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                </div>
                <div class="space-y-3">
                    <div class="h-4 w-20 rounded-lg animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                    <div class="h-14 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
                </div>
                <div class="h-36 rounded-2xl animate-pulse bg-slate-200 dark:bg-white/[0.06]" />
            </template>

            <!-- DESCRIPTION ─────────────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
            <div v-if="selected?.metadata?.description && selected.metadata.description.trim()">
                <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Description</p>
                <div class="px-4 py-4 rounded-2xl border
                    bg-slate-50 dark:bg-[#0d1f35]
                    border-slate-200 dark:border-white/[0.07]
                    text-[13px] text-slate-700 dark:text-slate-300 leading-[1.75] whitespace-pre-line">
                    {{ selected.metadata.description }}
                </div>
            </div>
            </Transition>

            <!-- LINK ───────────────────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
            <div v-if="selected">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[13px] font-bold text-slate-700 dark:text-white">Link</p>
                    <span class="text-[11px] text-slate-400 dark:text-slate-600">Must be public</span>
                </div>
                <input
                    v-model="form.link"
                    type="url"
                    placeholder="https://..."
                    :class="[
                        'w-full px-4 text-[13.5px] rounded-2xl border transition-all',
                        'bg-slate-50 dark:bg-[#0d1f35] text-slate-800 dark:text-slate-100',
                        'placeholder:text-slate-400 dark:placeholder:text-slate-600',
                        'focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30',
                        'hover:border-slate-300 dark:hover:border-white/[0.12]',
                        (linkError || form.errors.link)
                            ? 'border-rose-400 dark:border-rose-500/60 focus:ring-rose-500/20 focus:border-rose-400 dark:focus:border-rose-500/60'
                            : (form.link && !linkError ? 'border-emerald-400 dark:border-emerald-500/40' : 'border-slate-200 dark:border-white/[0.07]'),
                    ]"
                    style="height: 54px"
                />
                <Transition
                    enter-active-class="transition-all duration-150 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-100 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                <p v-if="linkError || form.errors.link"
                    class="mt-1.5 text-[11px] text-rose-500 dark:text-rose-400 flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 flex-shrink-0" />
                    {{ linkError || form.errors.link }}
                </p>
                </Transition>
            </div>
            </Transition>

            <!-- QUANTITY ───────────────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
            <div v-if="selected">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[13px] font-bold text-slate-700 dark:text-white">Quantity</p>
                    <span class="text-[11px] text-slate-400 dark:text-slate-600 font-mono tabular-nums">
                        {{ (selected.min_amount ?? 1).toLocaleString() }} – {{ (selected.max_amount ?? 1e6).toLocaleString() }}
                    </span>
                </div>
                <input
                    v-model.number="form.quantity"
                    type="number"
                    :min="selected.min_amount ?? 1"
                    :max="selected.max_amount ?? 1e6"
                    :class="[
                        'w-full px-4 text-[14px] font-mono rounded-2xl border transition-all',
                        'bg-slate-50 dark:bg-[#0d1f35] text-slate-800 dark:text-slate-100',
                        'focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-400 dark:focus:border-sky-500/30',
                        'hover:border-slate-300 dark:hover:border-white/[0.12]',
                        (!qtyValid || form.errors.quantity)
                            ? 'border-rose-400 dark:border-rose-500/60'
                            : 'border-slate-200 dark:border-white/[0.07]',
                    ]"
                    style="height: 54px"
                />

                <!-- Quick presets -->
                <div class="flex gap-1.5 mt-2.5 flex-wrap">
                    <button
                        v-for="q in [100,500,1000,5000,10000].filter(q => q >= (selected.min_amount??1) && q <= (selected.max_amount??1e9))"
                        :key="q" type="button" @click="form.quantity = q"
                        :class="[
                            'h-6 px-2.5 rounded-lg text-[10.5px] font-semibold transition-all border',
                            form.quantity === q
                                ? 'bg-sky-500 text-white border-transparent shadow-sm'
                                : 'bg-slate-50 dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 hover:border-sky-300 dark:hover:border-white/[0.18] hover:text-slate-700 dark:hover:text-slate-300',
                        ]"
                    >{{ q >= 1000 ? (q/1000)+'k' : q }}</button>
                </div>

                <p v-if="form.errors.quantity" class="mt-1.5 text-[11px] text-rose-500 dark:text-rose-400 flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 flex-shrink-0" /> {{ form.errors.quantity }}
                </p>
                <p v-else-if="!qtyValid && form.quantity" class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-500 flex items-center gap-1">
                    <AlertCircle class="w-3 h-3 flex-shrink-0" />
                    Must be between {{ (selected.min_amount??1).toLocaleString() }} and {{ (selected.max_amount??1e6).toLocaleString() }}
                </p>
            </div>
            </Transition>

            <!-- PRICE + DETAILS ─────────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
            <div v-if="selected">
                <p class="text-[13px] font-bold text-slate-700 dark:text-white mb-2">Price &amp; Details</p>

                <div class="rounded-2xl border overflow-hidden
                    bg-slate-50 dark:bg-[#0d1f35]
                    border-slate-200 dark:border-white/[0.07]">

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-white/[0.06]
                        border-b border-slate-200 dark:border-white/[0.06]">
                        <div class="px-3 py-3.5">
                            <p class="text-[9.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Rate / 1K</p>
                            <p class="text-[14px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                {{ symbol }}{{ convertAmount(selected.selling_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                            </p>
                        </div>
                        <div class="px-3 py-3.5">
                            <p class="text-[9.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Min</p>
                            <p class="text-[14px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                {{ (selected.min_amount ?? 1).toLocaleString() }}
                            </p>
                        </div>
                        <div class="px-3 py-3.5">
                            <p class="text-[9.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Max</p>
                            <p class="text-[14px] font-black text-slate-800 dark:text-white font-mono tabular-nums">
                                {{ (selected.max_amount ?? 1e6).toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <!-- Features row -->
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-white/[0.06] flex items-center gap-2 flex-wrap">
                        <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Features:</span>
                        <span v-if="selected.metadata?.refill"
                            class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/20">↻ Refill</span>
                        <span v-if="selected.metadata?.cancel"
                            class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/15 border border-sky-200 dark:border-sky-500/20">✕ Cancel</span>
                        <span v-if="selected.metadata?.dripfeed"
                            class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/15 border border-violet-200 dark:border-violet-500/20">⏱ Drip</span>
                        <span v-if="!selected.metadata?.refill && !selected.metadata?.cancel && !selected.metadata?.dripfeed"
                            class="text-[10px] text-slate-400 dark:text-slate-600">None</span>
                    </div>

                    <!-- Total row -->
                    <div class="px-4 py-4 flex items-center justify-between gap-3
                        bg-gradient-to-r from-sky-50/80 to-indigo-50/40 dark:from-sky-500/[0.07] dark:to-indigo-600/[0.04]">
                        <div class="min-w-0">
                            <p class="text-[10px] text-slate-400 dark:text-slate-400 mb-0.5">Estimated total</p>
                            <p class="text-[28px] sm:text-[32px] font-black text-sky-600 dark:text-sky-400 font-mono tabular-nums leading-none">
                                {{ symbol }}{{ convertAmount(orderTotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] text-slate-400 dark:text-slate-400 mb-0.5">for</p>
                            <p class="text-[20px] sm:text-[24px] font-black text-slate-800 dark:text-white font-mono tabular-nums leading-none">
                                {{ (form.quantity || 0).toLocaleString() }}
                            </p>
                            <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5">units</p>
                        </div>
                    </div>
                </div>
            </div>
            </Transition>

            <!-- Balance error ───────────────────────────────────────────────── -->
            <div v-if="form.errors.balance"
                class="flex items-center gap-2.5 px-4 py-3.5 rounded-2xl text-[12.5px] font-medium
                    bg-rose-50 dark:bg-rose-500/[0.1] border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                {{ form.errors.balance }}
            </div>

            <!-- PLACE ORDER ─────────────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
            <button v-if="selected"
                type="button"
                @click="submit"
                :disabled="form.processing || !form.link || !!linkError || !form.quantity || !qtyValid"
                class="w-full flex items-center justify-center gap-2.5 font-bold text-[15px] rounded-2xl
                    transition-all duration-150 active:scale-[0.98] text-white
                    shadow-lg shadow-sky-500/20 hover:shadow-sky-500/35 hover:brightness-110
                    disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:active:scale-100"
                style="height: 58px; background:linear-gradient(135deg,#0ea5e9,#6366f1)"
            >
                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                <Zap v-else class="w-5 h-5" :stroke-width="2.5" />
                {{ form.processing ? 'Placing order…' : 'Place Order' }}
            </button>
            </Transition>

            <!-- Bottom spacer for mobile sticky bar -->
            <div v-if="selected" class="h-1 sm:h-0" />

        </div>
        </Transition>

        <!-- ── Mobile sticky bar ──────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="selected && !showSuccess"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50
                        bg-white/95 dark:bg-[#0c1829]/95 backdrop-blur-xl
                        border-t border-slate-200 dark:border-white/[0.08]
                        shadow-[0_-8px_32px_rgba(0,0,0,0.12)] dark:shadow-[0_-8px_32px_rgba(0,0,0,0.5)]
                        safe-area-bottom">
                    <div class="px-4 py-3 flex items-center gap-3 max-w-xl mx-auto">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :style="`background:linear-gradient(135deg,${platformInfo(selected.category?.name).from},${platformInfo(selected.category?.name).to})`">
                            <PlatformLogo :platform="platformInfo(selected.category?.name).key" class="w-4 h-4 text-white" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-bold text-slate-800 dark:text-white truncate leading-none mb-0.5">{{ selected.name }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600">Scroll up to complete</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[14px] font-black text-sky-600 dark:text-sky-400 tabular-nums leading-none">
                                {{ symbol }}{{ convertAmount(orderTotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                            </p>
                            <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5">total</p>
                        </div>
                        <button @click="selected = null; form.service_id = null"
                            class="w-7 h-7 flex items-center justify-center rounded-xl flex-shrink-0
                                text-slate-400 hover:text-slate-700 dark:hover:text-white
                                hover:bg-slate-100 dark:hover:bg-white/[0.1] transition-colors">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Order confirmation modal ──────────────────────────────────── -->
        <OrderSuccessModal
            v-model:show="showSuccess"
            :order="successData"
            @place-another="placeAnother"
        />

    </AuthenticatedLayout>
</template>

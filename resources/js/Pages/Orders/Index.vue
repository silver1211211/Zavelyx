<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowUpRight, CheckCircle2, ChevronLeft, ChevronRight,
    Clock, ExternalLink, Loader2, Plus, Search,
    ShoppingCart, TrendingUp, X, XCircle, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    orders: { type: Object, default: () => ({ data: [], links: [], meta: {}, current_page: 1 }) },
});

const { symbol, formatAmount } = useCurrency();

// ── Status config ──────────────────────────────────────────────────────────────
const SC = {
    pending:    { label: 'Pending',    icon: Clock,        dot: 'bg-amber-400',   badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',   bar: 'bg-amber-400',    ring: 'ring-amber-200 dark:ring-amber-500/30' },
    processing: { label: 'Processing', icon: Loader2,      dot: 'bg-sky-400',     badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',         bar: 'bg-sky-500',      ring: 'ring-sky-200 dark:ring-sky-500/30',    spin: true },
    completed:  { label: 'Completed',  icon: CheckCircle2, dot: 'bg-emerald-400', badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', bar: 'bg-emerald-500', ring: 'ring-emerald-200 dark:ring-emerald-500/30' },
    partial:    { label: 'Partial',    icon: AlertCircle,  dot: 'bg-orange-400',  badge: 'bg-orange-500/10 text-orange-600 dark:text-orange-400', bar: 'bg-orange-400',   ring: 'ring-orange-200 dark:ring-orange-500/30' },
    canceled:   { label: 'Canceled',   icon: XCircle,      dot: 'bg-rose-400',    badge: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',       bar: 'bg-rose-400',     ring: 'ring-rose-200 dark:ring-rose-500/30' },
    failed:     { label: 'Failed',     icon: XCircle,      dot: 'bg-red-500',     badge: 'bg-red-500/10 text-red-600 dark:text-red-400',          bar: 'bg-red-400',      ring: 'ring-red-200 dark:ring-red-500/30' },
};
const sc = (s) => SC[s] ?? { label: s, icon: Clock, dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400', bar: 'bg-slate-300', ring: '' };

// ── Silent live polling every 5 seconds ───────────────────────────────────────
const polling = ref(false);
let   pollTimer = null;

function doPoll() {
    if (document.visibilityState !== 'visible' || polling.value) return;
    polling.value = true;
    router.reload({
        only: ['orders'],
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { polling.value = false; },
    });
}

function onVisibility() { if (document.visibilityState === 'visible') doPoll(); }

onMounted(() => {
    pollTimer = setInterval(doPoll, 5000);
    document.addEventListener('visibilitychange', onVisibility);
});
onUnmounted(() => {
    clearInterval(pollTimer);
    document.removeEventListener('visibilitychange', onVisibility);
});

// ── Debounce utility ──────────────────────────────────────────────────────────
function debounce(fn, ms = 250) {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── Filter / search ───────────────────────────────────────────────────────────
const TABS = [
    { key: '',           label: 'All' },
    { key: 'pending',    label: 'Pending' },
    { key: 'processing', label: 'Processing' },
    { key: 'completed',  label: 'Completed' },
    { key: 'partial',    label: 'Partial' },
    { key: 'canceled',   label: 'Canceled' },
    { key: 'failed',     label: 'Failed' },
];
const activeTab      = ref('');
const searchRaw      = ref('');
const searchQuery    = ref('');

const updateSearch = debounce(v => { searchQuery.value = v; }, 200);

const allOrders = computed(() => props.orders?.data ?? []);

const statusCounts = computed(() => {
    const c = {};
    allOrders.value.forEach(o => { c[o.status] = (c[o.status] ?? 0) + 1; });
    return c;
});

const filteredOrders = computed(() => {
    let list = allOrders.value;
    if (activeTab.value) list = list.filter(o => o.status === activeTab.value);
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(o =>
            o.service?.name?.toLowerCase().includes(q) ||
            o.link?.toLowerCase().includes(q) ||
            String(o.id).includes(q)
        );
    }
    return list;
});

// ── Progress helpers ──────────────────────────────────────────────────────────
const delivered = (o) => Math.max(0, (o.quantity ?? 0) - (o.remains ?? 0));
const progress  = (o) => {
    const qty = o.quantity ?? 0;
    if (qty <= 0) return 0;
    return Math.min(100, Math.round((delivered(o) / qty) * 100));
};

// ── Status change animations ──────────────────────────────────────────────────
const changedStatus = ref({});

// ── Details slide panel ───────────────────────────────────────────────────────
const selected = ref(null);
const openModal  = (o) => { selected.value = o; };
const closeModal = () => { selected.value = null; };

watch(() => props.orders?.data, (newOrders, oldOrders) => {
    if (!newOrders) return;
    // Live-update the open panel with fresh data
    if (selected.value) {
        const fresh = newOrders.find(o => o.id === selected.value.id);
        if (fresh) selected.value = fresh;
    }
    // Detect status changes for row flash animation + balance refresh
    if (oldOrders) {
        const prev = {};
        oldOrders.forEach(o => { prev[o.id] = o.status; });
        let anyChange = false;
        newOrders.forEach(o => {
            if (prev[o.id] !== undefined && prev[o.id] !== o.status) {
                changedStatus.value[o.id] = true;
                setTimeout(() => { delete changedStatus.value[o.id]; }, 1800);
                anyChange = true;
            }
        });
        // Trigger balance refresh if any order changed (refund may have been issued)
        if (anyChange) {
            window.dispatchEvent(new CustomEvent('balance-refresh'));
        }
    }
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtDate(iso, time = false) {
    if (!iso) return '—';
    const opts = { month: 'short', day: 'numeric', year: 'numeric' };
    if (time) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
    return new Date(iso).toLocaleDateString('en-US', opts);
}

function timeAgo(iso) {
    if (!iso) return null;
    const s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60)    return `${s}s ago`;
    if (s < 3600)  return `${Math.floor(s / 60)}m ago`;
    if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
    return fmtDate(iso);
}

const money = (n) => symbol.value + formatAmount(n ?? 0);
</script>

<template>
    <Head title="My Orders" />
    <AuthenticatedLayout>

        <!-- ── Header ─────────────────────────────────────────────────────── -->
        <div class="mb-5 flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">My Orders</h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ allOrders.length }} orders on this page
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <Link :href="route('orders.create')"
                    class="flex items-center gap-1.5 h-9 px-4 rounded-xl text-[13px] font-bold text-white
                        shadow-lg shadow-sky-500/30 active:scale-95 transition-all"
                    style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%)">
                    <Plus class="w-3.5 h-3.5" />New Order
                </Link>
            </div>
        </div>

        <!-- ── Main card ──────────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/10 overflow-hidden">

            <!-- Status tabs + search row -->
            <div class="px-4 pt-4 pb-3 border-b border-slate-100 dark:border-white/[0.05] space-y-3">

                <!-- Status tabs (scrollable on mobile) -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-px scrollbar-none">
                    <button v-for="tab in TABS" :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'flex-shrink-0 flex items-center gap-1.5 h-7 px-3 rounded-lg text-[12px] font-semibold transition-all border',
                            activeTab === tab.key
                                ? 'bg-sky-500 border-sky-500 text-white shadow-sm shadow-sky-500/30'
                                : 'border-slate-200 dark:border-white/[0.07] text-slate-600 dark:text-slate-400 hover:border-sky-300 dark:hover:border-sky-500/30 bg-white dark:bg-transparent',
                        ]">
                        {{ tab.label }}
                        <span v-if="tab.key && statusCounts[tab.key]"
                            :class="['text-[10px] font-black px-1 py-px rounded', activeTab === tab.key ? 'bg-white/25' : 'bg-slate-100 dark:bg-white/[0.06]']">
                            {{ statusCounts[tab.key] }}
                        </span>
                    </button>
                </div>

                <!-- Search -->
                <div class="relative max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                    <input :value="searchRaw" @input="e => { searchRaw = e.target.value; updateSearch(e.target.value); }" type="search" placeholder="Search by service, link or ID…"
                        class="w-full h-9 pl-9 pr-4 text-[13px] rounded-xl border
                            bg-slate-50 dark:bg-white/[0.04]
                            border-slate-200 dark:border-white/[0.07]
                            text-slate-700 dark:text-slate-300
                            placeholder:text-slate-400 dark:placeholder:text-slate-600
                            focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400
                            transition-all" />
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredOrders.length === 0"
                class="py-16 flex flex-col items-center gap-3 text-center px-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/[0.05] flex items-center justify-center">
                    <ShoppingCart class="w-6 h-6 text-slate-400 dark:text-slate-600" />
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-slate-700 dark:text-slate-300">
                        {{ searchRaw ? 'No matching orders' : activeTab ? `No ${activeTab} orders` : 'No orders yet' }}
                    </p>
                    <p class="text-[12px] text-slate-400 dark:text-slate-600 mt-0.5">
                        {{ searchRaw ? 'Try a different search.' : 'Place your first order to get started.' }}
                    </p>
                </div>
                <Link v-if="!searchRaw && !activeTab" :href="route('orders.create')"
                    class="mt-1 flex items-center gap-1.5 h-9 px-4 rounded-xl text-[13px] font-bold text-white
                        shadow-lg shadow-sky-500/30 active:scale-95 transition-all"
                    style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%)">
                    <Zap class="w-3.5 h-3.5" /> Place Order
                </Link>
            </div>

            <!-- ── Desktop table ──────────────────────────────────────────── -->
            <div v-else class="hidden sm:block overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/60 dark:bg-white/[0.01]">
                            <th class="text-left px-5 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Order</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Link</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Qty</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Charge</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Status</th>
                            <th class="text-left px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600 whitespace-nowrap">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                        <tr v-for="o in filteredOrders" :key="o.id"
                            @click="openModal(o)"
                            :class="[
                                'hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors cursor-pointer group',
                                changedStatus[o.id] ? 'status-flash' : '',
                            ]">

                            <!-- Service + ID -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                        :style="{ background: 'linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08))', border: '1px solid rgba(14,165,233,0.15)' }">
                                        <TrendingUp class="w-3.5 h-3.5 text-sky-500" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 max-w-[180px] truncate leading-tight">
                                            {{ o.service?.name ?? 'SMM Service' }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-600 font-mono mt-px">#{{ o.id }}</p>
                                        <!-- Inline progress for processing orders -->
                                        <div v-if="o.status === 'processing' && o.quantity > 0" class="mt-1.5 w-36">
                                            <div class="h-1 rounded-full bg-slate-100 dark:bg-white/[0.06] overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    :class="progress(o) > 0 ? sc(o.status).bar : 'bg-sky-400 animate-pulse'"
                                                    :style="{ width: Math.max(progress(o), 4) + '%' }" />
                                            </div>
                                            <p class="text-[9.5px] text-slate-400 dark:text-slate-600 mt-0.5 tabular-nums">
                                                {{ delivered(o).toLocaleString() }} / {{ o.quantity.toLocaleString() }} delivered
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Link -->
                            <td class="px-4 py-3.5 max-w-[160px]">
                                <span v-if="o.link"
                                    class="flex items-center gap-1 text-sky-500 group-hover:text-sky-600 dark:group-hover:text-sky-400 truncate text-[12px]"
                                    :title="o.link">
                                    <span class="truncate">{{ o.link.replace(/^https?:\/\//, '') }}</span>
                                    <ExternalLink class="w-3 h-3 flex-shrink-0 opacity-60" />
                                </span>
                                <span v-else class="text-slate-300 dark:text-slate-700">—</span>
                            </td>

                            <!-- Qty -->
                            <td class="px-4 py-3.5 font-mono text-[13px] text-slate-700 dark:text-slate-300">
                                {{ (o.quantity ?? 0).toLocaleString() }}
                            </td>

                            <!-- Charge -->
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ money(o.amount) }}</p>
                                <p v-if="o.refund_status === 'completed' && o.refund_amount > 0"
                                    class="text-[10.5px] text-emerald-600 dark:text-emerald-400 font-semibold mt-px">
                                    ↩ {{ money(o.refund_amount) }} refunded
                                </p>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5">
                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold', sc(o.status).badge]">
                                    <component :is="sc(o.status).icon"
                                        :class="['w-3 h-3', sc(o.status).spin && o.status === 'processing' ? 'animate-spin' : '']" />
                                    {{ sc(o.status).label }}
                                </span>
                            </td>

                            <!-- Date -->
                            <td class="px-4 py-3.5 text-[12px] text-slate-400 dark:text-slate-600 whitespace-nowrap">
                                {{ fmtDate(o.created_at) }}
                            </td>

                            <!-- Arrow -->
                            <td class="px-4 py-3.5">
                                <ArrowUpRight class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700 group-hover:text-sky-500 transition-colors" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Mobile cards ───────────────────────────────────────────── -->
            <div v-if="filteredOrders.length > 0" class="sm:hidden divide-y divide-slate-100 dark:divide-white/[0.05]">
                <div v-for="o in filteredOrders" :key="o.id"
                    @click="openModal(o)"
                    :class="[
                        'px-4 py-4 hover:bg-slate-50/80 dark:hover:bg-white/[0.02] active:bg-slate-100 dark:active:bg-white/[0.04] transition-colors cursor-pointer',
                        changedStatus[o.id] ? 'status-flash' : '',
                    ]">

                    <!-- Top row -->
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center"
                                style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.15)">
                                <TrendingUp class="w-4 h-4 text-sky-500" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 dark:text-slate-200 text-[13.5px] truncate leading-tight">
                                    {{ o.service?.name ?? 'SMM Service' }}
                                </p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-600 font-mono">#{{ o.id }} · {{ fmtDate(o.created_at) }}</p>
                            </div>
                        </div>
                        <span :class="['flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold', sc(o.status).badge]">
                            <component :is="sc(o.status).icon"
                                :class="['w-2.5 h-2.5', o.status === 'processing' ? 'animate-spin' : '']" />
                            {{ sc(o.status).label }}
                        </span>
                    </div>

                    <!-- Detail row -->
                    <div class="flex items-center gap-4 text-[12px] text-slate-500 dark:text-slate-400">
                        <span class="tabular-nums">{{ (o.quantity ?? 0).toLocaleString() }} units</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300 tabular-nums">{{ money(o.amount) }}</span>
                        <span v-if="o.link" class="truncate text-sky-500 flex items-center gap-0.5 min-w-0">
                            <span class="truncate">{{ o.link.replace(/^https?:\/\//, '') }}</span>
                            <ExternalLink class="w-3 h-3 flex-shrink-0" />
                        </span>
                    </div>

                    <!-- Progress bar (processing) -->
                    <div v-if="o.status === 'processing' && o.quantity > 0" class="mt-2.5">
                        <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-600 mb-1">
                            <span>{{ delivered(o).toLocaleString() }} delivered</span>
                            <span>{{ o.remains?.toLocaleString() ?? 0 }} remaining</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 dark:bg-white/[0.06] overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="progress(o) > 0 ? 'bg-sky-500' : 'bg-sky-400 animate-pulse'"
                                :style="{ width: Math.max(progress(o), 4) + '%' }" />
                        </div>
                    </div>

                    <!-- Refund badge -->
                    <div v-if="o.refund_status === 'completed' && o.refund_amount > 0" class="mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-bold
                            bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400
                            border border-emerald-200/60 dark:border-emerald-500/20">
                            ↩ Refunded {{ money(o.refund_amount) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="(orders?.links?.length ?? 0) > 3"
                class="flex items-center justify-between px-5 py-4 border-t border-slate-100 dark:border-white/[0.05]">
                <p class="text-[12px] text-slate-400 dark:text-slate-600">
                    Showing {{ orders.meta?.from ?? 0 }}–{{ orders.meta?.to ?? 0 }} of {{ orders.meta?.total ?? 0 }}
                </p>
                <div class="flex items-center gap-1">
                    <Link v-for="link in orders.links" :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 rounded-lg text-[12px] font-semibold transition-all',
                            link.active
                                ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30'
                                : link.url
                                    ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/[0.06]'
                                    : 'text-slate-300 dark:text-slate-700 cursor-default pointer-events-none',
                        ]"
                        preserve-scroll
                        v-html="link.label.replace('&laquo;','‹').replace('&raquo;','›')"
                    />
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- ORDER DETAILS SLIDE PANEL                                        -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="selected"
                    class="fixed inset-0 z-50 bg-black/60 backdrop-blur-[2px]"
                    @click="closeModal" />
            </Transition>

            <!-- Slide panel -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-250 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full">
                <div v-if="selected"
                    class="fixed inset-y-0 right-0 z-50 w-full sm:max-w-[440px] flex flex-col
                        shadow-[0_0_80px_rgba(0,0,0,0.4)] dark:shadow-[0_0_80px_rgba(0,0,0,0.8)]"
                    :style="{ background: 'var(--panel-bg)', borderLeft: '1px solid var(--panel-border)' }">

                    <!-- Panel header -->
                    <div class="flex items-center justify-between px-5 py-4 flex-shrink-0"
                        :style="{ borderBottom: '1px solid var(--panel-header-border)', background: 'var(--panel-header-bg)' }">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.15)">
                                <TrendingUp class="w-4 h-4 text-sky-500 dark:text-sky-400" />
                            </div>
                            <div>
                                <h3 class="text-[14px] font-black text-slate-900 dark:text-white leading-tight">Order #{{ selected.id }}</h3>
                                <p class="text-[11px] text-slate-500 mt-px">{{ fmtDate(selected.created_at, true) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition-all duration-500', sc(selected.status).badge]">
                                <component :is="sc(selected.status).icon"
                                    :class="['w-3 h-3', selected.status === 'processing' ? 'animate-spin' : '']" />
                                {{ sc(selected.status).label }}
                            </span>
                            <button @click="closeModal"
                                class="w-8 h-8 flex items-center justify-center rounded-xl
                                    text-slate-500 hover:text-slate-800 dark:hover:text-slate-200
                                    active:scale-90 transition-all"
                                :style="{ border: '1px solid var(--panel-btn-border)', background: 'var(--panel-btn-bg)' }">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable body -->
                    <div class="overflow-y-auto flex-1">

                        <!-- Service name + link -->
                        <div class="px-5 pt-5 pb-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-600 mb-1.5">Service</p>
                            <p class="text-[14px] font-bold text-slate-900 dark:text-white leading-snug">{{ selected.service?.name ?? 'SMM Service' }}</p>
                            <a v-if="selected.link" :href="selected.link" target="_blank" rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-[12px] text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 mt-1.5 break-all"
                                @click.stop>
                                <span class="truncate max-w-[320px]">{{ selected.link }}</span>
                                <ExternalLink class="w-3 h-3 flex-shrink-0 opacity-70" />
                            </a>
                        </div>

                        <!-- Progress section (processing/partial) -->
                        <div v-if="['processing', 'partial'].includes(selected.status) && selected.quantity > 0"
                            class="mx-5 mb-4 p-4 rounded-xl"
                            :style="{ background: 'var(--panel-section-bg)', border: '1px solid var(--panel-section-border)' }">
                            <div class="flex items-center justify-between mb-2.5">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Delivery Progress</p>
                                <p class="text-[13px] font-black tabular-nums text-sky-600 dark:text-sky-400">{{ progress(selected) }}%</p>
                            </div>
                            <div class="h-2.5 rounded-full overflow-hidden mb-4"
                                :style="{ background: 'var(--panel-track-bg)' }">
                                <div class="h-full rounded-full transition-all duration-700"
                                    :class="[sc(selected.status).bar, selected.status === 'processing' && progress(selected) === 0 ? 'animate-pulse' : '']"
                                    :style="{ width: Math.max(progress(selected), 2) + '%' }" />
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="p-2.5 rounded-xl"
                                    :style="{ background: 'var(--panel-section-bg)', border: '1px solid var(--panel-section-border)' }">
                                    <p class="text-[18px] font-black text-slate-900 dark:text-white tabular-nums">{{ selected.start_count?.toLocaleString() ?? 0 }}</p>
                                    <p class="text-[9.5px] text-slate-500 mt-px uppercase tracking-wide">Start</p>
                                </div>
                                <div class="p-2.5 rounded-xl"
                                    :style="{ background: 'var(--panel-section-bg)', border: '1px solid var(--panel-section-border)' }">
                                    <p class="text-[18px] font-black text-sky-600 dark:text-sky-400 tabular-nums">{{ delivered(selected).toLocaleString() }}</p>
                                    <p class="text-[9.5px] text-slate-500 mt-px uppercase tracking-wide">Done</p>
                                </div>
                                <div class="p-2.5 rounded-xl"
                                    :style="{ background: 'var(--panel-section-bg)', border: '1px solid var(--panel-section-border)' }">
                                    <p class="text-[18px] font-black text-rose-600 dark:text-rose-400 tabular-nums">{{ (selected.remains ?? 0).toLocaleString() }}</p>
                                    <p class="text-[9.5px] text-slate-500 mt-px uppercase tracking-wide">Left</p>
                                </div>
                            </div>
                        </div>

                        <!-- Completed: delivery summary -->
                        <div v-if="selected.status === 'completed'"
                            class="mx-5 mb-4 p-4 rounded-xl"
                            style="background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.2)">
                            <div class="flex items-center gap-2 mb-1.5">
                                <CheckCircle2 class="w-4 h-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0" />
                                <p class="text-[13px] font-bold text-emerald-700 dark:text-emerald-300">Order Completed</p>
                            </div>
                            <p class="text-[12px] text-emerald-600 dark:text-emerald-400">
                                {{ selected.quantity?.toLocaleString() }} units delivered successfully.
                            </p>
                        </div>

                        <!-- Partial refund section -->
                        <div v-if="selected.status === 'partial' && selected.refund_status === 'completed'"
                            class="mx-5 mb-4 p-4 rounded-xl"
                            style="background: rgba(249,115,22,0.07); border: 1px solid rgba(249,115,22,0.2)">
                            <p class="text-[12px] font-bold text-orange-700 dark:text-orange-300 mb-2">Partial Refund Issued</p>
                            <div class="space-y-1.5 text-[12px] text-orange-600 dark:text-orange-400">
                                <div class="flex justify-between">
                                    <span>Delivered:</span>
                                    <span class="font-bold text-orange-700 dark:text-orange-300">{{ delivered(selected).toLocaleString() }} units</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Undelivered:</span>
                                    <span class="font-bold text-orange-700 dark:text-orange-300">{{ (selected.remains ?? 0).toLocaleString() }} units</span>
                                </div>
                                <div class="flex justify-between pt-1.5" style="border-top: 1px solid rgba(249,115,22,0.2)">
                                    <span>Refunded:</span>
                                    <span class="font-black text-emerald-600 dark:text-emerald-400">{{ money(selected.refund_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Canceled / Failed refund section -->
                        <div v-if="['canceled','failed'].includes(selected.status) && selected.refund_status === 'completed'"
                            class="mx-5 mb-4 p-4 rounded-xl"
                            style="background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.2)">
                            <div class="flex items-center gap-2 mb-1">
                                <CheckCircle2 class="w-4 h-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0" />
                                <p class="text-[12px] font-bold text-emerald-700 dark:text-emerald-300">
                                    Full refund issued — {{ money(selected.refund_amount) }}
                                </p>
                            </div>
                            <p class="text-[11.5px] text-emerald-600 dark:text-emerald-400">
                                {{ selected.refunded_at ? 'Refunded ' + timeAgo(selected.refunded_at) : 'Refund completed.' }}
                            </p>
                        </div>

                        <!-- Refund pending -->
                        <div v-if="['canceled','failed','partial'].includes(selected.status) && !selected.refund_status"
                            class="mx-5 mb-4 p-3 rounded-xl flex items-center gap-2"
                            style="background: rgba(245,158,11,0.07); border: 1px solid rgba(245,158,11,0.2)">
                            <Clock class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400 flex-shrink-0 animate-pulse" />
                            <p class="text-[12px] font-semibold text-amber-700 dark:text-amber-300">Refund processing — will be credited shortly.</p>
                        </div>

                        <!-- Details grid -->
                        <div class="px-5 pb-6">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-600 mb-3">Order Details</p>
                            <div class="rounded-xl overflow-hidden"
                                :style="{ border: '1px solid var(--panel-section-border)' }">
                                <div v-for="(row, i) in [
                                    { label: 'Order ID',       value: '#' + selected.id },
                                    { label: 'Quantity',       value: (selected.quantity ?? 0).toLocaleString() + ' units' },
                                    { label: 'Charged',        value: money(selected.amount) },
                                    { label: 'Provider',       value: selected.provider_name ?? '—' },
                                    { label: 'Provider Order', value: selected.provider_order_id ? '#' + selected.provider_order_id : '—' },
                                    { label: 'Placed',         value: fmtDate(selected.created_at, true) },
                                    { label: 'Updated',        value: selected.processed_at ? fmtDate(selected.processed_at, true) : '—' },
                                ]" :key="row.label"
                                    class="flex items-center justify-between px-4 py-3"
                                    :style="i > 0 ? 'border-top: 1px solid var(--panel-row-border)' : ''">
                                    <span class="text-[12px] text-slate-500">{{ row.label }}</span>
                                    <span class="text-[12px] font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ row.value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-4 flex gap-2.5 flex-shrink-0"
                        :style="{ borderTop: '1px solid var(--panel-header-border)', background: 'var(--panel-footer-bg)' }">
                        <button @click="closeModal"
                            class="flex-1 h-10 rounded-xl text-[13px] font-bold text-slate-500 dark:text-slate-400
                                hover:text-slate-800 dark:hover:text-slate-200 active:scale-[0.98] transition-all"
                            :style="{ border: '1px solid var(--panel-btn-border)', background: 'var(--panel-btn-bg)' }">
                            Close
                        </button>
                        <Link :href="route('orders.create')"
                            class="flex-1 flex items-center justify-center gap-1.5 h-10 rounded-xl text-[13px] font-bold text-white
                                shadow-sm shadow-sky-500/30 active:scale-[0.98] transition-all"
                            style="background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%)">
                            <Plus class="w-3.5 h-3.5" /> New Order
                        </Link>
                    </div>

                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes statusFlash {
    0%   { background: rgba(14,165,233,0.18); }
    60%  { background: rgba(14,165,233,0.08); }
    100% { background: transparent; }
}
.status-flash {
    animation: statusFlash 1.8s ease-out forwards;
}
</style>

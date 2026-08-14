<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowUpRight,
    BarChart2,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    CreditCard,
    Download,
    ExternalLink,
    Eye,
    Filter,
    Loader2,
    RefreshCw,
    Search,
    ThumbsDown,
    ThumbsUp,
    TrendingUp,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    invoices:          { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    counts:            { type: Object, default: () => ({}) },
    filters:           { type: Object, default: () => ({}) },
    analytics:         { type: Object, default: () => ({}) },
    availableGateways: { type: Array,  default: () => [] },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Status tabs ───────────────────────────────────────────────────────────────
const STATUS_TABS = [
    { key: '',          label: 'All',       count: 'all' },
    { key: 'pending',   label: 'Pending',   count: 'pending' },
    { key: 'completed', label: 'Completed', count: 'completed' },
    { key: 'failed',    label: 'Failed',    count: 'failed' },
];

const search       = ref(props.filters.search    ?? '');
const activeStatus = ref(props.filters.status    ?? '');
const gateway      = ref(props.filters.gateway   ?? '');
const currency     = ref(props.filters.currency  ?? '');
const dateFrom     = ref(props.filters.dateFrom  ?? '');
const dateTo       = ref(props.filters.dateTo    ?? '');
const showFilters  = ref(!!(gateway.value || currency.value || dateFrom.value || dateTo.value));

function applyFilters() {
    router.get(route('admin.payments.index'), {
        status:    activeStatus.value,
        search:    search.value,
        gateway:   gateway.value,
        currency:  currency.value,
        date_from: dateFrom.value,
        date_to:   dateTo.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function setStatus(s) {
    activeStatus.value = s;
    applyFilters();
}

function clearFilters() {
    gateway.value  = '';
    currency.value = '';
    dateFrom.value = '';
    dateTo.value   = '';
    applyFilters();
}

function exportCsv() {
    const params = new URLSearchParams({
        status:    activeStatus.value,
        search:    search.value,
        gateway:   gateway.value,
        currency:  currency.value,
        date_from: dateFrom.value,
        date_to:   dateTo.value,
    }).toString();
    window.location.href = route('admin.payments.export') + '?' + params;
}

// ── Status styles ─────────────────────────────────────────────────────────────
const STATUS_STYLE = {
    waiting:        { color: 'text-amber-600 dark:text-amber-400',   bg: 'bg-amber-50 dark:bg-amber-500/10',   dot: 'bg-amber-400' },
    confirming:     { color: 'text-sky-600 dark:text-sky-400',       bg: 'bg-sky-50 dark:bg-sky-500/10',       dot: 'bg-sky-400' },
    confirmed:      { color: 'text-sky-600 dark:text-sky-400',       bg: 'bg-sky-50 dark:bg-sky-500/10',       dot: 'bg-sky-400' },
    sending:        { color: 'text-violet-600 dark:text-violet-400', bg: 'bg-violet-50 dark:bg-violet-500/10', dot: 'bg-violet-400' },
    partially_paid: { color: 'text-orange-600 dark:text-orange-400', bg: 'bg-orange-50 dark:bg-orange-500/10', dot: 'bg-orange-400' },
    finished:       { color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-500/10', dot: 'bg-emerald-400' },
    failed:         { color: 'text-rose-600 dark:text-rose-400',     bg: 'bg-rose-50 dark:bg-rose-500/10',     dot: 'bg-rose-400' },
    expired:        { color: 'text-slate-500 dark:text-slate-400',   bg: 'bg-slate-50 dark:bg-white/4',        dot: 'bg-slate-400' },
    refunded:       { color: 'text-slate-500 dark:text-slate-400',   bg: 'bg-slate-50 dark:bg-white/4',        dot: 'bg-slate-400' },
};
function ss(s) {
    return STATUS_STYLE[s] ?? { color: 'text-slate-500', bg: 'bg-slate-50 dark:bg-white/4', dot: 'bg-slate-400' };
}

function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function fmtSec(sec) {
    if (!sec || sec <= 0) return '—';
    if (sec < 60)  return Math.round(sec) + 's';
    if (sec < 3600) return Math.round(sec / 60) + 'm';
    return (sec / 3600).toFixed(1) + 'h';
}

// ── Actions ───────────────────────────────────────────────────────────────────
const processing = ref({});

function invoiceAction(routeName, invoice) {
    processing.value[invoice.id] = routeName;
    router.post(route(routeName, invoice.id), {}, {
        onFinish: () => { delete processing.value[invoice.id]; },
        preserveScroll: true,
    });
}

function isProcessing(id, routeName) {
    return processing.value[id] === routeName;
}

// ── Pagination ─────────────────────────────────────────────────────────────────
const meta  = computed(() => props.invoices.meta  ?? {});
const links = computed(() => props.invoices.links ?? []);

// ── Analytics ─────────────────────────────────────────────────────────────────
const a = computed(() => props.analytics ?? {});
const showAnalytics = ref(false);
</script>

<template>
    <Head title="Payment Logs — Admin" />
    <AdminLayout>

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Finance</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Deposit Management</h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">All deposits, statuses, and manual actions</p>
            </div>
            <div class="sm:ml-auto flex items-center gap-2">
                <button @click="showAnalytics = !showAnalytics"
                    :class="['flex items-center gap-1.5 h-9 px-3.5 rounded-xl text-[12.5px] font-semibold transition-all border',
                        showAnalytics
                            ? 'bg-sky-500 text-white border-sky-500'
                            : 'bg-white dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300 hover:border-sky-300 dark:hover:border-sky-500/40']">
                    <BarChart2 class="w-3.5 h-3.5" />
                    Analytics
                </button>
                <button @click="exportCsv"
                    class="flex items-center gap-1.5 h-9 px-3.5 rounded-xl text-[12.5px] font-semibold
                        bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                        text-slate-600 dark:text-slate-300 hover:border-emerald-300 dark:hover:border-emerald-500/40
                        hover:text-emerald-600 dark:hover:text-emerald-400 transition-all">
                    <Download class="w-3.5 h-3.5" />
                    Export CSV
                </button>
                <Link :href="route('admin.gateways.index')"
                    class="flex items-center gap-1.5 h-9 px-3.5 rounded-xl text-[12.5px] font-semibold
                        bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                        text-slate-600 dark:text-slate-300 hover:border-sky-300 dark:hover:border-sky-500/40
                        hover:text-sky-600 dark:hover:text-sky-400 transition-all">
                    <CreditCard class="w-3.5 h-3.5" />
                    Gateways
                </Link>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="flash.success"
            class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/[0.08] border border-emerald-200 dark:border-emerald-500/20">
            <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
            <p class="text-[13px] font-semibold text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
        </div>
        <div v-if="flash.error"
            class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/20">
            <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
            <p class="text-[13px] font-semibold text-rose-700 dark:text-rose-400">{{ flash.error }}</p>
        </div>

        <!-- Analytics Panel -->
        <div v-if="showAnalytics" class="mb-5 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
            <h2 class="text-[13px] font-bold text-slate-700 dark:text-slate-200 mb-4 flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-sky-500" />
                Deposit Analytics
            </h2>

            <!-- Key stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-4">
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-3 border border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-600 mb-1 uppercase tracking-wide">Total</p>
                    <p class="text-[20px] font-black text-slate-900 dark:text-white leading-none">{{ a.total ?? 0 }}</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-500/[0.06] rounded-xl p-3 border border-emerald-100 dark:border-emerald-500/20">
                    <p class="text-[10px] font-semibold text-emerald-500 mb-1 uppercase tracking-wide">Successful</p>
                    <p class="text-[20px] font-black text-emerald-700 dark:text-emerald-400 leading-none">{{ a.successful ?? 0 }}</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/[0.06] rounded-xl p-3 border border-amber-100 dark:border-amber-500/20">
                    <p class="text-[10px] font-semibold text-amber-500 mb-1 uppercase tracking-wide">Pending</p>
                    <p class="text-[20px] font-black text-amber-700 dark:text-amber-400 leading-none">{{ a.pending ?? 0 }}</p>
                </div>
                <div class="bg-rose-50 dark:bg-rose-500/[0.06] rounded-xl p-3 border border-rose-100 dark:border-rose-500/20">
                    <p class="text-[10px] font-semibold text-rose-500 mb-1 uppercase tracking-wide">Failed</p>
                    <p class="text-[20px] font-black text-rose-700 dark:text-rose-400 leading-none">{{ a.failed ?? 0 }}</p>
                </div>
                <div class="bg-sky-50 dark:bg-sky-500/[0.06] rounded-xl p-3 border border-sky-100 dark:border-sky-500/20">
                    <p class="text-[10px] font-semibold text-sky-500 mb-1 uppercase tracking-wide">Volume</p>
                    <p class="text-[18px] font-black text-sky-700 dark:text-sky-400 leading-none">${{ Number(a.totalVolume ?? 0).toFixed(2) }}</p>
                </div>
                <div class="bg-violet-50 dark:bg-violet-500/[0.06] rounded-xl p-3 border border-violet-100 dark:border-violet-500/20">
                    <p class="text-[10px] font-semibold text-violet-500 mb-1 uppercase tracking-wide">Success Rate</p>
                    <p class="text-[20px] font-black text-violet-700 dark:text-violet-400 leading-none">{{ a.successRate ?? 0 }}%</p>
                </div>
            </div>

            <!-- Secondary stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="rounded-xl p-3 bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-600 mb-1 uppercase tracking-wide">Credited</p>
                    <p class="text-[18px] font-black text-slate-900 dark:text-white leading-none">{{ a.credited ?? 0 }}</p>
                </div>
                <div class="rounded-xl p-3 bg-rose-50 dark:bg-rose-500/[0.05] border border-rose-100 dark:border-rose-500/15">
                    <p class="text-[10px] font-semibold text-rose-500 mb-1 uppercase tracking-wide">Failed Callbacks</p>
                    <p class="text-[18px] font-black text-rose-700 dark:text-rose-400 leading-none">{{ a.failedCallbacks ?? 0 }}</p>
                    <p class="text-[10px] text-rose-500/70 mt-0.5">finished but not credited</p>
                </div>
                <div class="rounded-xl p-3 bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-600 mb-1 uppercase tracking-wide">Avg Process Time</p>
                    <p class="text-[18px] font-black text-slate-900 dark:text-white leading-none">{{ fmtSec(a.avgProcessSeconds) }}</p>
                </div>
                <div class="rounded-xl p-3 bg-sky-50 dark:bg-sky-500/[0.05] border border-sky-100 dark:border-sky-500/15">
                    <p class="text-[10px] font-semibold text-sky-500 mb-1 uppercase tracking-wide">Today's Volume</p>
                    <p class="text-[18px] font-black text-sky-700 dark:text-sky-400 leading-none">—</p>
                </div>
            </div>

            <!-- By gateway -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wide">By Gateway</p>
                    <div class="space-y-1.5">
                        <div v-for="g in (a.byGateway ?? [])" :key="g.gateway"
                            class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05]">
                            <span class="text-[12px] font-semibold text-slate-700 dark:text-slate-300 capitalize">{{ g.gateway }}</span>
                            <div class="flex items-center gap-4">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ g.count }} txns</span>
                                <span class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400">${{ Number(g.volume).toFixed(2) }}</span>
                            </div>
                        </div>
                        <p v-if="!(a.byGateway ?? []).length" class="text-[12px] text-slate-400 dark:text-slate-600 px-3 py-2">No data yet</p>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wide">Top Currencies</p>
                    <div class="space-y-1.5">
                        <div v-for="c in (a.byCurrency ?? []).slice(0, 6)" :key="c.currency"
                            class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05]">
                            <span class="text-[12px] font-bold text-slate-700 dark:text-slate-300">{{ c.currency }}</span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ c.count }} txns</span>
                        </div>
                        <p v-if="!(a.byCurrency ?? []).length" class="text-[12px] text-slate-400 dark:text-slate-600 px-3 py-2">No data yet</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status tabs -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div v-for="tab in STATUS_TABS" :key="tab.key"
                @click="setStatus(tab.key)"
                :class="[
                    'bg-white dark:bg-[#0d1e35] rounded-2xl border p-4 cursor-pointer transition-all duration-150',
                    activeStatus === tab.key
                        ? 'border-sky-400 dark:border-sky-500/50 shadow-[0_0_0_3px_rgba(14,165,233,0.12)]'
                        : 'border-slate-200 dark:border-sky-500/12 hover:border-sky-300 dark:hover:border-sky-500/30',
                ]">
                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 mb-1">{{ tab.label }}</p>
                <p class="text-[22px] font-black text-slate-900 dark:text-white leading-none">{{ counts[tab.count] ?? 0 }}</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col gap-3 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                    <input v-model="search" @keydown.enter="applyFilters" type="search"
                        placeholder="Reference, hash, user, email…"
                        class="w-full h-9 pl-9 pr-4 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl
                            text-slate-700 dark:text-slate-300 placeholder:text-slate-400 dark:placeholder:text-slate-600
                            focus:outline-none focus:ring-2 focus:ring-sky-500/25 focus:border-sky-400 dark:focus:border-sky-500/40 transition-all" />
                </div>
                <button @click="applyFilters"
                    class="h-9 px-4 rounded-xl text-[12.5px] font-semibold bg-sky-500 text-white hover:bg-sky-600 transition-colors">
                    Search
                </button>
                <button @click="showFilters = !showFilters"
                    :class="['flex items-center gap-1.5 h-9 px-3.5 rounded-xl text-[12.5px] font-semibold transition-all border',
                        showFilters
                            ? 'bg-sky-50 dark:bg-sky-500/10 border-sky-300 dark:border-sky-500/40 text-sky-600 dark:text-sky-400'
                            : 'bg-white dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300']">
                    <Filter class="w-3.5 h-3.5" />
                    Filters
                </button>
            </div>

            <!-- Extra filters -->
            <div v-if="showFilters" class="flex flex-wrap gap-2 p-3.5 bg-sky-50/50 dark:bg-sky-500/[0.05] rounded-xl border border-sky-100 dark:border-sky-500/15">
                <!-- Gateway -->
                <select v-model="gateway"
                    class="h-8 px-2.5 text-[12px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/15 rounded-lg
                        text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-sky-400">
                    <option value="">All Gateways</option>
                    <option v-for="g in availableGateways" :key="g" :value="g" class="capitalize">{{ g }}</option>
                </select>

                <!-- Currency -->
                <input v-model="currency" placeholder="Currency (e.g. BTC)"
                    class="h-8 px-2.5 w-36 text-[12px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/15 rounded-lg
                        text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-400" />

                <!-- Date range -->
                <input v-model="dateFrom" type="date"
                    class="h-8 px-2.5 text-[12px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/15 rounded-lg
                        text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-sky-400" />
                <span class="self-center text-[11px] text-slate-400 dark:text-slate-600">to</span>
                <input v-model="dateTo" type="date"
                    class="h-8 px-2.5 text-[12px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/15 rounded-lg
                        text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-sky-400" />

                <button @click="applyFilters"
                    class="h-8 px-3 rounded-lg text-[12px] font-semibold bg-sky-500 text-white hover:bg-sky-600 transition-colors">
                    Apply
                </button>
                <button @click="clearFilters"
                    class="h-8 px-3 rounded-lg text-[12px] font-semibold bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                        text-slate-500 dark:text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 transition-colors">
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">

            <div v-if="invoices.data.length === 0" class="py-16 text-center">
                <CreditCard class="w-10 h-10 text-slate-300 dark:text-slate-700 mx-auto mb-3" />
                <p class="text-[14px] font-semibold text-slate-500 dark:text-slate-400">No payments found</p>
                <p class="text-[12px] text-slate-400 dark:text-slate-600 mt-1">Try adjusting your filters</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[12.5px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-sky-500/[0.08]">
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Reference / Hash</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">User</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Amount</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Gateway / Currency</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Date</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-sky-500/[0.05]">
                        <tr v-for="inv in invoices.data" :key="inv.id"
                            class="hover:bg-slate-50/50 dark:hover:bg-white/[0.015] transition-colors">

                            <!-- Reference -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-white/[0.04] px-2 py-0.5 rounded-md border border-slate-100 dark:border-white/[0.06]">
                                        {{ inv.reference.slice(0, 8) }}…
                                    </span>
                                    <span v-if="inv.is_credited"
                                        class="text-[9px] font-bold px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-500/20">
                                        CREDITED
                                    </span>
                                </div>
                                <div v-if="inv.blockchain_hash" class="mt-0.5 flex items-center gap-1">
                                    <span class="text-[9px] font-semibold text-violet-500 dark:text-violet-400">TX</span>
                                    <span class="font-mono text-[10px] text-slate-400 dark:text-slate-600">
                                        {{ inv.blockchain_hash.slice(0, 14) }}…
                                    </span>
                                </div>
                                <div v-else-if="inv.gateway_payment_id" class="mt-0.5">
                                    <span class="font-mono text-[10px] text-slate-400 dark:text-slate-600">
                                        {{ String(inv.gateway_payment_id).slice(0, 14) }}…
                                    </span>
                                </div>
                                <div v-if="inv.retry_count > 0" class="mt-0.5">
                                    <span class="text-[9px] font-semibold text-amber-500 dark:text-amber-400">{{ inv.retry_count }} retries</span>
                                </div>
                            </td>

                            <!-- User -->
                            <td class="px-4 py-3.5">
                                <div v-if="inv.user">
                                    <Link :href="route('admin.users.show', inv.user.id)"
                                        class="font-semibold text-slate-800 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
                                        {{ inv.user.name }}
                                    </Link>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5">{{ inv.user.email }}</p>
                                </div>
                                <span v-else class="text-slate-400 dark:text-slate-600">—</span>
                            </td>

                            <!-- Amount -->
                            <td class="px-4 py-3.5 text-right">
                                <p class="font-bold text-slate-800 dark:text-slate-200">${{ inv.price_amount.toFixed(2) }}</p>
                                <p v-if="inv.amount_received" class="text-[10.5px] text-slate-400 dark:text-slate-600 mt-0.5">
                                    Recv: {{ Number(inv.amount_received).toFixed(6) }}
                                </p>
                            </td>

                            <!-- Gateway / Currency -->
                            <td class="px-4 py-3.5">
                                <span class="capitalize text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] px-2 py-0.5 rounded">{{ inv.gateway }}</span>
                                <div class="mt-1 flex items-center gap-1">
                                    <span v-if="inv.pay_currency" class="uppercase font-bold text-slate-500 dark:text-slate-400 text-[11px]">{{ inv.pay_currency }}</span>
                                    <span v-if="inv.network" class="text-[10px] text-slate-400 dark:text-slate-600">/ {{ inv.network }}</span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5">
                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold text-[11px]', ss(inv.status).bg, ss(inv.status).color]">
                                    <span :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', ss(inv.status).dot]"></span>
                                    {{ inv.status_label }}
                                </span>
                                <div v-if="inv.failure_reason" class="mt-1">
                                    <span class="text-[10px] text-rose-500 dark:text-rose-400 truncate block max-w-[140px]" :title="inv.failure_reason">
                                        ⚠ {{ inv.failure_reason.slice(0, 40) }}{{ inv.failure_reason.length > 40 ? '…' : '' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ fmtDate(inv.created_at) }}
                                <div v-if="inv.credited_at" class="text-[10.5px] text-emerald-600 dark:text-emerald-500 mt-0.5">
                                    ✓ {{ fmtDate(inv.credited_at) }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- View details -->
                                    <Link :href="route('admin.payments.show', inv.id)"
                                        title="View full details"
                                        class="flex items-center h-7 w-7 justify-center rounded-lg
                                            bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                                            text-slate-400 dark:text-slate-600 hover:text-sky-500 dark:hover:text-sky-400
                                            hover:border-sky-300 dark:hover:border-sky-500/40 transition-colors">
                                        <Eye class="w-3 h-3" />
                                    </Link>

                                    <!-- Approve -->
                                    <button v-if="!inv.is_credited"
                                        @click="invoiceAction('admin.payments.approve', inv)"
                                        :disabled="!!processing[inv.id]"
                                        title="Approve & credit wallet"
                                        class="flex items-center gap-1 h-7 px-2.5 rounded-lg text-[11px] font-semibold
                                            bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400
                                            border border-emerald-200 dark:border-emerald-500/20
                                            hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors
                                            disabled:opacity-50 disabled:cursor-not-allowed">
                                        <Loader2 v-if="isProcessing(inv.id, 'admin.payments.approve')" class="w-3 h-3 animate-spin" />
                                        <ThumbsUp v-else class="w-3 h-3" />
                                        Approve
                                    </button>

                                    <!-- Retry -->
                                    <button v-if="inv.can_retry"
                                        @click="invoiceAction('admin.payments.retry', inv)"
                                        :disabled="!!processing[inv.id]"
                                        title="Retry credit job"
                                        class="flex items-center gap-1 h-7 px-2.5 rounded-lg text-[11px] font-semibold
                                            bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400
                                            border border-sky-200 dark:border-sky-500/20
                                            hover:bg-sky-100 dark:hover:bg-sky-500/20 transition-colors
                                            disabled:opacity-50 disabled:cursor-not-allowed">
                                        <Loader2 v-if="isProcessing(inv.id, 'admin.payments.retry')" class="w-3 h-3 animate-spin" />
                                        <RefreshCw v-else class="w-3 h-3" />
                                        Retry
                                    </button>

                                    <!-- Reject -->
                                    <button v-if="!inv.is_credited && !['finished','failed','refunded','expired'].includes(inv.status)"
                                        @click="invoiceAction('admin.payments.reject', inv)"
                                        :disabled="!!processing[inv.id]"
                                        title="Reject deposit"
                                        class="flex items-center gap-1 h-7 px-2.5 rounded-lg text-[11px] font-semibold
                                            bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400
                                            border border-rose-200 dark:border-rose-500/20
                                            hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors
                                            disabled:opacity-50 disabled:cursor-not-allowed">
                                        <ThumbsDown class="w-3 h-3" />
                                        Reject
                                    </button>

                                    <!-- Payment link -->
                                    <a v-if="inv.payment_url" :href="inv.payment_url" target="_blank"
                                        title="Open payment page"
                                        class="flex items-center h-7 w-7 justify-center rounded-lg
                                            bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                                            text-slate-400 dark:text-slate-600 hover:text-sky-500 dark:hover:text-sky-400
                                            hover:border-sky-300 dark:hover:border-sky-500/40 transition-colors">
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between">
            <p class="text-[12px] text-slate-400 dark:text-slate-600">
                Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
            </p>
            <div class="flex items-center gap-1">
                <component v-for="link in links" :key="link.label"
                    :is="link.url ? 'a' : 'span'"
                    :href="link.url ?? undefined"
                    v-html="link.label"
                    :class="[
                        'inline-flex items-center justify-center min-w-[32px] h-8 px-2 rounded-lg text-[12px] font-semibold transition-colors',
                        link.active
                            ? 'bg-sky-500 text-white'
                            : link.url
                                ? 'bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 text-slate-500 dark:text-slate-400 hover:border-sky-300 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-400'
                                : 'text-slate-300 dark:text-slate-700 cursor-default',
                    ]" />
            </div>
        </div>

    </AdminLayout>
</template>

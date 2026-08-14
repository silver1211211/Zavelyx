<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownLeft, ArrowUpDown, ArrowUpRight, Calendar,
    ChevronDown, ChevronLeft, ChevronRight, Copy, Download,
    Gift, RefreshCw, Search, TrendingDown, TrendingUp, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    transactions: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    filters:      { type: Object, default: () => ({}) },
    summary:      { type: Object, default: () => ({ total_in: 0, total_out: 0, net: 0 }) },
});

const { symbol, formatAmount: convertAmount } = useCurrency();

// ── Filters (reactive, synced to URL) ─────────────────────────────────────────
const typeFilter   = ref(props.filters.type      ?? '');
const statusFilter = ref(props.filters.status    ?? '');
const searchQuery  = ref(props.filters.search    ?? '');
const dateFrom     = ref(props.filters.date_from ?? '');
const dateTo       = ref(props.filters.date_to   ?? '');

let debounceTimer;
function applyFilters() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('transactions.index'), {
            type:      typeFilter.value   || undefined,
            status:    statusFilter.value || undefined,
            search:    searchQuery.value  || undefined,
            date_from: dateFrom.value     || undefined,
            date_to:   dateTo.value       || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
}

watch([typeFilter, statusFilter, dateFrom, dateTo], applyFilters);
watch(searchQuery, applyFilters);

function clearFilters() {
    typeFilter.value = statusFilter.value = searchQuery.value = dateFrom.value = dateTo.value = '';
}

const hasActiveFilters = computed(() =>
    typeFilter.value || statusFilter.value || searchQuery.value || dateFrom.value || dateTo.value
);

// ── Transaction type config ───────────────────────────────────────────────────
const typeConfig = {
    deposit:      { label: 'Deposit',    icon: ArrowDownLeft, credit: true,  color: 'emerald' },
    order_debit:  { label: 'Order',      icon: ArrowUpRight,  credit: false, color: 'rose' },
    refund:       { label: 'Refund',     icon: RefreshCw,     credit: true,  color: 'sky' },
    bonus:        { label: 'Bonus',      icon: Gift,          credit: true,  color: 'violet' },
    admin_credit: { label: 'Adjustment', icon: ArrowDownLeft, credit: true,  color: 'amber' },
    withdrawal:   { label: 'Withdrawal', icon: ArrowUpRight,  credit: false, color: 'rose' },
};

const colorMap = {
    emerald: { badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', icon: 'bg-emerald-500/10 text-emerald-500', amount: 'text-emerald-600 dark:text-emerald-400' },
    rose:    { badge: 'bg-rose-500/10 text-rose-500',                             icon: 'bg-rose-500/10 text-rose-500',        amount: 'text-rose-500' },
    sky:     { badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',             icon: 'bg-sky-500/10 text-sky-500',          amount: 'text-sky-600 dark:text-sky-400' },
    violet:  { badge: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',    icon: 'bg-violet-500/10 text-violet-500',    amount: 'text-violet-600 dark:text-violet-400' },
    amber:   { badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',       icon: 'bg-amber-500/10 text-amber-500',      amount: 'text-amber-600 dark:text-amber-400' },
    slate:   { badge: 'bg-slate-100 dark:bg-white/5 text-slate-500',              icon: 'bg-slate-100 dark:bg-white/8 text-slate-400', amount: 'text-slate-600 dark:text-slate-400' },
};

function txCfg(type) {
    return typeConfig[type] ?? { label: type, icon: ArrowUpDown, credit: true, color: 'slate' };
}
function txColors(type) { return colorMap[txCfg(type).color] ?? colorMap.slate; }

const statusConfig = {
    completed:  'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    successful: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    pending:    'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    failed:     'bg-rose-500/10 text-rose-500',
};

// ── Detail drawer ─────────────────────────────────────────────────────────────
const selectedTx  = ref(null);
const drawerOpen  = ref(false);
const copiedKey   = ref('');

function openDrawer(tx) { selectedTx.value = tx; drawerOpen.value = true; }
function closeDrawer()  { drawerOpen.value = false; setTimeout(() => { selectedTx.value = null; }, 300); }

function copyText(text, key) {
    navigator.clipboard.writeText(text).then(() => {
        copiedKey.value = key;
        setTimeout(() => { copiedKey.value = ''; }, 1800);
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function exportUrl() {
    const params = new URLSearchParams();
    if (typeFilter.value)   params.set('type',      typeFilter.value);
    if (statusFilter.value) params.set('status',    statusFilter.value);
    if (dateFrom.value)     params.set('date_from', dateFrom.value);
    if (dateTo.value)       params.set('date_to',   dateTo.value);
    return route('transactions.export') + (params.toString() ? '?' + params : '');
}

const txList = computed(() =>
    Array.isArray(props.transactions) ? props.transactions : (props.transactions?.data ?? [])
);
</script>

<template>
    <Head title="Transactions" />
    <AuthenticatedLayout>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.15))">
                        <ArrowUpDown class="w-4 h-4 text-sky-500" />
                    </div>
                    Transaction History
                </h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">Complete ledger of all account activity.</p>
            </div>
            <a :href="exportUrl()" class="flex items-center gap-2 px-4 py-2 rounded-xl text-[12.5px] font-semibold bg-white dark:bg-[var(--surface-card)] border border-slate-200 dark:border-white/[0.07] text-slate-600 dark:text-slate-300 hover:border-sky-500/40 transition-all active:scale-95">
                <Download class="w-3.5 h-3.5" />
                Export CSV
            </a>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-5">
            <div class="bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.07] p-2.5 sm:p-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4 relative overflow-hidden shadow-sm">
                <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at 0% 0%, rgba(16,185,129,0.06), transparent 60%)" />
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-500/10">
                    <TrendingUp class="w-3.5 h-3.5 sm:w-5 sm:h-5 text-emerald-500" />
                </div>
                <div class="min-w-0">
                    <p class="text-[9px] sm:text-[11px] font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wide leading-tight">
                        <span class="sm:hidden">Credits</span><span class="hidden sm:inline">Total Credits</span>
                    </p>
                    <p class="text-[13px] sm:text-[20px] font-black text-emerald-500 leading-tight truncate">+{{ symbol }}{{ convertAmount(summary.total_in) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.07] p-2.5 sm:p-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4 relative overflow-hidden shadow-sm">
                <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at 0% 0%, rgba(239,68,68,0.06), transparent 60%)" />
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-500/10">
                    <TrendingDown class="w-3.5 h-3.5 sm:w-5 sm:h-5 text-rose-500" />
                </div>
                <div class="min-w-0">
                    <p class="text-[9px] sm:text-[11px] font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wide leading-tight">
                        <span class="sm:hidden">Debits</span><span class="hidden sm:inline">Total Debits</span>
                    </p>
                    <p class="text-[13px] sm:text-[20px] font-black text-rose-500 leading-tight truncate">-{{ symbol }}{{ convertAmount(summary.total_out) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.07] p-2.5 sm:p-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-4 relative overflow-hidden shadow-sm">
                <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at 0% 0%, rgba(14,165,233,0.06), transparent 60%)" />
                <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-sky-500/10">
                    <ArrowUpDown class="w-3.5 h-3.5 sm:w-5 sm:h-5 text-sky-500" />
                </div>
                <div class="min-w-0">
                    <p class="text-[9px] sm:text-[11px] font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wide leading-tight">Net</p>
                    <p class="text-[13px] sm:text-[20px] font-black leading-tight truncate" :class="summary.net >= 0 ? 'text-sky-500' : 'text-rose-500'">
                        {{ summary.net >= 0 ? '+' : '' }}{{ symbol }}{{ convertAmount(Math.abs(summary.net)) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters bar -->
        <div class="mb-5 bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.07] p-4 space-y-3 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-400 pointer-events-none" />
                    <input v-model="searchQuery" type="search" placeholder="Search description or reference…"
                        class="w-full h-9 pl-9 pr-4 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                </div>

                <!-- Type pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap sm:overflow-visible" style="scrollbar-width: none;">
                    <button v-for="f in [
                        { val: '',            label: 'All' },
                        { val: 'deposit',     label: 'Deposits' },
                        { val: 'order_debit', label: 'Orders' },
                        { val: 'refund',      label: 'Refunds' },
                        { val: 'bonus',       label: 'Bonuses' },
                    ]" :key="f.val" @click="typeFilter = f.val"
                        :class="['flex-shrink-0 whitespace-nowrap px-3 py-1.5 rounded-xl text-[11.5px] font-semibold transition-all',
                            typeFilter === f.val ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10']">
                        {{ f.label }}
                    </button>
                </div>
            </div>

            <!-- Date range + status -->
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Calendar class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                    <div class="grid grid-cols-[1fr_auto_1fr] sm:flex sm:items-center gap-2 flex-1 sm:flex-initial">
                        <input v-model="dateFrom" type="date" placeholder="From"
                            class="w-full sm:w-auto h-8 px-2 sm:px-2.5 text-[11px] sm:text-[12px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                        <span class="text-[11px] text-slate-400 self-center">to</span>
                        <input v-model="dateTo" type="date" placeholder="To"
                            class="w-full sm:w-auto h-8 px-2 sm:px-2.5 text-[11px] sm:text-[12px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="statusFilter"
                        class="flex-1 sm:flex-initial h-8 px-2.5 text-[12px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                        <option value="">All statuses</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>

                    <button v-if="hasActiveFilters" @click="clearFilters"
                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12px] font-semibold text-rose-500 border border-rose-500/20 hover:bg-rose-500/8 transition-all active:scale-95">
                        <X class="w-3.5 h-3.5" />
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table card -->
        <div class="bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.07] overflow-hidden shadow-sm">

            <div v-if="txList.length === 0" class="py-16 flex flex-col items-center gap-3 text-center">
                <ArrowUpDown class="w-10 h-10 text-slate-300 dark:text-slate-500" />
                <p class="text-[14px] font-semibold text-slate-600 dark:text-slate-400">No transactions found</p>
                <p class="text-[12px] text-slate-400 dark:text-slate-400">
                    {{ hasActiveFilters ? 'Try adjusting your filters.' : 'Transactions will appear here once your account is active.' }}
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-white/[0.06] bg-slate-50 dark:bg-white/[0.04]">
                            <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Activity</th>
                            <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400 hidden sm:table-cell">Reference</th>
                            <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Amount</th>
                            <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400 hidden md:table-cell">Balance After</th>
                            <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400 hidden sm:table-cell">Status</th>
                            <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400 hidden lg:table-cell">Date</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        <tr v-for="tx in txList" :key="tx.reference"
                            class="hover:bg-slate-50 dark:hover:bg-white/[0.025] transition-colors cursor-pointer"
                            @click="openDrawer(tx)">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0', txColors(tx.type).icon]">
                                        <component :is="txCfg(tx.type).icon" class="w-3.5 h-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[180px]">
                                            {{ tx.description || txCfg(tx.type).label }}
                                        </p>
                                        <span :class="['text-[10.5px] font-semibold', txColors(tx.type).amount]">{{ txCfg(tx.type).label }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <code class="text-[11px] text-slate-400 dark:text-slate-400 font-mono">{{ tx.reference?.slice(0, 13) }}…</code>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <span :class="['font-bold tabular-nums', txColors(tx.type).amount]">
                                    {{ txCfg(tx.type).credit ? '+' : '-' }}{{ symbol }}{{ convertAmount(tx.amount) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-500 dark:text-slate-400 font-medium hidden md:table-cell">
                                {{ symbol }}{{ convertAmount(tx.balance_after ?? 0) }}
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize', statusConfig[tx.status] ?? 'bg-slate-100 dark:bg-white/5 text-slate-500']">
                                    {{ tx.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-400 dark:text-slate-400 text-[11.5px] whitespace-nowrap hidden lg:table-cell">
                                {{ formatDate(tx.created_at) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <ChevronRight class="w-4 h-4 text-slate-300 dark:text-slate-500" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="(transactions?.meta?.last_page ?? 1) > 1" class="flex items-center justify-between px-5 py-4 border-t border-slate-100 dark:border-white/[0.06]">
                <p class="text-[12px] text-slate-400 dark:text-slate-400">
                    Showing {{ transactions.meta?.from ?? 0 }}–{{ transactions.meta?.to ?? 0 }} of {{ transactions.meta?.total ?? 0 }}
                </p>
                <div class="flex items-center gap-1">
                    <Link v-for="link in transactions.links" :key="link.label"
                        :href="link.url ?? ''"
                        :class="['px-3 py-1.5 rounded-lg text-[12px] font-medium transition-all',
                            link.active ? 'bg-sky-500 text-white shadow-sm' :
                            link.url ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' :
                            'text-slate-300 dark:text-slate-500 cursor-default pointer-events-none']"
                        preserve-scroll
                        v-html="link.label" />
                </div>
            </div>
        </div>

        <!-- ── Detail drawer ────────────────────────────────────────────────── -->
        <Teleport to="body">
            <!-- Backdrop -->
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="drawerOpen" class="fixed inset-0 bg-black/40 dark:bg-black/60 z-40 backdrop-blur-sm" @click="closeDrawer" />
            </Transition>

            <!-- Panel -->
            <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-x-full opacity-0" enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in" leave-from-class="translate-x-0 opacity-100" leave-to-class="translate-x-full opacity-0">
                <div v-if="drawerOpen && selectedTx" class="fixed top-0 right-0 h-full w-full max-w-md bg-white dark:bg-[var(--surface-card)] border-l border-slate-200 dark:border-white/[0.07] z-50 overflow-y-auto shadow-2xl">

                    <!-- Drawer header -->
                    <div class="sticky top-0 bg-white dark:bg-[var(--surface-card)] border-b border-slate-100 dark:border-white/[0.06] px-6 py-4 flex items-center justify-between z-10">
                        <div class="flex items-center gap-3">
                            <div :class="['w-9 h-9 rounded-xl flex items-center justify-center', txColors(selectedTx.type).icon]">
                                <component :is="txCfg(selectedTx.type).icon" class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-slate-900 dark:text-white">Transaction Detail</p>
                                <p :class="['text-[11px] font-semibold', txColors(selectedTx.type).amount]">{{ txCfg(selectedTx.type).label }}</p>
                            </div>
                        </div>
                        <button @click="closeDrawer" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/5 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Amount hero -->
                    <div class="px-6 py-8 text-center border-b border-slate-100 dark:border-white/[0.06]">
                        <p :class="['text-[36px] font-black tabular-nums', txColors(selectedTx.type).amount]">
                            {{ txCfg(selectedTx.type).credit ? '+' : '-' }}{{ symbol }}{{ convertAmount(selectedTx.amount) }}
                        </p>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-1">{{ selectedTx.description || txCfg(selectedTx.type).label }}</p>
                        <span :class="['mt-3 inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold capitalize', statusConfig[selectedTx.status] ?? 'bg-slate-100 dark:bg-white/5 text-slate-500']">
                            {{ selectedTx.status }}
                        </span>
                    </div>

                    <!-- Details list -->
                    <div class="px-6 py-5 space-y-4">
                        <template v-for="row in [
                            { label: 'Date',           value: formatDate(selectedTx.created_at) },
                            { label: 'Type',           value: txCfg(selectedTx.type).label },
                            { label: 'Balance Before', value: symbol + convertAmount(selectedTx.balance_before ?? 0) },
                            { label: 'Balance After',  value: symbol + convertAmount(selectedTx.balance_after ?? 0) },
                        ]" :key="row.label">
                            <div class="flex items-center justify-between py-2.5 border-b border-slate-50 dark:border-white/[0.04] last:border-0">
                                <span class="text-[12px] text-slate-400 dark:text-slate-400">{{ row.label }}</span>
                                <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ row.value }}</span>
                            </div>
                        </template>

                        <!-- Reference with copy -->
                        <div class="flex items-center justify-between py-2.5 border-b border-slate-50 dark:border-white/[0.04]">
                            <span class="text-[12px] text-slate-400 dark:text-slate-400">Reference</span>
                            <button @click="copyText(selectedTx.reference, 'ref')"
                                class="flex items-center gap-1.5 text-[12px] font-mono font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-500 transition-colors">
                                {{ selectedTx.reference?.slice(0, 18) }}…
                                <component :is="copiedKey === 'ref' ? ArrowUpDown : Copy" class="w-3 h-3 flex-shrink-0" />
                            </button>
                        </div>

                        <!-- Metadata section -->
                        <template v-if="selectedTx.metadata && Object.keys(selectedTx.metadata).length > 0">
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 pt-2">Additional Details</p>
                            <div v-for="(val, key) in selectedTx.metadata" :key="key"
                                class="flex items-start justify-between py-2 border-b border-slate-50 dark:border-white/[0.04] last:border-0 gap-4">
                                <span class="text-[12px] text-slate-400 dark:text-slate-400 capitalize">{{ String(key).replace(/_/g, ' ') }}</span>
                                <span class="text-[12px] font-medium text-slate-600 dark:text-slate-400 text-right break-all max-w-[220px] font-mono">{{ val }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

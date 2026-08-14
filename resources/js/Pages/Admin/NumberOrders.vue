<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowDownRight, ArrowUpRight, Ban, Check,
    ChevronRight, Clock, Copy, DollarSign, Phone,
    RefreshCw, Search, ShieldCheck, TrendingUp, X, Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    orders:  { type: Object, default: () => ({ data: [] }) },
    stats:   { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

// ── Filter state ──────────────────────────────────────────────────────────────
const search     = ref(props.filters.search      ?? '');
const scope      = ref(props.filters.scope       ?? '');
const statusFlt  = ref(props.filters.status      ?? '');
const serviceFlt = ref(props.filters.service     ?? '');
const dateFrom   = ref(props.filters.date_from   ?? '');
const dateTo     = ref(props.filters.date_to     ?? '');
const showMore   = ref(false);

function applyFilters() {
    router.get(route('admin.number-orders.index'), {
        search:      search.value      || undefined,
        scope:       scope.value       || undefined,
        status:      statusFlt.value   || undefined,
        service:     serviceFlt.value  || undefined,
        date_from:   dateFrom.value    || undefined,
        date_to:     dateTo.value      || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function setScope(s) {
    scope.value    = s;
    statusFlt.value = '';
    applyFilters();
}

function clearFilters() {
    search.value = ''; scope.value = ''; statusFlt.value = '';
    serviceFlt.value = ''; dateFrom.value = ''; dateTo.value = '';
    router.get(route('admin.number-orders.index'), {}, { preserveState: false });
}

const hasFilters = computed(() =>
    search.value || scope.value || statusFlt.value ||
    serviceFlt.value || dateFrom.value || dateTo.value
);

// ── Scopes ────────────────────────────────────────────────────────────────────
const SCOPES = [
    { key: '',           label: 'All Orders' },
    { key: 'successful', label: 'Successful' },
    { key: 'pending',    label: 'Pending' },
    { key: 'cancelled',  label: 'Cancelled' },
    { key: 'failed',     label: 'Failed' },
];

const STATUSES = ['PENDING','RECEIVED','FINISHED','CANCELLED','BANNED','EXPIRED','TIMEOUT'];

// ── Labels & display helpers ──────────────────────────────────────────────────
const COUNTRY_NAMES = {
    usa:'United States', england:'United Kingdom', germany:'Germany',
    france:'France', russia:'Russia', indonesia:'Indonesia',
    philippines:'Philippines', vietnam:'Vietnam', india:'India',
    brazil:'Brazil', cambodia:'Cambodia', bangladesh:'Bangladesh',
    pakistan:'Pakistan', malaysia:'Malaysia', thailand:'Thailand',
    srilanka:'Sri Lanka', nigeria:'Nigeria', ghana:'Ghana',
    kenya:'Kenya', ethiopia:'Ethiopia', egypt:'Egypt',
    morocco:'Morocco', southafrica:'South Africa', tanzania:'Tanzania',
    senegal:'Senegal', ivorycoast:'Ivory Coast', mexico:'Mexico',
    colombia:'Colombia', argentina:'Argentina', chile:'Chile',
    peru:'Peru', saudiarabia:'Saudi Arabia', jordan:'Jordan',
    israel:'Israel', kuwait:'Kuwait', poland:'Poland',
    romania:'Romania', czech:'Czech Republic', italy:'Italy',
    spain:'Spain', netherlands:'Netherlands', sweden:'Sweden',
    portugal:'Portugal', bulgaria:'Bulgaria', uzbekistan:'Uzbekistan',
    kazakhstan:'Kazakhstan', kyrgyzstan:'Kyrgyzstan', tajikistan:'Tajikistan',
    taiwan:'Taiwan', hongkong:'Hong Kong', australia:'Australia',
    canada:'Canada', ukraine:'Ukraine', turkey:'Turkey',
    japan:'Japan', singapore:'Singapore', southkorea:'South Korea',
    myanmar:'Myanmar', iran:'Iran', iraq:'Iraq', uae:'UAE',
    greece:'Greece', albania:'Albania', austria:'Austria',
    belgium:'Belgium', bolivia:'Bolivia', croatia:'Croatia',
    cyprus:'Cyprus', denmark:'Denmark', estonia:'Estonia',
    finland:'Finland', georgia:'Georgia', hungary:'Hungary',
    ireland:'Ireland', latvia:'Latvia', lithuania:'Lithuania',
    malta:'Malta', moldova:'Moldova', newzealand:'New Zealand',
    paraguay:'Paraguay', serbia:'Serbia', slovakia:'Slovakia',
    slovenia:'Slovenia', switzerland:'Switzerland', china:'China',
    hongkong:'Hong Kong', unitedstates:'United States',
};

function countryName(c) {
    if (!c || c === 'any') return null;
    return COUNTRY_NAMES[c.toLowerCase()] ?? c.replace(/_/g, ' ').replace(/\b\w/g, x => x.toUpperCase());
}

const SERVICE_LABELS = {
    telegram:'Telegram', whatsapp:'WhatsApp', google:'Google', discord:'Discord',
    openai:'OpenAI', tiktok:'TikTok', instagram:'Instagram', facebook:'Facebook',
    twitter:'Twitter / X', amazon:'Amazon', microsoft:'Microsoft', binance:'Binance',
    bybit:'Bybit', okx:'OKX', uber:'Uber', netflix:'Netflix', apple:'Apple',
    paypal:'PayPal', ebay:'eBay', airbnb:'Airbnb', linkedin:'LinkedIn',
    snapchat:'Snapchat', steam:'Steam', coinbase:'Coinbase', viber:'Viber',
    wise:'Wise', revolut:'Revolut', stripe:'Stripe',
};
function svcLabel(id) {
    const k = (id ?? '').toLowerCase();
    return SERVICE_LABELS[k] ?? (id ?? '').replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

const OPERATOR_NAMES = {
    telkomsel:'Telkomsel', beeline:'Beeline', megafon:'MegaFon',
    tele2:'Tele 2', mts:'MTS', tmobile:'T-Mobile', att:'AT&T',
    verizon:'Verizon', orange:'Orange', vodafone:'Vodafone',
    o2:'O2', lyca:'Lyca Mobile', airtel:'Airtel', jio:'Jio',
    dtac:'DTAC', truemove:'True Move', ais:'AIS',
    digi:'Digi', celcom:'Celcom', maxis:'Maxis',
    globe:'Globe', smart:'Smart',
};
function operatorLabel(name) {
    if (!name || name === 'any') return null;
    return OPERATOR_NAMES[name.toLowerCase()] ?? name.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// ── Status colours ────────────────────────────────────────────────────────────
function statusColor(s) {
    const map = {
        PENDING:   'bg-amber-500/15 text-amber-400 border-amber-500/25',
        RECEIVED:  'bg-sky-500/15 text-sky-400 border-sky-500/25',
        FINISHED:  'bg-emerald-500/15 text-emerald-400 border-emerald-500/25',
        CANCELLED: 'bg-orange-500/15 text-orange-400 border-orange-500/25',
        BANNED:    'bg-rose-500/15 text-rose-400 border-rose-500/25',
        EXPIRED:   'bg-slate-500/15 text-slate-400 border-slate-500/20',
        TIMEOUT:   'bg-slate-500/15 text-slate-400 border-slate-500/20',
    };
    return map[s] ?? map.PENDING;
}
function statusDot(s) {
    const map = {
        PENDING:   'bg-amber-400 animate-pulse',
        RECEIVED:  'bg-sky-400 animate-pulse',
        FINISHED:  'bg-emerald-400',
        CANCELLED: 'bg-orange-400',
        BANNED:    'bg-rose-400',
        EXPIRED:   'bg-slate-500',
        TIMEOUT:   'bg-slate-500',
    };
    return map[s] ?? 'bg-slate-500';
}

// ── Detail panel ──────────────────────────────────────────────────────────────
const selectedOrder  = ref(null);
const copied         = ref(null);
const showRawJson    = ref(false);

function openDetail(order) { selectedOrder.value = order; showRawJson.value = false; }
function closeDetail()     { selectedOrder.value = null; }

async function copyText(text, key) {
    try {
        await navigator.clipboard.writeText(String(text));
        copied.value = key;
        setTimeout(() => { if (copied.value === key) copied.value = null; }, 2000);
    } catch {}
}

// ── Formatting helpers ────────────────────────────────────────────────────────
function fmt(n, dp = 2) { return Number(n ?? 0).toFixed(dp); }
function fmtDate(s)      { return s ? new Date(s).toLocaleString() : '—'; }
function fmtDateShort(s) { return s ? new Date(s).toLocaleDateString() : '—'; }
function fmtMoney(n)     { return '$' + fmt(n, 4); }

// ── Computed stats helpers ────────────────────────────────────────────────────
const profitMargin = computed(() => {
    const rev = Number(props.stats.realized_revenue ?? 0);
    const pft = Number(props.stats.net_profit ?? 0);
    return rev > 0 ? Math.round(pft / rev * 100) : 0;
});
</script>

<template>
    <Head title="Number Orders — Admin" />
    <AdminLayout>

        <!-- ── Page header ──────────────────────────────────────────────────── -->
        <div class="mb-6 flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-[18px] font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <Phone class="w-5 h-5 text-sky-500" :stroke-width="2" />
                    Number Orders
                </h1>
                <p class="text-[12.5px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Virtual number purchases · SMS activations · Full accounting
                </p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- STATS — Row 1: order counts                                         -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-3">

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-3.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1">Total</p>
                <p class="text-[20px] font-black text-slate-800 dark:text-white tabular-nums">
                    {{ Number(stats.total_orders ?? 0).toLocaleString() }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">all orders</p>
            </div>

            <div class="rounded-2xl border border-emerald-100 dark:border-emerald-500/15 bg-white dark:bg-[#09111f] p-3.5"
                style="background:linear-gradient(135deg,rgba(16,185,129,0.03),transparent)">
                <p class="text-[9px] font-black uppercase tracking-widest text-emerald-500 dark:text-emerald-400 mb-1">Successful</p>
                <p class="text-[20px] font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                    {{ Number(stats.successful ?? 0).toLocaleString() }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">SMS delivered</p>
            </div>

            <div class="rounded-2xl border border-amber-100 dark:border-amber-500/15 bg-white dark:bg-[#09111f] p-3.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-amber-500 dark:text-amber-400 mb-1">Pending</p>
                <p class="text-[20px] font-black text-amber-600 dark:text-amber-400 tabular-nums">
                    {{ Number(stats.pending_count ?? 0).toLocaleString() }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">waiting for SMS</p>
            </div>

            <div class="rounded-2xl border border-orange-100 dark:border-orange-500/15 bg-white dark:bg-[#09111f] p-3.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-orange-500 dark:text-orange-400 mb-1">Cancelled</p>
                <p class="text-[20px] font-black text-orange-600 dark:text-orange-400 tabular-nums">
                    {{ Number(stats.cancelled_count ?? 0).toLocaleString() }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">refunded to users</p>
            </div>

            <div class="rounded-2xl border border-rose-100 dark:border-rose-500/15 bg-white dark:bg-[#09111f] p-3.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-rose-500 dark:text-rose-400 mb-1">Failed</p>
                <p class="text-[20px] font-black text-rose-600 dark:text-rose-400 tabular-nums">
                    {{ Number(stats.failed_count ?? 0).toLocaleString() }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">banned/expired</p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-3.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1">Refunded</p>
                <p class="text-[20px] font-black text-slate-700 dark:text-slate-300 tabular-nums font-mono">
                    ${{ fmt(stats.total_refunded, 2) }}
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5">
                    {{ stats.cancelled_count ?? 0 }} orders
                </p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- STATS — Row 2: financials (realized only)                           -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-4">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1">Realized Revenue</p>
                <p class="text-[22px] font-black text-sky-600 dark:text-sky-400 tabular-nums font-mono">
                    ${{ fmt(stats.realized_revenue, 2) }}
                </p>
                <p class="text-[10.5px] text-slate-400 dark:text-slate-400 mt-0.5">
                    SMS delivered · <span class="text-slate-400">${{ fmt(stats.gross_revenue, 2) }} gross</span>
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-4">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1">Provider Cost</p>
                <p class="text-[22px] font-black text-rose-500 dark:text-rose-400 tabular-nums font-mono">
                    ${{ fmt(stats.realized_cost, 2) }}
                </p>
                <p class="text-[10.5px] text-slate-400 dark:text-slate-400 mt-0.5">
                    realized · <span class="text-slate-400">${{ fmt(stats.gross_cost, 2) }} gross</span>
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-4"
                style="background:linear-gradient(135deg,rgba(16,185,129,0.04),transparent)">
                <p class="text-[9px] font-black uppercase tracking-widest text-emerald-500 dark:text-emerald-400 mb-1">Net Profit</p>
                <p class="text-[22px] font-black text-emerald-600 dark:text-emerald-400 tabular-nums font-mono">
                    ${{ fmt(stats.net_profit, 2) }}
                </p>
                <p class="text-[10.5px] text-slate-400 dark:text-slate-400 mt-0.5">successful orders only</p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-4"
                style="background:linear-gradient(135deg,rgba(16,185,129,0.04),transparent)">
                <p class="text-[9px] font-black uppercase tracking-widest text-emerald-500 dark:text-emerald-400 mb-1">Profit Margin</p>
                <p class="text-[22px] font-black text-emerald-600 dark:text-emerald-400 tabular-nums font-mono">
                    {{ profitMargin }}%
                </p>
                <p class="text-[10.5px] text-slate-400 dark:text-slate-400 mt-0.5">on realized revenue</p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- QUICK SCOPE TABS                                                    -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <div class="flex items-center gap-1.5 mb-4 overflow-x-auto scrollbar-none">
            <button v-for="s in SCOPES" :key="s.key"
                @click="setScope(s.key)"
                class="flex-shrink-0 h-8 px-3.5 rounded-xl text-[12px] font-bold transition-all"
                :class="scope === s.key
                    ? 'text-white shadow-sm'
                    : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-white/[0.05] hover:bg-slate-200 dark:hover:bg-white/[0.08]'"
                :style="scope === s.key ? 'background:linear-gradient(135deg,#0ea5e9,#6366f1)' : ''">
                {{ s.label }}
                <span v-if="s.key === '' && stats.total_orders"
                    class="ml-1.5 text-[10px] opacity-70">{{ stats.total_orders }}</span>
                <span v-if="s.key === 'successful' && stats.successful"
                    class="ml-1.5 text-[10px] opacity-70">{{ stats.successful }}</span>
                <span v-if="s.key === 'pending' && stats.pending_count"
                    class="ml-1.5 text-[10px] opacity-70">{{ stats.pending_count }}</span>
                <span v-if="s.key === 'cancelled' && stats.cancelled_count"
                    class="ml-1.5 text-[10px] opacity-70">{{ stats.cancelled_count }}</span>
                <span v-if="s.key === 'failed' && stats.failed_count"
                    class="ml-1.5 text-[10px] opacity-70">{{ stats.failed_count }}</span>
            </button>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- FILTERS                                                             -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <div class="mb-4 space-y-2">
            <!-- Primary filter row -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[220px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                    <input v-model="search" type="text"
                        placeholder="Search phone, ID, user, email, service, country, operator, provider…"
                        @keyup.enter="applyFilters"
                        class="w-full h-9 pl-9 pr-3 text-[12.5px] rounded-xl border border-slate-200 dark:border-white/[0.1]
                            bg-white dark:bg-[#09111f] text-slate-700 dark:text-slate-300
                            focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                </div>

                <button @click="showMore = !showMore"
                    class="h-9 px-3 rounded-xl text-[12px] font-bold transition-all flex items-center gap-1.5
                        border border-slate-200 dark:border-white/[0.1] text-slate-500 dark:text-slate-400
                        hover:bg-slate-100 dark:hover:bg-white/[0.05]">
                    Filters
                    <span v-if="hasFilters"
                        class="w-2 h-2 rounded-full bg-sky-500 flex-shrink-0" />
                </button>

                <button @click="applyFilters"
                    class="h-9 px-4 rounded-xl text-[12.5px] font-bold text-white transition-all active:scale-95"
                    style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                    Search
                </button>

                <button v-if="hasFilters" @click="clearFilters"
                    class="h-9 px-3 rounded-xl text-[12px] font-medium text-slate-500 dark:text-slate-400
                        bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] transition-all flex items-center gap-1">
                    <X class="w-3.5 h-3.5" /> Clear
                </button>
            </div>

            <!-- Expanded filter row -->
            <div v-show="showMore" class="flex flex-wrap items-center gap-2">
                <select v-model="statusFlt" @change="applyFilters"
                    class="h-9 px-3 text-[12.5px] rounded-xl border border-slate-200 dark:border-white/[0.1]
                        bg-white dark:bg-[#09111f] text-slate-700 dark:text-slate-300
                        focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                    <option value="">All statuses</option>
                    <option v-for="s in STATUSES" :key="s" :value="s.toLowerCase()">{{ s }}</option>
                </select>

                <input v-model="serviceFlt" type="text" placeholder="Service (e.g. telegram)"
                    @keyup.enter="applyFilters"
                    class="h-9 px-3 text-[12.5px] rounded-xl border border-slate-200 dark:border-white/[0.1]
                        bg-white dark:bg-[#09111f] text-slate-700 dark:text-slate-300
                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 w-44" />

                <div class="flex items-center gap-1.5">
                    <input v-model="dateFrom" type="date"
                        class="h-9 px-3 text-[12.5px] rounded-xl border border-slate-200 dark:border-white/[0.1]
                            bg-white dark:bg-[#09111f] text-slate-700 dark:text-slate-300
                            focus:outline-none focus:ring-2 focus:ring-sky-500/30 w-38" />
                    <span class="text-[11px] text-slate-400">to</span>
                    <input v-model="dateTo" type="date"
                        class="h-9 px-3 text-[12.5px] rounded-xl border border-slate-200 dark:border-white/[0.1]
                            bg-white dark:bg-[#09111f] text-slate-700 dark:text-slate-300
                            focus:outline-none focus:ring-2 focus:ring-sky-500/30 w-38" />
                    <button @click="applyFilters"
                        class="h-9 px-3 rounded-xl text-[12px] font-bold text-white transition-all"
                        style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                        Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- TABLE                                                               -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] overflow-hidden">

            <div v-if="orders.data.length === 0" class="p-12 text-center">
                <Phone class="w-8 h-8 text-slate-300 dark:text-slate-700 mx-auto mb-3" :stroke-width="1.5" />
                <p class="text-[13px] text-slate-400 dark:text-slate-600">No orders found.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-white/[0.05]"
                            style="background:rgba(255,255,255,0.02)">
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400 whitespace-nowrap">#</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">User</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Service</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Country · Carrier</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Phone</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">OTP</th>
                            <th class="px-3 py-2.5 text-right font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Cost</th>
                            <th class="px-3 py-2.5 text-right font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Charged</th>
                            <th class="px-3 py-2.5 text-right font-bold text-[10px] uppercase tracking-wider text-emerald-500 dark:text-emerald-400">Profit</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400">Status</th>
                            <th class="px-3 py-2.5 text-left font-bold text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-400 whitespace-nowrap">Date</th>
                            <th class="px-3 py-2.5 w-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/[0.03]">
                        <tr v-for="order in orders.data" :key="order.id"
                            @click="openDetail(order)"
                            class="hover:bg-slate-50 dark:hover:bg-white/[0.025] transition-colors cursor-pointer group">

                            <!-- # -->
                            <td class="px-3 py-3 font-mono text-[10.5px] text-slate-400 dark:text-slate-600 whitespace-nowrap">
                                #{{ order.id }}
                            </td>

                            <!-- User -->
                            <td class="px-3 py-3 max-w-[140px]">
                                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate text-[12.5px]">{{ order.user.name }}</p>
                                <p class="text-[10.5px] text-slate-400 dark:text-slate-600 truncate">{{ order.user.email }}</p>
                            </td>

                            <!-- Service -->
                            <td class="px-3 py-3 whitespace-nowrap">
                                <p class="font-semibold text-slate-800 dark:text-slate-200 text-[12.5px]">{{ svcLabel(order.service) }}</p>
                                <p class="text-[10px] font-mono text-slate-400 dark:text-slate-600">{{ order.service }}</p>
                            </td>

                            <!-- Country · Carrier -->
                            <td class="px-3 py-3 whitespace-nowrap">
                                <p class="font-semibold text-slate-800 dark:text-slate-200 text-[12.5px]">
                                    {{ countryName(order.country) ?? '—' }}
                                </p>
                                <p class="text-[10.5px] text-slate-400 dark:text-slate-600">
                                    <span class="font-mono text-[10px]">{{ order.country }}</span>
                                    <template v-if="operatorLabel(order.operator)">
                                        · {{ operatorLabel(order.operator) }}
                                    </template>
                                </p>
                            </td>

                            <!-- Phone -->
                            <td class="px-3 py-3 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap text-[12px]">
                                {{ order.phone_number }}
                            </td>

                            <!-- OTP -->
                            <td class="px-3 py-3">
                                <span v-if="order.otp_code"
                                    class="font-mono font-black text-emerald-600 dark:text-emerald-400 text-[13px]">
                                    {{ order.otp_code }}
                                </span>
                                <span v-else class="text-slate-300 dark:text-slate-700 text-[13px]">—</span>
                            </td>

                            <!-- Cost -->
                            <td class="px-3 py-3 text-right font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                ${{ fmt(order.provider_cost, 4) }}
                            </td>

                            <!-- Charged -->
                            <td class="px-3 py-3 text-right font-mono font-bold text-sky-600 dark:text-sky-400 whitespace-nowrap">
                                ${{ fmt(order.amount, 4) }}
                            </td>

                            <!-- Profit -->
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <p class="font-mono font-bold text-[12px]"
                                    :class="order.status === 'FINISHED'
                                        ? (order.profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500')
                                        : 'text-slate-400 dark:text-slate-600'">
                                    <template v-if="order.status === 'FINISHED'">
                                        {{ order.profit >= 0 ? '+' : '' }}${{ fmt(order.profit, 4) }}
                                    </template>
                                    <template v-else>—</template>
                                </p>
                                <p v-if="order.status === 'FINISHED'" class="text-[10px] text-emerald-500/70 dark:text-emerald-400/50 text-right">
                                    {{ order.profit_pct }}%
                                </p>
                                <p v-else-if="order.is_refunded" class="text-[10px] text-orange-400/80">
                                    refunded
                                </p>
                            </td>

                            <!-- Status -->
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="statusColor(order.status)">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="statusDot(order.status)" />
                                        {{ order.status }}
                                    </span>
                                </div>
                                <p v-if="order.sms_count > 0"
                                    class="text-[10px] text-emerald-500 dark:text-emerald-400/70 mt-0.5">
                                    {{ order.sms_count }} SMS
                                </p>
                            </td>

                            <!-- Date -->
                            <td class="px-3 py-3 text-slate-400 dark:text-slate-600 whitespace-nowrap text-[11px]">
                                {{ fmtDateShort(order.created_at) }}
                            </td>

                            <!-- Arrow -->
                            <td class="px-3 py-3">
                                <ChevronRight class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700 group-hover:text-slate-400 dark:group-hover:text-slate-500 transition-colors" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.last_page > 1"
                class="px-4 py-3 border-t border-slate-100 dark:border-white/[0.05] flex items-center justify-between">
                <p class="text-[12px] text-slate-400 dark:text-slate-600">
                    Showing {{ orders.from }}–{{ orders.to }} of {{ orders.total }} orders
                </p>
                <div class="flex gap-1">
                    <Link v-if="orders.prev_page_url" :href="orders.prev_page_url"
                        class="px-3 py-1.5 rounded-lg text-[12px] font-medium
                            bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-400
                            hover:bg-slate-200 dark:hover:bg-white/[0.1] transition-all">
                        ← Prev
                    </Link>
                    <Link v-if="orders.next_page_url" :href="orders.next_page_url"
                        class="px-3 py-1.5 rounded-lg text-[12px] font-medium
                            bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-400
                            hover:bg-slate-200 dark:hover:bg-white/[0.1] transition-all">
                        Next →
                    </Link>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════════ -->
        <!-- ORDER DETAIL SLIDE-OVER                                             -->
        <!-- ════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="selectedOrder"
                    class="fixed inset-0 z-50 flex items-stretch justify-end"
                    @click.self="closeDetail">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDetail" />

                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full">
                        <div v-if="selectedOrder"
                            class="relative w-full max-w-[560px] h-full flex flex-col overflow-hidden shadow-2xl"
                            style="background:#09111f;border-left:1px solid rgba(255,255,255,0.07)">

                            <!-- Panel header -->
                            <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06] flex-shrink-0">
                                <div>
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <p class="text-[15px] font-black text-white">Order #{{ selectedOrder.id }}</p>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                            :class="statusColor(selectedOrder.status)">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(selectedOrder.status)" />
                                            {{ selectedOrder.status }}
                                        </span>
                                        <span v-if="selectedOrder.is_refunded"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold
                                                bg-orange-500/15 text-orange-400 border border-orange-500/25">
                                            <RefreshCw class="w-2.5 h-2.5" />
                                            Refunded
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-mono">
                                        Provider ID: {{ selectedOrder.activation_id }}
                                    </p>
                                </div>
                                <button @click="closeDetail"
                                    class="w-8 h-8 flex items-center justify-center rounded-xl
                                        text-slate-400 hover:text-white hover:bg-white/[0.08] transition-all flex-shrink-0">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Scrollable body -->
                            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-5">

                                <!-- ── SMS status banner ── -->
                                <div v-if="selectedOrder.otp_code"
                                    class="rounded-2xl p-4 text-center"
                                    style="background:linear-gradient(135deg,rgba(16,185,129,0.12),rgba(16,185,129,0.05));border:1px solid rgba(16,185,129,0.25)">
                                    <p class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-400 mb-2">OTP Code Received</p>
                                    <p class="text-[38px] font-black text-white font-mono tracking-[0.2em] leading-none mb-3">
                                        {{ selectedOrder.otp_code }}
                                    </p>
                                    <button @click="copyText(selectedOrder.otp_code, 'otp')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                        :class="copied === 'otp'
                                            ? 'bg-emerald-500/25 text-emerald-300'
                                            : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'">
                                        <Check v-if="copied === 'otp'" class="w-3 h-3" :stroke-width="2.5" />
                                        <Copy v-else class="w-3 h-3" />
                                        {{ copied === 'otp' ? 'Copied!' : 'Copy OTP' }}
                                    </button>
                                </div>

                                <!-- ── USER ── -->
                                <section>
                                    <p class="section-label">User</p>
                                    <div class="detail-card">
                                        <div class="grid grid-cols-3 divide-x divide-white/[0.04]">
                                            <div class="cell">
                                                <p class="cell-label">Name</p>
                                                <p class="cell-value">{{ selectedOrder.user.name }}</p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">User ID</p>
                                                <p class="cell-value font-mono">#{{ selectedOrder.user.id }}</p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">Email</p>
                                                <p class="cell-value text-[11px] break-all">{{ selectedOrder.user.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- ── SERVICE ── -->
                                <section>
                                    <p class="section-label">Service &amp; Number</p>
                                    <div class="detail-card">
                                        <div class="grid grid-cols-2 divide-x divide-white/[0.04]">
                                            <div class="cell">
                                                <p class="cell-label">Service / App</p>
                                                <p class="cell-value">{{ svcLabel(selectedOrder.service) }}</p>
                                                <p class="text-[10px] font-mono text-slate-600 mt-0.5">{{ selectedOrder.service }}</p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">Provider Source</p>
                                                <p class="cell-value">{{ selectedOrder.provider.name }}</p>
                                                <p class="text-[10px] font-mono text-slate-600 mt-0.5">ID #{{ selectedOrder.provider.id }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 divide-x divide-white/[0.04] border-t border-white/[0.04]">
                                            <div class="cell">
                                                <p class="cell-label">Country Selected</p>
                                                <p class="cell-value">
                                                    {{ countryName(selectedOrder.country) ?? '(any / auto)' }}
                                                </p>
                                                <p class="text-[10px] font-mono text-slate-600 mt-0.5">
                                                    code: {{ selectedOrder.country }}
                                                </p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">Carrier / Operator</p>
                                                <p class="cell-value">
                                                    {{ operatorLabel(selectedOrder.operator) ?? '(any / auto)' }}
                                                </p>
                                                <p class="text-[10px] font-mono text-slate-600 mt-0.5">
                                                    {{ selectedOrder.operator }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="border-t border-white/[0.04] px-3.5 py-3">
                                            <p class="cell-label">Phone Number</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <p class="text-[18px] font-black text-white font-mono tracking-wider">
                                                    {{ selectedOrder.phone_number }}
                                                </p>
                                                <button @click="copyText(selectedOrder.phone_number, 'phone')"
                                                    class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold transition-all"
                                                    :class="copied === 'phone'
                                                        ? 'bg-emerald-500/15 text-emerald-400'
                                                        : 'bg-white/[0.06] text-slate-400 hover:bg-white/[0.1]'">
                                                    <Check v-if="copied === 'phone'" class="w-3 h-3" :stroke-width="2.5" />
                                                    <Copy v-else class="w-3 h-3" />
                                                    {{ copied === 'phone' ? 'Copied!' : 'Copy' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- ── FINANCIAL BREAKDOWN ── -->
                                <section>
                                    <p class="section-label">Financial Breakdown</p>
                                    <div class="detail-card">
                                        <div class="grid grid-cols-3 divide-x divide-white/[0.04]">
                                            <div class="cell text-center">
                                                <p class="cell-label">Provider Cost</p>
                                                <p class="text-[16px] font-black text-slate-300 font-mono tabular-nums mt-1">
                                                    ${{ fmt(selectedOrder.provider_cost, 4) }}
                                                </p>
                                            </div>
                                            <div class="cell text-center">
                                                <p class="cell-label">Markup</p>
                                                <p class="text-[16px] font-black text-amber-400 font-mono tabular-nums mt-1">
                                                    {{ fmt(selectedOrder.markup_percent, 2) }}%
                                                </p>
                                            </div>
                                            <div class="cell text-center">
                                                <p class="cell-label">Charged to User</p>
                                                <p class="text-[16px] font-black text-sky-400 font-mono tabular-nums mt-1">
                                                    ${{ fmt(selectedOrder.amount, 4) }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Profit row -->
                                        <div class="px-4 py-3 border-t border-white/[0.04] flex items-center justify-between"
                                            :style="selectedOrder.status === 'FINISHED'
                                                ? 'background:rgba(16,185,129,0.06)'
                                                : 'background:rgba(255,255,255,0.02)'">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <TrendingUp class="w-3.5 h-3.5"
                                                        :class="selectedOrder.status === 'FINISHED' ? 'text-emerald-400' : 'text-slate-500'" />
                                                    <p class="text-[11px] font-bold"
                                                        :class="selectedOrder.status === 'FINISHED' ? 'text-emerald-400' : 'text-slate-500'">
                                                        {{ selectedOrder.status === 'FINISHED' ? 'Profit on this order' : 'Profit (unrealized)' }}
                                                    </p>
                                                </div>
                                                <p v-if="selectedOrder.is_refunded"
                                                    class="text-[10px] text-orange-400 mt-0.5 ml-5">
                                                    Order was cancelled &amp; refunded — not counted as revenue
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[16px] font-black font-mono tabular-nums"
                                                    :class="selectedOrder.status === 'FINISHED'
                                                        ? (selectedOrder.profit >= 0 ? 'text-emerald-400' : 'text-rose-400')
                                                        : 'text-slate-500'">
                                                    {{ selectedOrder.profit >= 0 ? '+' : '' }}${{ fmt(selectedOrder.profit, 4) }}
                                                </p>
                                                <p v-if="selectedOrder.status === 'FINISHED'"
                                                    class="text-[10.5px] text-emerald-400/60 font-mono">
                                                    {{ selectedOrder.profit_pct }}% margin
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Balance snapshot -->
                                        <div class="grid grid-cols-2 divide-x divide-white/[0.04] border-t border-white/[0.04]">
                                            <div class="cell">
                                                <p class="cell-label">Balance Before</p>
                                                <p class="cell-value font-mono">${{ fmt(selectedOrder.balance_before, 4) }}</p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">Balance After</p>
                                                <p class="cell-value font-mono">${{ fmt(selectedOrder.balance_after, 4) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- ── TIMESTAMPS / TIMELINE ── -->
                                <section>
                                    <p class="section-label">Status Timeline</p>
                                    <div class="detail-card">
                                        <div class="grid grid-cols-2 divide-x divide-white/[0.04]">
                                            <div class="cell">
                                                <p class="cell-label">Internal Order ID</p>
                                                <p class="cell-value font-mono">#{{ selectedOrder.id }}</p>
                                            </div>
                                            <div class="cell">
                                                <p class="cell-label">Provider Order ID</p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <p class="cell-value font-mono truncate text-[11px]">{{ selectedOrder.activation_id }}</p>
                                                    <button @click="copyText(selectedOrder.activation_id, 'actid')"
                                                        class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-md
                                                            text-slate-500 hover:text-slate-300 transition-all">
                                                        <Check v-if="copied === 'actid'" class="w-3 h-3 text-emerald-400" :stroke-width="2.5" />
                                                        <Copy v-else class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border-t border-white/[0.04] divide-y divide-white/[0.04]">
                                            <div class="flex items-center justify-between px-3.5 py-2.5 gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 flex-shrink-0"></span>
                                                    <p class="text-[11px] text-slate-400">Purchased</p>
                                                </div>
                                                <p class="text-[11.5px] font-bold text-white">{{ fmtDate(selectedOrder.created_at) }}</p>
                                            </div>

                                            <div v-if="selectedOrder.sms_received_at"
                                                class="flex items-center justify-between px-3.5 py-2.5 gap-2"
                                                style="background:rgba(16,185,129,0.04)">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                                    <p class="text-[11px] text-emerald-400">SMS Received</p>
                                                </div>
                                                <p class="text-[11.5px] font-bold text-emerald-300">{{ fmtDate(selectedOrder.sms_received_at) }}</p>
                                            </div>

                                            <div class="flex items-center justify-between px-3.5 py-2.5 gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                                    <p class="text-[11px] text-slate-400">Expires</p>
                                                </div>
                                                <p class="text-[11.5px] font-bold text-white">{{ fmtDate(selectedOrder.expires_at) }}</p>
                                            </div>

                                            <div v-if="selectedOrder.completed_at"
                                                class="flex items-center justify-between px-3.5 py-2.5 gap-2"
                                                :style="selectedOrder.is_refunded ? 'background:rgba(249,115,22,0.05)' : ''">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                        :class="selectedOrder.is_refunded ? 'bg-orange-400' : 'bg-emerald-400'" />
                                                    <p class="text-[11px]"
                                                        :class="selectedOrder.is_refunded ? 'text-orange-400' : 'text-slate-400'">
                                                        {{ selectedOrder.is_refunded ? 'Cancelled &amp; Refunded' : 'Completed' }}
                                                    </p>
                                                </div>
                                                <p class="text-[11.5px] font-bold"
                                                    :class="selectedOrder.is_refunded ? 'text-orange-300' : 'text-white'">
                                                    {{ fmtDate(selectedOrder.completed_at) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- ── SMS MESSAGES ── -->
                                <section v-if="selectedOrder.sms_messages?.length">
                                    <p class="section-label">SMS History ({{ selectedOrder.sms_messages.length }})</p>
                                    <div class="space-y-2">
                                        <div v-for="(msg, i) in selectedOrder.sms_messages" :key="msg.id"
                                            class="rounded-xl px-3.5 py-3"
                                            style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06)">
                                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[9px] font-black text-slate-600">#{{ i + 1 }}</span>
                                                    <span class="text-[11px] font-bold text-slate-300">{{ msg.sender ?? 'Unknown Sender' }}</span>
                                                    <span v-if="msg.code"
                                                        class="px-1.5 py-0.5 rounded-md text-[10px] font-black
                                                            bg-emerald-500/15 text-emerald-400">
                                                        OTP: {{ msg.code }}
                                                    </span>
                                                </div>
                                                <span class="text-[9.5px] text-slate-600 flex-shrink-0 whitespace-nowrap">
                                                    {{ fmtDate(msg.received_at) }}
                                                </span>
                                            </div>
                                            <p class="text-[12px] text-slate-300 leading-relaxed">{{ msg.message }}</p>
                                        </div>
                                    </div>
                                </section>

                                <!-- SMS waiting -->
                                <section v-else-if="['PENDING','RECEIVED'].includes(selectedOrder.status)">
                                    <div class="rounded-xl px-4 py-3.5 flex items-center gap-3"
                                        style="background:rgba(14,165,233,0.06);border:1px solid rgba(14,165,233,0.15)">
                                        <Clock class="w-4 h-4 text-sky-400 flex-shrink-0 animate-spin" style="animation-duration:3s" />
                                        <p class="text-[12.5px] font-bold text-sky-400">Waiting for SMS…</p>
                                    </div>
                                </section>

                                <!-- No SMS received -->
                                <section v-else>
                                    <p class="section-label">SMS</p>
                                    <div class="rounded-xl px-4 py-3.5 flex items-center gap-3"
                                        style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05)">
                                        <AlertCircle class="w-4 h-4 text-slate-500 flex-shrink-0" />
                                        <p class="text-[12.5px] text-slate-500">No SMS was received for this order.</p>
                                    </div>
                                </section>

                                <!-- ── RAW PROVIDER RESPONSE ── -->
                                <section v-if="selectedOrder.raw_response">
                                    <button @click="showRawJson = !showRawJson"
                                        class="flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em]
                                            text-slate-500 hover:text-slate-300 transition-colors mb-2.5">
                                        Raw Provider Response
                                        <span class="text-[10px] normal-case tracking-normal">{{ showRawJson ? '▲ hide' : '▼ show' }}</span>
                                    </button>
                                    <div v-if="showRawJson"
                                        class="rounded-xl p-3.5 overflow-x-auto text-[11px] font-mono text-slate-400 leading-relaxed"
                                        style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.05);max-height:300px">
                                        <pre>{{ JSON.stringify(selectedOrder.raw_response, null, 2) }}</pre>
                                    </div>
                                </section>

                            </div><!-- end scrollable body -->
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>

<style scoped>
.section-label {
    @apply text-[9px] font-black uppercase tracking-[0.18em] text-slate-500 mb-2.5;
}
.detail-card {
    @apply rounded-xl border border-white/[0.06] overflow-hidden;
}
.cell {
    @apply px-3.5 py-3;
}
.cell-label {
    @apply text-[9.5px] text-slate-500 mb-0.5;
}
.cell-value {
    @apply text-[12.5px] font-bold text-white;
}
</style>

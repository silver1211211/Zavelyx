<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    ArrowUpRight,
    Bell,
    BarChart2,
    CheckCircle2,
    Circle,
    Clock,
    DollarSign,
    Loader2,
    Megaphone,
    RefreshCw,
    Server,
    ShoppingCart,
    Terminal,
    TrendingUp,
    UserPlus,
    Users,
    Wifi,
    WifiOff,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    stats:             { type: Object, default: () => ({}) },
    notifStats:        { type: Object, default: () => ({}) },
    recentBroadcasts:  { type: Array,  default: () => [] },
    recentOrders:      { type: Array,  default: () => [] },
    recentUsers:       { type: Array,  default: () => [] },
    weeklyOrders:      { type: Array,  default: () => [] },
    monthlyRevenue:    { type: Array,  default: () => [] },
    providers:         { type: Array,  default: () => [] },
    liveOrders:        { type: Array,  default: () => [] },
    depositStats:      { type: Object, default: () => ({}) },
    depositDaily:      { type: Array,  default: () => [] },
});

// Animated counters
const displayStats = ref({
    totalUsers: 0,
    totalOrders: 0,
    pendingOrders: 0,
    revenue: 0,
    activeServices: 0,
    newUsersToday: 0,
});

const refreshing = ref(false);
const lastRefresh = ref(new Date());
let refreshTimer = null;

function refresh() {
    refreshing.value = true;
    router.reload({ only: ['stats', 'providers', 'liveOrders', 'recentOrders'], preserveState: true, preserveScroll: true, onFinish: () => {
        refreshing.value = false;
        lastRefresh.value = new Date();
    }});
}

onMounted(() => {
    const duration = 1400;
    const start = Date.now();
    const targets = props.stats;

    const tick = () => {
        const elapsed = Date.now() - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        for (const key in targets) {
            if (key in displayStats.value) {
                displayStats.value[key] = targets[key] * eased;
            }
        }

        if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    refreshTimer = setInterval(refresh, 60_000);
});
onUnmounted(() => clearInterval(refreshTimer));

// Stat cards
const statCards = computed(() => [
    {
        label: 'Total Users',
        value: Math.round(displayStats.value.totalUsers),
        raw: props.stats.totalUsers ?? 0,
        icon: Users,
        color: 'sky',
        iconBg: 'bg-sky-500/10 dark:bg-sky-500/15',
        iconColor: 'text-sky-600 dark:text-sky-400',
        border: 'border-sky-500/15',
        trend: '+' + (props.stats.newUsersToday ?? 0) + ' today',
    },
    {
        label: 'Total Orders',
        value: Math.round(displayStats.value.totalOrders),
        raw: props.stats.totalOrders ?? 0,
        icon: ShoppingCart,
        color: 'violet',
        iconBg: 'bg-violet-500/10 dark:bg-violet-500/15',
        iconColor: 'text-violet-600 dark:text-violet-400',
        border: 'border-violet-500/15',
        trend: (props.stats.completedOrders ?? 0) + ' completed',
    },
    {
        label: 'Pending Orders',
        value: Math.round(displayStats.value.pendingOrders),
        raw: props.stats.pendingOrders ?? 0,
        icon: Clock,
        color: 'amber',
        iconBg: 'bg-amber-500/10 dark:bg-amber-500/15',
        iconColor: 'text-amber-600 dark:text-amber-400',
        border: 'border-amber-500/15',
        trend: (props.stats.processingOrders ?? 0) + ' processing',
    },
    {
        label: 'Total Revenue',
        value: displayStats.value.revenue,
        raw: props.stats.revenue ?? 0,
        icon: DollarSign,
        color: 'emerald',
        iconBg: 'bg-emerald-500/10 dark:bg-emerald-500/15',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        border: 'border-emerald-500/15',
        isMoney: true,
        trend: 'All time',
    },
    {
        label: 'Active Services',
        value: Math.round(displayStats.value.activeServices),
        raw: props.stats.activeServices ?? 0,
        icon: Zap,
        color: 'blue',
        iconBg: 'bg-blue-500/10 dark:bg-blue-500/15',
        iconColor: 'text-blue-600 dark:text-blue-400',
        border: 'border-blue-500/15',
        trend: 'SMM services',
    },
    {
        label: 'API Requests',
        value: 0,
        raw: 0,
        icon: Terminal,
        color: 'pink',
        iconBg: 'bg-pink-500/10 dark:bg-pink-500/15',
        iconColor: 'text-pink-600 dark:text-pink-400',
        border: 'border-pink-500/15',
        trend: 'Today',
    },
]);

function formatMoney(val) {
    return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatStatValue(card) {
    if (card.isMoney) return formatMoney(card.value);
    return Math.round(card.value).toLocaleString();
}

// Weekly chart bar heights
const maxWeeklyOrders = computed(() => Math.max(...props.weeklyOrders.map(d => d.count), 1));
const maxMonthlyRevenue = computed(() => Math.max(...props.monthlyRevenue.map(d => d.revenue), 1));

function barHeight(value, max) {
    return Math.max(4, Math.round((value / max) * 100));
}

// Order status
const statusConfig = {
    pending:    { label: 'Pending',    class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400' },
    processing: { label: 'Processing', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400' },
    completed:  { label: 'Completed',  class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' },
    cancelled:  { label: 'Cancelled',  class: 'bg-rose-500/10 text-rose-500' },
    failed:     { label: 'Failed',     class: 'bg-red-500/10 text-red-500' },
};

function statusBadge(status) {
    return statusConfig[status] ?? { label: status, class: 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400' };
}

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function timeAgo(iso) {
    if (!iso) return 'Never';
    const sec = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (sec < 60) return sec + 's ago';
    if (sec < 3600) return Math.floor(sec / 60) + 'm ago';
    if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
    return Math.floor(sec / 86400) + 'd ago';
}

const lastRefreshDisplay = computed(() => {
    const d = lastRefresh.value;
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
});

const activeProviders = computed(() => props.providers.filter(p => p.is_active).length);
const totalProviders  = computed(() => props.providers.length);
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <!-- Page header -->
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Overview</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Admin Dashboard</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden sm:block text-[11px] text-slate-400 dark:text-slate-600">Updated {{ lastRefreshDisplay }}</span>
                <button @click="refresh" :disabled="refreshing"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-white/[0.07]
                        bg-white dark:bg-[#0d1e35] text-[12px] font-semibold text-slate-600 dark:text-slate-300
                        hover:border-sky-300 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-400
                        active:scale-95 transition-all disabled:opacity-50">
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': refreshing }" />
                    Refresh
                </button>
                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-500/8 border border-emerald-500/20 rounded-xl text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Live
                </div>
            </div>
        </div>

        <!-- Stat cards (6 grid) -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            <div
                v-for="card in statCards"
                :key="card.label"
                :class="['bg-white dark:bg-[#0d1e35] rounded-2xl border p-4 hover:shadow-md transition-all', card.border]"
            >
                <div :class="['w-9 h-9 rounded-xl flex items-center justify-center mb-3', card.iconBg]">
                    <component :is="card.icon" :class="['w-4 h-4', card.iconColor]" />
                </div>
                <p class="text-[22px] font-black text-slate-900 dark:text-white leading-none">
                    {{ formatStatValue(card) }}
                </p>
                <p class="text-[12px] font-medium text-slate-500 dark:text-slate-400 mt-1">{{ card.label }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ card.trend }}</p>
            </div>
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">

            <!-- Weekly orders bar chart -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Orders This Week</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Daily order volume</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-[12px] font-semibold text-sky-500">
                        <TrendingUp class="w-3.5 h-3.5" />
                        <span>{{ stats.totalOrders ?? 0 }} total</span>
                    </div>
                </div>

                <div class="flex items-end gap-2 h-32">
                    <template v-if="weeklyOrders.length > 0">
                        <div
                            v-for="day in weeklyOrders"
                            :key="day.date"
                            class="flex-1 flex flex-col items-center gap-1.5"
                        >
                            <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                {{ day.count > 0 ? day.count : '' }}
                            </span>
                            <div
                                class="w-full rounded-t-lg transition-all duration-700 ease-out"
                                :style="{ height: barHeight(day.count, maxWeeklyOrders) + '%' }"
                                :class="day.count > 0 ? 'bg-sky-500 dark:bg-sky-500' : 'bg-slate-100 dark:bg-white/5'"
                            ></div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-600">{{ day.date }}</span>
                        </div>
                    </template>
                    <div v-else class="flex-1 flex items-center justify-center text-[12px] text-slate-400 dark:text-slate-600">
                        No data yet
                    </div>
                </div>
            </div>

            <!-- Monthly revenue bar chart -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Revenue (6 Months)</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Deposit volume by month</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-[12px] font-semibold text-emerald-500">
                        <DollarSign class="w-3.5 h-3.5" />
                        <span>{{ formatMoney(stats.revenue ?? 0) }}</span>
                    </div>
                </div>

                <div class="flex items-end gap-2 h-32">
                    <template v-if="monthlyRevenue.length > 0">
                        <div
                            v-for="month in monthlyRevenue"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center gap-1.5"
                        >
                            <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-400">
                                {{ month.revenue > 0 ? '$' + Math.round(month.revenue) : '' }}
                            </span>
                            <div
                                class="w-full rounded-t-lg transition-all duration-700 ease-out"
                                :style="{ height: barHeight(month.revenue, maxMonthlyRevenue) + '%' }"
                                :class="month.revenue > 0 ? 'bg-emerald-500 dark:bg-emerald-500' : 'bg-slate-100 dark:bg-white/5'"
                            ></div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-600">{{ month.month }}</span>
                        </div>
                    </template>
                    <div v-else class="flex-1 flex items-center justify-center text-[12px] text-slate-400 dark:text-slate-600">
                        No data yet
                    </div>
                </div>
            </div>
        </div>

        <!-- Live monitoring row: provider health + live queue + alert cards -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">

            <!-- Provider health -->
            <div class="xl:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-sky-500/8">
                    <div class="flex items-center gap-2">
                        <Server class="w-4 h-4 text-slate-400 dark:text-slate-400" />
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Provider Health</h2>
                    </div>
                    <span class="text-[12px] font-medium text-slate-400 dark:text-slate-600">
                        {{ activeProviders }}/{{ totalProviders }} active
                    </span>
                </div>

                <div v-if="providers.length === 0" class="py-10 flex flex-col items-center gap-2 text-center">
                    <Server class="w-7 h-7 text-slate-300 dark:text-slate-700" />
                    <p class="text-[12px] text-slate-400 dark:text-slate-600">No providers configured.</p>
                </div>

                <div v-else class="divide-y divide-slate-100 dark:divide-sky-500/6">
                    <div v-for="p in providers" :key="p.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-white/2 transition-colors">
                        <!-- Status dot -->
                        <div :class="['w-2.5 h-2.5 rounded-full flex-shrink-0 ring-4',
                            p.is_active
                                ? 'bg-emerald-500 ring-emerald-500/15 animate-pulse'
                                : 'bg-slate-300 dark:bg-slate-600 ring-slate-200/50 dark:ring-white/5']" />
                        <!-- Name + last sync -->
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ p.name }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-600">
                                {{ p.services_count }} services · Synced {{ timeAgo(p.last_synced_at) }}
                            </p>
                        </div>
                        <!-- Balance -->
                        <div v-if="p.balance !== null" class="hidden sm:block text-right flex-shrink-0">
                            <p class="text-[12px] font-bold font-mono text-slate-700 dark:text-slate-300">
                                ${{ Number(p.balance).toFixed(4) }}
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600">API balance</p>
                        </div>
                        <!-- Priority badge -->
                        <span class="hidden sm:flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                            bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 flex-shrink-0">
                            P{{ p.priority ?? '—' }}
                        </span>
                        <!-- Status badge -->
                        <span :class="['px-2.5 py-1 rounded-xl text-[11px] font-bold flex-shrink-0',
                            p.is_active
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400']">
                            {{ p.is_active ? 'Active' : 'Offline' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Alert / queue panel -->
            <div class="space-y-3">

                <!-- Pending refunds alert -->
                <div :class="['rounded-2xl border p-4',
                    stats.pendingRefunds > 0
                        ? 'bg-amber-50 dark:bg-amber-500/6 border-amber-500/25'
                        : 'bg-white dark:bg-[#0d1e35] border-slate-200 dark:border-sky-500/12']">
                    <div class="flex items-center gap-3 mb-1">
                        <AlertTriangle :class="['w-4 h-4 flex-shrink-0',
                            stats.pendingRefunds > 0 ? 'text-amber-500' : 'text-slate-400 dark:text-slate-600']" />
                        <p class="text-[13px] font-bold text-slate-900 dark:text-white">Pending Refunds</p>
                    </div>
                    <p :class="['text-[28px] font-black leading-none mt-1',
                        stats.pendingRefunds > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-200']">
                        {{ stats.pendingRefunds ?? 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-1">
                        {{ stats.pendingRefunds > 0 ? 'Orders awaiting refund processing' : 'All refunds processed' }}
                    </p>
                </div>

                <!-- Failed today -->
                <div :class="['rounded-2xl border p-4',
                    stats.failedToday > 0
                        ? 'bg-rose-50 dark:bg-rose-500/6 border-rose-500/25'
                        : 'bg-white dark:bg-[#0d1e35] border-slate-200 dark:border-sky-500/12']">
                    <div class="flex items-center gap-3 mb-1">
                        <WifiOff :class="['w-4 h-4 flex-shrink-0',
                            stats.failedToday > 0 ? 'text-rose-500' : 'text-slate-400 dark:text-slate-600']" />
                        <p class="text-[13px] font-bold text-slate-900 dark:text-white">Failed Today</p>
                    </div>
                    <p :class="['text-[28px] font-black leading-none mt-1',
                        stats.failedToday > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200']">
                        {{ stats.failedToday ?? 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-1">
                        {{ stats.failedToday > 0 ? 'Orders failed since midnight' : 'No failures today' }}
                    </p>
                </div>

                <!-- Live queue -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-sky-500/8">
                        <div class="flex items-center gap-2">
                            <Activity class="w-3.5 h-3.5 text-sky-500" />
                            <h3 class="text-[12px] font-bold text-slate-900 dark:text-white">Live Queue</h3>
                        </div>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full
                            bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            {{ liveOrders.length }}
                        </span>
                    </div>
                    <div v-if="liveOrders.length === 0" class="py-6 text-center text-[12px] text-slate-400 dark:text-slate-600">
                        No active orders
                    </div>
                    <div v-else class="divide-y divide-slate-100 dark:divide-sky-500/6 max-h-[200px] overflow-y-auto">
                        <div v-for="o in liveOrders" :key="o.id"
                            class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/2 transition-colors">
                            <span :class="['w-2 h-2 rounded-full flex-shrink-0',
                                o.status === 'processing' ? 'bg-blue-500 animate-pulse' : 'bg-amber-400']" />
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 truncate">
                                    {{ o.user ?? 'User' }}
                                </p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-600 truncate">
                                    {{ o.service ?? 'Service' }}
                                </p>
                            </div>
                            <span class="text-[11px] font-bold font-mono text-slate-600 dark:text-slate-400 flex-shrink-0">
                                ${{ Number(o.amount).toFixed(4) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Orders table + Recent users -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

            <!-- Recent orders table -->
            <div class="xl:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-sky-500/8">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Recent Orders</h2>
                    <span class="text-[12px] font-medium text-sky-500">View all →</span>
                </div>

                <div v-if="recentOrders.length === 0" class="py-12 flex flex-col items-center gap-3 text-center">
                    <ShoppingCart class="w-8 h-8 text-slate-300 dark:text-slate-700" />
                    <p class="text-[12px] text-slate-400 dark:text-slate-600">No orders yet.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">User</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Service</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Amount</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/6">
                            <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50 dark:hover:bg-white/3 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ order.user?.name ?? '—' }}</p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ order.user?.email ?? '' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-400 max-w-[140px] truncate">
                                    {{ order.service?.name ?? 'SMM Service' }}
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    ${{ Number(order.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold', statusBadge(order.status).class]">
                                        {{ statusBadge(order.status).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-400 dark:text-slate-600">{{ formatDate(order.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent users + activity -->
            <div class="space-y-4">

                <!-- Recent users -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 dark:border-sky-500/8">
                        <h2 class="text-[13px] font-bold text-slate-900 dark:text-white">New Users</h2>
                        <span class="text-[11px] font-medium text-sky-500">{{ stats.totalUsers ?? 0 }} total</span>
                    </div>

                    <div v-if="recentUsers.length === 0" class="py-8 flex flex-col items-center gap-2 text-center">
                        <Users class="w-6 h-6 text-slate-300 dark:text-slate-700" />
                        <p class="text-[12px] text-slate-400 dark:text-slate-600">No users yet.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-sky-500/8">
                        <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/3 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                                {{ user.name?.[0]?.toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ user.name }}</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-600 truncate">{{ user.email }}</p>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600 flex-shrink-0">{{ formatDate(user.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <h2 class="text-[13px] font-bold text-slate-900 dark:text-white mb-3">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="/admin/users" class="flex flex-col items-center gap-2 p-3 rounded-xl bg-sky-500/8 hover:bg-sky-500/15 border border-sky-500/15 transition-all group">
                            <Users class="w-4.5 h-4.5 text-sky-500" />
                            <span class="text-[11px] font-semibold text-sky-600 dark:text-sky-400">Users</span>
                        </a>
                        <a href="/admin/currency" class="flex flex-col items-center gap-2 p-3 rounded-xl bg-emerald-500/8 hover:bg-emerald-500/15 border border-emerald-500/15 transition-all">
                            <DollarSign class="w-4.5 h-4.5 text-emerald-500" />
                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Currency</span>
                        </a>
                        <a href="/admin/services" class="flex flex-col items-center gap-2 p-3 rounded-xl bg-violet-500/8 hover:bg-violet-500/15 border border-violet-500/15 transition-all">
                            <Zap class="w-4.5 h-4.5 text-violet-500" />
                            <span class="text-[11px] font-semibold text-violet-600 dark:text-violet-400">Services</span>
                        </a>
                        <a href="/admin/orders" class="flex flex-col items-center gap-2 p-3 rounded-xl bg-amber-500/8 hover:bg-amber-500/15 border border-amber-500/15 transition-all">
                            <ShoppingCart class="w-4.5 h-4.5 text-amber-500" />
                            <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">Orders</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Deposit Analytics ────────────────────────────────────────────── -->
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(14,165,233,0.12))">
                        <DollarSign class="w-3.5 h-3.5 text-emerald-500" />
                    </div>
                    Deposit Analytics
                </h2>
                <a href="/admin/payments" class="text-[12px] font-semibold text-sky-500 hover:text-sky-400 transition-colors">View All →</a>
            </div>

            <!-- Key deposit stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[11px] text-slate-400 dark:text-slate-400 mb-1">Total</p>
                    <p class="text-[22px] font-black text-slate-900 dark:text-white">{{ (depositStats.total ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-500/8 rounded-2xl border border-emerald-500/20 p-4">
                    <p class="text-[11px] text-emerald-500 mb-1">Successful</p>
                    <p class="text-[22px] font-black text-emerald-600 dark:text-emerald-400">{{ (depositStats.successful ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/8 rounded-2xl border border-amber-500/20 p-4">
                    <p class="text-[11px] text-amber-500 mb-1">Pending</p>
                    <p class="text-[22px] font-black text-amber-600 dark:text-amber-400">{{ (depositStats.pending ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-rose-50 dark:bg-rose-500/8 rounded-2xl border border-rose-500/20 p-4">
                    <p class="text-[11px] text-rose-500 mb-1">Failed</p>
                    <p class="text-[22px] font-black text-rose-600 dark:text-rose-400">{{ (depositStats.failed ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-sky-50 dark:bg-sky-500/8 rounded-2xl border border-sky-500/20 p-4">
                    <p class="text-[11px] text-sky-500 mb-1">Volume</p>
                    <p class="text-[20px] font-black text-sky-600 dark:text-sky-400">${{ Number(depositStats.totalVolume ?? 0).toFixed(2) }}</p>
                </div>
                <div :class="[(depositStats.successRate ?? 0) >= 90 ? 'bg-emerald-50 dark:bg-emerald-500/8 border-emerald-500/20' : 'bg-amber-50 dark:bg-amber-500/8 border-amber-500/20', 'rounded-2xl border p-4']">
                    <p :class="[(depositStats.successRate ?? 0) >= 90 ? 'text-emerald-500' : 'text-amber-500', 'text-[11px] mb-1']">Success Rate</p>
                    <p :class="[(depositStats.successRate ?? 0) >= 90 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400', 'text-[22px] font-black']">{{ depositStats.successRate ?? 0 }}%</p>
                </div>
            </div>

            <!-- Failed callbacks alert + daily mini-chart -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Failed callbacks -->
                <div v-if="(depositStats.failedCallbacks ?? 0) > 0"
                    class="bg-rose-50 dark:bg-rose-500/[0.06] rounded-2xl border border-rose-200 dark:border-rose-500/20 p-4 flex items-start gap-3">
                    <AlertTriangle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-[13px] font-bold text-rose-700 dark:text-rose-400">{{ depositStats.failedCallbacks }} Unprocessed Deposit(s)</p>
                        <p class="text-[11.5px] text-rose-600/80 dark:text-rose-400/70 mt-0.5">Payment confirmed by gateway but wallet not credited. Use retry or manual approve.</p>
                        <a href="/admin/payments?status=completed"
                            class="mt-2 inline-flex items-center gap-1 text-[11.5px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            Fix now →
                        </a>
                    </div>
                </div>
                <div v-else
                    class="bg-emerald-50 dark:bg-emerald-500/[0.06] rounded-2xl border border-emerald-200 dark:border-emerald-500/20 p-4 flex items-center gap-3">
                    <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                    <div>
                        <p class="text-[13px] font-bold text-emerald-700 dark:text-emerald-400">All Deposits Processed</p>
                        <p class="text-[11.5px] text-emerald-600/80 dark:text-emerald-400/70 mt-0.5">No unprocessed confirmed deposits.</p>
                    </div>
                </div>

                <!-- Daily deposit bar chart -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wide mb-3">Last 7 Days</p>
                    <div class="flex items-end gap-1 h-12">
                        <div v-for="d in depositDaily" :key="d.date"
                            class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full bg-sky-400 dark:bg-sky-500 rounded-sm transition-all"
                                :style="{ height: Math.max(2, (d.count / Math.max(...depositDaily.map(x => x.count), 1)) * 40) + 'px' }"
                                :title="`${d.date}: ${d.count} deposits, $${d.volume.toFixed(2)}`">
                            </div>
                            <span class="text-[9px] text-slate-400 dark:text-slate-600">{{ d.date }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Notification Campaign Overview ───────────────────────────────── -->
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.12))">
                        <Megaphone class="w-3.5 h-3.5 text-sky-500" />
                    </div>
                    Notification Campaigns
                </h2>
                <a href="/admin/notifications" class="text-[12px] font-semibold text-sky-500 hover:text-sky-400 transition-colors">Manage →</a>
            </div>

            <!-- Notification stat cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-4">
                <div class="lg:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <Megaphone class="w-3.5 h-3.5 text-slate-400" />
                        <p class="text-[11px] text-slate-400 dark:text-slate-400">Campaigns Sent</p>
                    </div>
                    <p class="text-[22px] font-black text-slate-900 dark:text-white">{{ notifStats.totalBroadcasts ?? 0 }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ notifStats.activeCampaigns ?? 0 }} this month</p>
                </div>
                <div class="bg-violet-50 dark:bg-violet-500/8 rounded-2xl border border-violet-500/20 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <Users class="w-3.5 h-3.5 text-violet-400" />
                        <p class="text-[11px] text-violet-500">Delivered</p>
                    </div>
                    <p class="text-[22px] font-black text-violet-600 dark:text-violet-400">{{ (notifStats.delivered ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-sky-50 dark:bg-sky-500/8 rounded-2xl border border-sky-500/20 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <Bell class="w-3.5 h-3.5 text-sky-400" />
                        <p class="text-[11px] text-sky-500">Total Reads</p>
                    </div>
                    <p class="text-[22px] font-black text-sky-600 dark:text-sky-400">{{ (notifStats.read ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/8 rounded-2xl border border-amber-500/20 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <Bell class="w-3.5 h-3.5 text-amber-400" />
                        <p class="text-[11px] text-amber-500">Unread</p>
                    </div>
                    <p class="text-[22px] font-black text-amber-600 dark:text-amber-400">{{ (notifStats.unread ?? 0).toLocaleString() }}</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-500/8 rounded-2xl border border-emerald-500/20 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <BarChart2 class="w-3.5 h-3.5 text-emerald-400" />
                        <p class="text-[11px] text-emerald-500">Read Rate</p>
                    </div>
                    <p class="text-[22px] font-black" :class="(notifStats.read_rate ?? 0) >= 50 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'">{{ notifStats.read_rate ?? 0 }}%</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/8 rounded-2xl border border-amber-500/20 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <Clock class="w-3.5 h-3.5 text-amber-400" />
                        <p class="text-[11px] text-amber-500">Scheduled</p>
                    </div>
                    <p class="text-[22px] font-black text-amber-600 dark:text-amber-400">{{ notifStats.scheduled ?? 0 }}</p>
                </div>
                <div v-if="notifStats.topBroadcast" class="lg:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <TrendingUp class="w-3.5 h-3.5 text-slate-400" />
                        <p class="text-[11px] text-slate-400 dark:text-slate-400">Top Campaign</p>
                    </div>
                    <p class="text-[12px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ notifStats.topBroadcast.title }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ (notifStats.topBroadcast.recipients_count ?? 0).toLocaleString() }} recipients</p>
                </div>
            </div>

            <!-- Recent broadcasts -->
            <div v-if="recentBroadcasts.length > 0" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-sky-500/8">
                    <h3 class="text-[13px] font-bold text-slate-900 dark:text-white">Recent Campaigns</h3>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-sky-500/6">
                    <div v-for="b in recentBroadcasts" :key="b.id"
                        class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/2 transition-colors">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.08))">
                            <Megaphone class="w-4 h-4 text-sky-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ b.title }}</p>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600">
                                {{ (b.recipients_count ?? 0).toLocaleString() }} recipients · {{ b.creator ?? 'System Admin' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            <div class="text-right hidden sm:block">
                                <p class="text-[11px] font-bold text-sky-500">{{ b.read ?? 0 }} reads</p>
                            </div>
                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 hidden md:block">{{ formatDate(b.sent_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

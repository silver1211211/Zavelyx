<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle, ChevronLeft, ChevronRight, CircleCheck,
    Clock, ExternalLink, RefreshCw, Search, ShoppingCart, X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    orders:       Object,
    search:       { type: String, default: '' },
    statusFilter: { type: String, default: '' },
    statusCounts: Object,
});

const flash  = computed(() => usePage().props.flash ?? {});
const errors = computed(() => usePage().props.errors ?? {});

// ── Filters ───────────────────────────────────────────────────────────────────
const searchQuery  = ref(props.search);
const activeStatus = ref(props.statusFilter);

let searchTimer = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilter(), 350);
});

function applyFilter(status = activeStatus.value) {
    activeStatus.value = status;
    router.get(route('admin.orders.index'), {
        search: searchQuery.value,
        status: status,
    }, { preserveState: true, replace: true });
}

// ── Status update modal ───────────────────────────────────────────────────────
const editOrder   = ref(null);
const statusForm  = useForm({ status: '', note: '' });

function openStatusModal(order) {
    editOrder.value  = order;
    statusForm.status = order.status;
    statusForm.note   = '';
}

function submitStatus() {
    statusForm.patch(route('admin.orders.update-status', editOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => { editOrder.value = null; statusForm.reset(); },
    });
}

// ── Sync single order ─────────────────────────────────────────────────────────
const syncingId  = ref(null);
const syncResult = ref({}); // { [orderId]: { success, message, at } }

function syncOrder(order) {
    syncingId.value = order.id;
    router.post(route('admin.orders.sync', order.id), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            syncResult.value[order.id] = { success: true, at: new Date() };
        },
        onError: () => {
            syncResult.value[order.id] = { success: false, at: new Date() };
        },
        onFinish: () => { syncingId.value = null; },
    });
}

// ── Auto-refresh active orders every 30s ──────────────────────────────────────
let autoRefreshTimer = null;
const lastAutoRefresh = ref(new Date());

function autoRefresh() {
    router.reload({ only: ['orders', 'statusCounts'], preserveState: true, preserveScroll: true,
        onSuccess: () => { lastAutoRefresh.value = new Date(); },
    });
}

onMounted(() => {
    autoRefreshTimer = setInterval(autoRefresh, 30_000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) autoRefresh(); });
});
onUnmounted(() => clearInterval(autoRefreshTimer));

// ── Helpers ───────────────────────────────────────────────────────────────────
const STATUS_FILTERS = [
    { key: '',           label: 'All' },
    { key: 'pending',    label: 'Pending' },
    { key: 'processing', label: 'Processing' },
    { key: 'completed',  label: 'Completed' },
    { key: 'partial',    label: 'Partial' },
    { key: 'canceled',   label: 'Canceled' },
    { key: 'failed',     label: 'Failed' },
];

const STATUS_OPTIONS = ['pending', 'processing', 'completed', 'partial', 'canceled', 'failed'];

const statusBadge = (s) => ({
    completed:  'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    processing: 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
    pending:    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
    canceled:   'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
    partial:    'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
    failed:     'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
}[s] ?? 'bg-slate-100 text-slate-600 dark:bg-white/[0.06] dark:text-slate-400');

const refundBadge = (s) => ({
    completed: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    skipped:   'bg-slate-100 text-slate-500 dark:bg-white/[0.05] dark:text-slate-500',
}[s] ?? '');

function timeAgo(iso) {
    if (!iso) return null;
    const secs = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (secs < 60)   return `${secs}s ago`;
    if (secs < 3600) return `${Math.floor(secs / 60)}m ago`;
    if (secs < 86400) return `${Math.floor(secs / 3600)}h ago`;
    return fmtDate(iso);
}

const fmt = (n) => n !== null && n !== undefined
    ? Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 })
    : '—';
const fmtMoney = (n) => n !== null && n !== undefined
    ? '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 })
    : '—';
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
const shortRef = (r) => r ? String(r).slice(0, 8).toUpperCase() : '—';
</script>

<template>
    <Head title="Order Management" />
    <AdminLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Order Management</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ orders.total.toLocaleString() }} total orders
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <div class="hidden sm:flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-600">
                        <Clock class="w-3 h-3" />
                        Auto-refreshing every 30s
                    </div>
                    <button @click="autoRefresh"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-white/[0.07]
                            bg-white dark:bg-[#0d1829] text-[12px] font-semibold text-slate-600 dark:text-slate-300
                            hover:border-sky-300 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-400
                            active:scale-95 transition-all">
                        <RefreshCw class="w-3.5 h-3.5" />
                        Refresh All
                    </button>
                </div>
            </div>

            <!-- Flash -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="flash.success" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium">
                    <CircleCheck class="w-4 h-4 flex-shrink-0" />
                    {{ flash.success }}
                </div>
            </Transition>
            <div v-if="errors.order" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-sm font-medium">
                <AlertCircle class="w-4 h-4 flex-shrink-0" />
                {{ errors.order }}
            </div>

            <!-- Status filter tabs -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button v-for="f in STATUS_FILTERS" :key="f.key"
                    @click="applyFilter(f.key)"
                    :class="['px-3.5 py-1.5 rounded-xl text-[12px] font-semibold transition-all border',
                        activeStatus === f.key
                            ? 'bg-sky-500 border-sky-500 text-white shadow-sm shadow-sky-500/30'
                            : 'border-slate-200 dark:border-white/[0.07] text-slate-600 dark:text-slate-400 hover:border-sky-300 dark:hover:border-sky-500/30 bg-white dark:bg-[#0d1829]']">
                    {{ f.label }}
                    <span v-if="f.key && statusCounts[f.key]"
                        class="ml-1 opacity-70">({{ statusCounts[f.key] }})</span>
                </button>
            </div>

            <!-- Table card -->
            <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] overflow-hidden">

                <!-- Search -->
                <div class="p-4 border-b border-slate-100 dark:border-white/[0.05]">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search by reference or user…"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm
                                bg-slate-50 dark:bg-white/[0.04]
                                border border-slate-200 dark:border-white/[0.07]
                                text-slate-800 dark:text-slate-200
                                placeholder-slate-400 dark:placeholder-slate-600
                                focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400 transition-all" />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/50 dark:bg-white/[0.01]">
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Ref</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">User</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Service</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Qty</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Amount</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Provider</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Cost / Profit</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Status / Refund</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Date / Sync</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="11" class="px-4 py-12 text-center">
                                    <ShoppingCart class="w-10 h-10 mx-auto mb-3 text-slate-300 dark:text-slate-700" />
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">No orders found</p>
                                </td>
                            </tr>
                            <tr v-for="o in orders.data" :key="o.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors">
                                <!-- Ref -->
                                <td class="px-4 py-3.5">
                                    <span class="font-mono text-[12px] text-slate-500 dark:text-slate-400">{{ shortRef(o.reference) }}</span>
                                </td>
                                <!-- User -->
                                <td class="px-4 py-3.5">
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 text-[13px] max-w-[120px] truncate">{{ o.user?.name }}</p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-600 max-w-[120px] truncate">{{ o.user?.email }}</p>
                                    </div>
                                </td>
                                <!-- Service -->
                                <td class="px-4 py-3.5">
                                    <p class="text-[12px] text-slate-600 dark:text-slate-400 max-w-[150px] truncate" :title="o.service">{{ o.service ?? '—' }}</p>
                                </td>
                                <!-- Qty -->
                                <td class="px-4 py-3.5 text-[13px] text-slate-700 dark:text-slate-300 font-mono">
                                    {{ o.quantity?.toLocaleString() ?? '—' }}
                                </td>
                                <!-- Amount -->
                                <td class="px-4 py-3.5 font-mono font-semibold text-slate-800 dark:text-slate-200 text-[13px]">
                                    {{ fmt(o.amount) }}
                                </td>
                                <!-- Provider -->
                                <td class="px-4 py-3.5">
                                    <div v-if="o.provider_name">
                                        <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300">{{ o.provider_name }}</p>
                                        <p v-if="o.provider_order_id" class="font-mono text-[10px] text-slate-400 dark:text-slate-600">#{{ o.provider_order_id }}</p>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-300 dark:text-slate-700">—</span>
                                </td>
                                <!-- Cost / Profit -->
                                <td class="px-4 py-3.5">
                                    <div v-if="o.cost !== null && o.cost !== undefined">
                                        <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">Cost: {{ fmtMoney(o.cost) }}</p>
                                        <p :class="['font-mono text-[11px] font-semibold', o.profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400']">
                                            Profit: {{ fmtMoney(o.profit) }}
                                        </p>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-300 dark:text-slate-700">—</span>
                                </td>
                                <!-- Status / Refund -->
                                <td class="px-4 py-3.5">
                                    <span :class="['inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold capitalize', statusBadge(o.status)]">
                                        {{ o.status }}
                                    </span>
                                    <!-- Refund indicator -->
                                    <div v-if="o.refund_status === 'completed' && o.refund_amount > 0"
                                        class="mt-1 flex items-center gap-1">
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-px rounded text-[9.5px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/20">
                                            ↩ Refunded ${{ Number(o.refund_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 }) }}
                                        </span>
                                    </div>
                                    <div v-else-if="o.refund_status === null && ['canceled','partial','failed'].includes(o.status)"
                                        class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-px rounded text-[9.5px] font-bold bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/20">
                                            ⏳ Refund pending
                                        </span>
                                    </div>
                                </td>
                                <!-- Date / Sync -->
                                <td class="px-4 py-3.5 text-[12px] text-slate-400 dark:text-slate-400 whitespace-nowrap">
                                    <p>{{ fmtDate(o.created_at) }}</p>
                                    <!-- Show sync time if synced -->
                                    <p v-if="o.last_synced_at"
                                        class="text-[10px] mt-0.5 text-emerald-600 dark:text-emerald-500 font-medium">
                                        Last synced: {{ timeAgo(o.last_synced_at) }}
                                    </p>
                                    <!-- Show "Never synced" only for orders with a provider order ID -->
                                    <p v-else-if="o.provider_order_id"
                                        class="text-[10px] mt-0.5 text-amber-500 dark:text-amber-500 font-medium">
                                        Not yet synced
                                    </p>
                                    <p v-else
                                        class="text-[10px] mt-0.5 text-slate-300 dark:text-slate-700">
                                        No provider ID
                                    </p>
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-col gap-1.5">
                                        <!-- Sync Now button — only for orders with a provider order ID -->
                                        <button v-if="o.provider_order_id"
                                            @click="syncOrder(o)"
                                            :disabled="syncingId === o.id"
                                            :class="['flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all disabled:opacity-40',
                                                syncingId === o.id
                                                    ? 'bg-sky-50 dark:bg-sky-500/10 text-sky-500 dark:text-sky-400'
                                                    : 'bg-sky-500/10 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400 hover:bg-sky-500/20 dark:hover:bg-sky-500/20 active:scale-95']">
                                            <RefreshCw :class="['w-3 h-3', syncingId === o.id ? 'animate-spin' : '']" />
                                            {{ syncingId === o.id ? 'Syncing…' : 'Sync Now' }}
                                        </button>
                                        <!-- Edit status -->
                                        <button @click="openStatusModal(o)"
                                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold
                                                bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-400
                                                hover:bg-slate-200 dark:hover:bg-white/10 active:scale-95 transition-all">
                                            Edit Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[12px] text-slate-500 dark:text-slate-400">
                        Showing {{ orders.from }}–{{ orders.to }} of {{ orders.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <button v-if="orders.prev_page_url" @click="router.get(orders.prev_page_url, {}, { preserveState: true })"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-500 dark:text-slate-400 transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <span class="px-3 py-1 text-[12px] font-semibold text-slate-700 dark:text-slate-300">
                            {{ orders.current_page }} / {{ orders.last_page }}
                        </span>
                        <button v-if="orders.next_page_url" @click="router.get(orders.next_page_url, {}, { preserveState: true })"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-500 dark:text-slate-400 transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Status Edit Modal ───────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="editOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="editOrder = null">
                <div class="w-full max-w-sm bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.08] shadow-2xl overflow-hidden">

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Update Order Status</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-px">Order #{{ editOrder?.id }}</p>
                        </div>
                        <button @click="editOrder = null" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-400 transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitStatus" class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select v-model="statusForm.status"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08]
                                    bg-white dark:bg-white/[0.03] text-slate-800 dark:text-slate-200 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all">
                                <option v-for="s in STATUS_OPTIONS" :key="s" :value="s" class="capitalize">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Note (optional)</label>
                            <input v-model="statusForm.note" type="text" placeholder="Reason for change…"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08]
                                    bg-white dark:bg-white/[0.03] text-slate-800 dark:text-slate-200 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all" />
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="button" @click="editOrder = null"
                                class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08] text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/[0.04] transition-all">
                                Cancel
                            </button>
                            <button type="submit" :disabled="statusForm.processing"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-bold transition-all disabled:opacity-50 shadow-sm shadow-sky-500/30">
                                <RefreshCw v-if="statusForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

    </AdminLayout>
</template>

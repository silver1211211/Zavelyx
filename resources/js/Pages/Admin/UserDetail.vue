<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDownLeft, ArrowLeft, ArrowUpRight, Ban, CheckCircle2,
    CircleCheck, ClipboardList, History, Minus, Plus,
    RefreshCw, ShieldAlert, ShoppingCart, Wallet, X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user:         Object,
    stats:        Object,
    orders:       Array,
    transactions: Array,
    adjustments:  Array,
});

const flash   = computed(() => usePage().props.flash ?? {});
const errors  = computed(() => usePage().props.errors ?? {});
const wallet  = computed(() => props.user.wallet);
const frozen  = computed(() => props.user.is_active === false);

// ── Tabs ──────────────────────────────────────────────────────────────────────
const TABS = ['Overview', 'Orders', 'Transactions', 'Balance History'];
const tab  = ref('Overview');

// ── Balance modal ─────────────────────────────────────────────────────────────
const showBalModal = ref(false);
const balForm = useForm({
    type:   'credit',
    amount: '',
    note:   '',
});

function submitBalance() {
    balForm.post(route('admin.users.adjust-balance', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            showBalModal.value = false;
            balForm.reset();
        },
    });
}

// ── Freeze / unfreeze ─────────────────────────────────────────────────────────
const freezeForm   = useForm({});
const unfreezeForm = useForm({});

function doFreeze()   { freezeForm.patch(route('admin.users.freeze', props.user.id),   { preserveScroll: true }); }
function doUnfreeze() { unfreezeForm.patch(route('admin.users.unfreeze', props.user.id), { preserveScroll: true }); }

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 });
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const statusBadge = (s) => ({
    completed:  'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    processing: 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
    pending:    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
    canceled:   'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
    partial:    'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
}[s] ?? 'bg-slate-100 text-slate-600 dark:bg-white/[0.06] dark:text-slate-400');
</script>

<template>
    <Head :title="`User — ${user.name}`" />
    <AdminLayout>
        <div class="space-y-6 max-w-5xl mx-auto">

            <!-- Back + header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.users.index')"
                        class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-500 dark:text-slate-400 transition-colors">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ user.name }}</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-px">{{ user.email }}</p>
                    </div>
                </div>

                <!-- Status badge + actions -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold',
                        frozen ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                               : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400']">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                            :class="frozen ? 'bg-rose-500' : 'bg-emerald-500 animate-pulse'" />
                        {{ frozen ? 'Frozen' : 'Active' }}
                    </span>

                    <button v-if="frozen" @click="doUnfreeze"
                        :disabled="unfreezeForm.processing"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold
                            bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400
                            hover:bg-emerald-100 dark:hover:bg-emerald-500/20 border border-emerald-200 dark:border-emerald-500/20
                            transition-all active:scale-95 disabled:opacity-50">
                        <CheckCircle2 class="w-3 h-3" />
                        Activate
                    </button>
                    <button v-else @click="doFreeze"
                        :disabled="freezeForm.processing"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold
                            bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400
                            hover:bg-rose-100 dark:hover:bg-rose-500/20 border border-rose-200 dark:border-rose-500/20
                            transition-all active:scale-95 disabled:opacity-50">
                        <Ban class="w-3 h-3" />
                        Freeze
                    </button>

                    <button @click="showBalModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold
                            bg-sky-500 text-white hover:bg-sky-600
                            shadow-sm shadow-sky-500/30
                            transition-all active:scale-95">
                        <Wallet class="w-3 h-3" />
                        Edit Balance
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
            <div v-if="errors.amount || errors.user" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-sm font-medium">
                {{ errors.amount || errors.user }}
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Balance -->
                <div class="col-span-2 sm:col-span-1 bg-gradient-to-br from-sky-500 to-blue-600 rounded-2xl p-5 shadow-lg shadow-sky-500/20">
                    <div class="flex items-center gap-2 mb-3">
                        <Wallet class="w-4 h-4 text-white/70" />
                        <p class="text-[11px] font-bold uppercase tracking-wider text-white/70">Balance</p>
                    </div>
                    <p class="text-2xl font-black text-white leading-none">
                        USD {{ fmt(wallet?.balance) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <ShoppingCart class="w-3.5 h-3.5 text-slate-400" />
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Orders</p>
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_orders }}</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5">{{ stats.completed_orders }} completed</p>
                </div>

                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <ArrowDownLeft class="w-3.5 h-3.5 text-slate-400" />
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Total Spent</p>
                    </div>
                    <p class="text-xl font-black text-slate-900 dark:text-white font-mono">{{ fmt(stats.total_spent) }}</p>
                </div>

                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <ArrowUpRight class="w-3.5 h-3.5 text-slate-400" />
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Refunded</p>
                    </div>
                    <p class="text-xl font-black text-slate-900 dark:text-white font-mono">{{ fmt(stats.total_refunded) }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 bg-slate-100 dark:bg-white/[0.04] p-1 rounded-xl w-fit">
                <button v-for="t in TABS" :key="t" @click="tab = t"
                    :class="['px-4 py-1.5 rounded-lg text-[13px] font-semibold transition-all',
                        tab === t
                            ? 'bg-white dark:bg-[#0d1829] text-slate-900 dark:text-white shadow-sm'
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']">
                    {{ t }}
                </button>
            </div>

            <!-- Overview tab -->
            <div v-if="tab === 'Overview'" class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-6 space-y-4">
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-200">Account Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">Name</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ user.name }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">Email</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ user.email }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">User ID</p>
                        <p class="font-mono text-slate-700 dark:text-slate-300">#{{ user.id }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">Joined</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ fmtDate(user.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">Currency</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200">USD</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1">Account Status</p>
                        <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold',
                            frozen ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                                   : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400']">
                            {{ frozen ? 'Frozen' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Orders tab -->
            <div v-if="tab === 'Orders'" class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/[0.05]">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200">Recent Orders</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/50 dark:bg-white/[0.01]">
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">ID</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Service</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Qty</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Amount</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-if="orders.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400 dark:text-slate-600 text-sm">No orders yet</td>
                            </tr>
                            <tr v-for="o in orders" :key="o.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-500 dark:text-slate-400">#{{ o.id }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300 max-w-[180px] truncate">{{ o.service ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ o.quantity?.toLocaleString() ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono font-semibold text-slate-800 dark:text-slate-200">{{ fmt(o.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold capitalize', statusBadge(o.status)]">
                                        {{ o.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-400 dark:text-slate-400 whitespace-nowrap">{{ fmtDate(o.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transactions tab -->
            <div v-if="tab === 'Transactions'" class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/[0.05]">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200">Transaction History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/50 dark:bg-white/[0.01]">
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Type</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Amount</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Balance After</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Reference</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Description</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-if="transactions.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400 dark:text-slate-600 text-sm">No transactions</td>
                            </tr>
                            <tr v-for="t in transactions" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold',
                                        t.type === 'credit' || t.type === 'deposit' || t.type === 'refund' || t.type === 'bonus'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400']">
                                        <ArrowUpRight v-if="t.type === 'credit' || t.type === 'deposit' || t.type === 'refund' || t.type === 'bonus'" class="w-3 h-3" />
                                        <ArrowDownLeft v-else class="w-3 h-3" />
                                        {{ t.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold"
                                    :class="t.type === 'credit' || t.type === 'deposit' || t.type === 'refund' || t.type === 'bonus' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ (t.type === 'credit' || t.type === 'deposit' || t.type === 'refund' || t.type === 'bonus') ? '+' : '-' }}{{ fmt(t.amount) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-700 dark:text-slate-300">{{ fmt(t.balance_after) }}</td>
                                <td class="px-4 py-3">
                                    <code v-if="t.reference" class="text-[10.5px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-white/[0.06] px-1.5 py-0.5 rounded-md">
                                        {{ t.reference.slice(0, 18) }}…
                                    </code>
                                    <span v-else class="text-slate-300 dark:text-slate-700">—</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 max-w-[180px] truncate text-[12px]">{{ t.description ?? '—' }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-400 dark:text-slate-400 whitespace-nowrap">{{ fmtDate(t.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Balance History tab -->
            <div v-if="tab === 'Balance History'" class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/[0.05]">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200">Admin Balance Adjustments</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/50 dark:bg-white/[0.01]">
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Type</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Amount</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Before → After</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Note</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">By</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-if="adjustments.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400 dark:text-slate-600 text-sm">No balance adjustments yet</td>
                            </tr>
                            <tr v-for="a in adjustments" :key="a.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold',
                                        a.type === 'credit'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400']">
                                        <Plus v-if="a.type === 'credit'" class="w-3 h-3" />
                                        <Minus v-else class="w-3 h-3" />
                                        {{ a.type === 'credit' ? 'Add' : 'Remove' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold"
                                    :class="a.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ fmt(a.amount) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-500 dark:text-slate-400">
                                    {{ fmt(a.balance_before) }} → <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ fmt(a.balance_after) }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-[12px] max-w-[160px] truncate">{{ a.note ?? '—' }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-500 dark:text-slate-400">{{ a.admin_user }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-400 dark:text-slate-400 whitespace-nowrap">{{ fmtDate(a.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── Balance Edit Modal ──────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="showBalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showBalModal = false">
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div class="w-full max-w-md bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.08] shadow-2xl overflow-hidden">

                        <!-- Modal header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-white/[0.06]">
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Edit Balance</h3>
                                <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-px">{{ user.name }}</p>
                            </div>
                            <button @click="showBalModal = false"
                                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-400 transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Current balance display -->
                        <div class="px-6 py-4 bg-sky-50 dark:bg-sky-500/[0.06] border-b border-sky-100 dark:border-sky-500/10">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-500 mb-1">Current Balance</p>
                            <p class="text-2xl font-black text-sky-700 dark:text-sky-400">
                                USD {{ fmt(wallet?.balance) }}
                            </p>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submitBalance" class="px-6 py-5 space-y-4">
                            <!-- Type toggle -->
                            <div>
                                <label class="block text-[12px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Action</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="balForm.type = 'credit'"
                                        :class="['flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-semibold transition-all',
                                            balForm.type === 'credit'
                                                ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-500/30'
                                                : 'border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-400 hover:border-emerald-300 dark:hover:border-emerald-500/30']">
                                        <Plus class="w-4 h-4" />
                                        Add Balance
                                    </button>
                                    <button type="button" @click="balForm.type = 'debit'"
                                        :class="['flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-semibold transition-all',
                                            balForm.type === 'debit'
                                                ? 'bg-rose-500 border-rose-500 text-white shadow-sm shadow-rose-500/30'
                                                : 'border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-400 hover:border-rose-300 dark:hover:border-rose-500/30']">
                                        <Minus class="w-4 h-4" />
                                        Remove Balance
                                    </button>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div>
                                <label class="block text-[12px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Amount</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm font-semibold">
                                        USD
                                    </span>
                                    <input v-model="balForm.amount" type="number" step="0.000001" min="0.000001" placeholder="0.000000" required
                                        class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 dark:border-white/[0.08]
                                            bg-white dark:bg-white/[0.03] text-slate-800 dark:text-slate-200 text-lg font-mono
                                            focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400 transition-all" />
                                </div>
                                <p v-if="balForm.errors.amount" class="text-rose-500 dark:text-rose-400 text-[12px] mt-1">{{ balForm.errors.amount }}</p>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-[12px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Note (optional)</label>
                                <input v-model="balForm.note" type="text" placeholder="Reason for adjustment…" maxlength="255"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08]
                                        bg-white dark:bg-white/[0.03] text-slate-800 dark:text-slate-200 text-sm
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400 transition-all" />
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showBalModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-white/[0.08] text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/[0.04] transition-all">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="balForm.processing || !balForm.amount"
                                    :class="['flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold text-white transition-all disabled:opacity-50',
                                        balForm.type === 'credit' ? 'bg-emerald-500 hover:bg-emerald-600 shadow-sm shadow-emerald-500/30' : 'bg-rose-500 hover:bg-rose-600 shadow-sm shadow-rose-500/30']">
                                    <RefreshCw v-if="balForm.processing" class="w-4 h-4 animate-spin" />
                                    {{ balForm.type === 'credit' ? 'Add Balance' : 'Remove Balance' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

    </AdminLayout>
</template>

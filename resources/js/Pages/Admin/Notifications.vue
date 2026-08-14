<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Activity, AlertTriangle, BarChart2, Bell, BellDot,
    Calendar, Check, ChevronDown, ChevronRight, Clock,
    Copy, Edit2, ExternalLink, Eye, Filter, Gift, Globe,
    Info, Loader2, Megaphone, Plus,
    RefreshCw, Search, Send, Shield, Star, Target,
    Trash2, TrendingUp, Users, X, Zap, CheckCheck,
    UserCheck, UserX, Wallet, ShoppingBag, Sparkles,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    broadcasts: { type: Object, default: () => ({ data: [] }) },
    stats:      { type: Object, default: () => ({}) },
    types:      { type: Array,  default: () => [] },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Create/Edit modal ─────────────────────────────────────────────────────────
const showModal  = ref(false);
const editMode   = ref(false);
const editId     = ref(null);
const searchQ    = ref('');
const filterSent = ref('all');

const form = useForm({
    title:            '',
    message:          '',
    type:             'admin_custom',
    category:         'system',
    priority:         'info',
    action_url:       '',
    action_label:     '',
    open_in_new_tab:  false,
    is_pinned:        false,
    expires_at:       '',
    target_type:      'all',
    target_config:    {},
    scheduled_at:     '',
});

const targetRoleVal    = ref('user');
const targetCountryVal = ref('');
const targetMinBalance = ref('');
const targetMaxBalance = ref('');
const targetUserIds    = ref('');
const targetFromDate   = ref('');
const targetToDate     = ref('');
const targetMinOrders  = ref('1');
const targetNewUserDays = ref('30');
const targetRecentDays  = ref('7');

watch(() => form.target_type, () => { buildTargetConfig(); });

function buildTargetConfig() {
    const tc = {};
    switch (form.target_type) {
        case 'role':              tc.role = targetRoleVal.value; break;
        case 'country':           tc.country = targetCountryVal.value; break;
        case 'balance_range':     tc.min_balance = targetMinBalance.value; tc.max_balance = targetMaxBalance.value; break;
        case 'specific':          tc.user_ids = targetUserIds.value.split(',').map(s => s.trim()).filter(Boolean).map(Number); break;
        case 'date_joined':       tc.from = targetFromDate.value; tc.to = targetToDate.value; break;
        case 'purchase_activity': tc.min_orders = Number(targetMinOrders.value); break;
        case 'new_users':         tc.days = Number(targetNewUserDays.value); break;
        case 'recent_active':     tc.days = Number(targetRecentDays.value); break;
    }
    form.target_config = tc;
}

function openCreate() {
    editMode.value = false;
    editId.value   = null;
    form.reset();
    showModal.value = true;
}

function openEdit(b) {
    editMode.value       = true;
    editId.value         = b.id;
    form.title           = b.title;
    form.message         = b.message;
    form.type            = b.type;
    form.category        = b.category;
    form.priority        = b.priority;
    form.action_url      = b.action_url ?? '';
    form.action_label    = b.action_label ?? '';
    form.open_in_new_tab = b.open_in_new_tab ?? false;
    form.is_pinned       = b.is_pinned;
    form.expires_at      = b.expires_at ? b.expires_at.slice(0, 16) : '';
    form.target_type     = b.target_type;
    form.target_config   = b.target_config ?? {};
    form.scheduled_at    = b.scheduled_at ? b.scheduled_at.slice(0, 16) : '';
    showModal.value = true;
}

function submitForm() {
    buildTargetConfig();
    if (editMode.value) {
        form.put(route('admin.notifications.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('admin.notifications.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function sendNow(id) {
    if (!confirm('Send this notification to all targeted users now?')) return;
    useForm({}).post(route('admin.notifications.send', id), { preserveScroll: true });
}

function deleteNotif(id) {
    if (!confirm('Delete this broadcast? All user notification records will be permanently removed globally — users will instantly lose access to it.')) return;
    useForm({}).delete(route('admin.notifications.destroy', id), { preserveScroll: true });
}

// ── Analytics modal ───────────────────────────────────────────────────────────
const showAnalytics    = ref(false);
const analyticsLoading = ref(false);
const analyticsData    = ref(null);
const analyticsTab     = ref('readers');

async function openAnalytics(b) {
    showAnalytics.value    = true;
    analyticsLoading.value = true;
    analyticsData.value    = null;
    analyticsTab.value     = 'readers';
    try {
        const res = await fetch(`/admin/notifications/${b.id}/analytics`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) analyticsData.value = await res.json();
    } finally {
        analyticsLoading.value = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const priorityConfig = {
    success:   { bg: 'bg-emerald-500/10', text: 'text-emerald-500', border: 'border-emerald-500/20', bar: 'bg-emerald-500' },
    warning:   { bg: 'bg-amber-500/10',   text: 'text-amber-500',   border: 'border-amber-500/20',   bar: 'bg-amber-500' },
    error:     { bg: 'bg-rose-500/10',    text: 'text-rose-500',    border: 'border-rose-500/20',     bar: 'bg-rose-500' },
    promotion: { bg: 'bg-violet-500/10',  text: 'text-violet-500',  border: 'border-violet-500/20',  bar: 'bg-violet-500' },
    info:      { bg: 'bg-sky-500/10',     text: 'text-sky-500',     border: 'border-sky-500/20',      bar: 'bg-sky-500' },
};
const pc = p => priorityConfig[p] ?? priorityConfig.info;

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(str) {
    if (!str) return '—';
    const d = new Date(str);
    const diff = Math.floor((Date.now() - d) / 1000);
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400)  return Math.floor(diff / 3600) + 'h ago';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const filteredBroadcasts = computed(() => {
    let list = props.broadcasts.data ?? [];
    if (searchQ.value.trim()) {
        const q = searchQ.value.toLowerCase();
        list = list.filter(b => b.title.toLowerCase().includes(q) || b.message.toLowerCase().includes(q));
    }
    if (filterSent.value === 'sent')      list = list.filter(b => b.sent_at);
    if (filterSent.value === 'scheduled') list = list.filter(b => b.scheduled_at && !b.sent_at);
    if (filterSent.value === 'draft')     list = list.filter(b => !b.sent_at && !b.scheduled_at);
    return list;
});

const targetLabels = {
    all:              '🌍 All Users',
    active:           '✅ Active Users',
    inactive:         '😴 Inactive Users',
    new_users:        '🆕 New Users',
    role:             '🏷️ By Role',
    country:          '🌐 By Country',
    balance_range:    '💰 By Balance Range',
    specific:         '👤 Specific Users',
    date_joined:      '📅 By Join Date',
    purchase_activity:'🛒 By Purchases',
    verified:         '✔️ Verified Users',
    unverified:       '📧 Unverified Users',
    with_balance:     '💵 Has Balance',
    without_balance:  '🪙 No Balance',
    recent_active:    '🔥 Recently Active',
};

const typeOptions = [
    'welcome','deposit_success','deposit_failed','number_purchased','otp_received',
    'refund_processed','promotional','maintenance','security_alert','new_feature',
    'verification_reminder','inactive_reminder','bonus_reward','balance_low',
    'admin_custom','flash_sale','service_outage','provider_maintenance','cashback','loyalty_vip',
];

const typeLabel = t => t?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) ?? t;

// Analytics computed
const aStats   = computed(() => analyticsData.value?.stats ?? {});
const aBcast   = computed(() => analyticsData.value?.broadcast ?? {});
const aReaders = computed(() => analyticsData.value?.readers ?? []);
const aIgnored = computed(() => analyticsData.value?.ignored ?? []);

const analyticsTabList = computed(() => [
    { key: 'readers', label: 'Readers', count: aStats.value.read ?? aReaders.value.length },
    { key: 'ignored', label: 'Ignored', count: aStats.value.unread ?? aIgnored.value.length },
]);
</script>

<template>
    <Head title="Admin – Notifications" />
    <AdminLayout>

        <!-- Page header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.15))">
                        <Megaphone class="w-4.5 h-4.5 text-sky-500" />
                    </div>
                    Notification Management
                </h1>
                <p class="text-[13px] text-slate-400 dark:text-slate-400 mt-0.5">Create, send, and analyze user notification campaigns.</p>
            </div>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-semibold text-white shadow-lg transition-all active:scale-95 flex-shrink-0"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 16px rgba(14,165,233,0.3)">
                <Plus class="w-4 h-4" />
                Create Notification
            </button>
        </div>

        <!-- Flash -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="flash.success" class="mb-5 flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-500/8 border border-emerald-500/20 rounded-2xl">
                <Check class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                <p class="text-[13px] font-medium text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
            </div>
        </Transition>

        <!-- Stats row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
            <div class="lg:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-slate-900 dark:text-white">{{ stats.total ?? 0 }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Total Created</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-emerald-500">{{ stats.sent ?? 0 }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Sent</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-amber-500">{{ stats.scheduled ?? 0 }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Scheduled</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-violet-500">{{ (stats.recipients ?? 0).toLocaleString() }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Delivered</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-sky-500">{{ (stats.read ?? 0).toLocaleString() }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Total Reads</p>
                <p class="text-[10px] text-sky-500/70 mt-0.5">{{ stats.read_rate ?? 0 }}% rate</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black text-amber-500">{{ (stats.unread ?? 0).toLocaleString() }}</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Unread</p>
            </div>
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                <p class="text-[22px] font-black" :class="(stats.read_rate ?? 0) >= 50 ? 'text-emerald-500' : 'text-rose-500'">{{ stats.read_rate ?? 0 }}%</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Read Rate</p>
            </div>
        </div>

        <!-- Filters & search -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input v-model="searchQ" type="text" placeholder="Search notifications…"
                    class="w-full h-10 pl-9 pr-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
            </div>
            <div class="flex gap-2">
                <button v-for="f in [['all','All'],['sent','Sent'],['scheduled','Scheduled'],['draft','Draft']]" :key="f[0]"
                    @click="filterSent = f[0]"
                    :class="['px-3 py-2 rounded-xl text-[12px] font-semibold transition-all', filterSent === f[0] ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400' : 'bg-white dark:bg-[#0d1e35] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-sky-500/12 hover:bg-slate-50 dark:hover:bg-white/5']">
                    {{ f[1] }}
                </button>
            </div>
        </div>

        <!-- Broadcast list -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
            <div v-if="filteredBroadcasts.length === 0" class="py-16 flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.08))">
                    <Bell class="w-6 h-6 text-sky-500/40" />
                </div>
                <p class="text-[14px] font-semibold text-slate-500 dark:text-slate-400">No notifications found</p>
                <button @click="openCreate" class="text-[12px] font-semibold text-sky-500 hover:text-sky-400 transition-colors">Create your first notification →</button>
            </div>

            <div v-else>
                <div v-for="b in filteredBroadcasts" :key="b.id"
                    class="flex items-start gap-4 p-4 border-b last:border-0 hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors"
                    :style="{ borderColor: 'rgba(255,255,255,0.06)' }">

                    <!-- Priority icon -->
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5', pc(b.priority).bg]">
                        <Megaphone class="w-5 h-5" :class="pc(b.priority).text" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-0.5">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ b.title }}</p>
                            <span v-if="b.is_pinned" class="text-[10px]">📌</span>
                            <span v-if="b.sent_at" class="text-[9.5px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Sent</span>
                            <span v-else-if="b.scheduled_at" class="text-[9.5px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">Scheduled</span>
                            <span v-else class="text-[9.5px] font-black uppercase px-2 py-0.5 rounded-full bg-slate-500/10 text-slate-500 border border-slate-500/20">Draft</span>
                            <span :class="['text-[9.5px] font-black uppercase px-2 py-0.5 rounded-full border', pc(b.priority).bg, pc(b.priority).text, pc(b.priority).border]">{{ b.priority }}</span>
                        </div>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 truncate mb-1.5">{{ b.message }}</p>
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-[10.5px] text-slate-400 dark:text-slate-600 flex items-center gap-1">
                                <Target class="w-3 h-3" />
                                {{ targetLabels[b.target_type] ?? b.target_type }}
                            </span>
                            <span v-if="b.recipients_count > 0" class="text-[10.5px] text-slate-400 dark:text-slate-600 flex items-center gap-1">
                                <Users class="w-3 h-3" />
                                {{ b.recipients_count.toLocaleString() }} recipients
                            </span>
                            <span v-if="b.action_url" class="text-[10.5px] text-sky-500 flex items-center gap-1">
                                <ExternalLink class="w-3 h-3" />
                                {{ b.action_label || 'CTA attached' }}
                            </span>
                            <span class="text-[10.5px] text-slate-400 dark:text-slate-600">
                                {{ b.sent_at ? 'Sent ' + formatDate(b.sent_at) : b.scheduled_at ? 'Scheduled ' + formatDate(b.scheduled_at) : 'Created ' + formatDate(b.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button v-if="b.sent_at" @click="openAnalytics(b)"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/10 hover:border-sky-400 hover:text-sky-500 dark:hover:text-sky-400 transition-all active:scale-95">
                            <BarChart2 class="w-3 h-3" />
                            <span class="hidden sm:block">Analytics</span>
                        </button>
                        <button v-if="!b.sent_at" @click="sendNow(b.id)"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-white transition-all active:scale-95"
                            style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">
                            <Send class="w-3 h-3" />
                            Send
                        </button>
                        <button v-if="!b.sent_at" @click="openEdit(b)"
                            class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-sky-500 hover:bg-sky-500/10 transition-all active:scale-90">
                            <Edit2 class="w-3.5 h-3.5" />
                        </button>
                        <button @click="deleteNotif(b.id)"
                            class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all active:scale-90">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Analytics Modal ─────────────────────────────────────────────── -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showAnalytics" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.75); backdrop-filter: blur(6px)"
                @click.self="showAnalytics = false">
                <div class="bg-white dark:bg-[#0a1628] rounded-2xl border border-slate-200 dark:border-sky-500/15 w-full max-w-4xl shadow-2xl flex flex-col"
                    style="max-height: min(90vh, 90dvh)"
                    @click.stop>

                    <!-- Modal header — sticky -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/[0.08] flex-shrink-0 rounded-t-2xl"
                        style="background: linear-gradient(135deg, rgba(14,165,233,0.05), rgba(99,102,241,0.04))">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.12))">
                                <BarChart2 class="w-4.5 h-4.5 text-sky-500" />
                            </div>
                            <div>
                                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Notification Analytics</h2>
                                <p v-if="aBcast.title" class="text-[11px] text-slate-400 dark:text-slate-400 truncate max-w-xs">{{ aBcast.title }}</p>
                            </div>
                        </div>
                        <button @click="showAnalytics = false" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.08] transition-all flex-shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Loading state -->
                    <div v-if="analyticsLoading" class="py-20 flex flex-col items-center gap-3 flex-shrink-0">
                        <div class="w-8 h-8 border-2 border-sky-500/30 border-t-sky-500 rounded-full animate-spin" />
                        <p class="text-[13px] text-slate-400 dark:text-slate-400">Loading analytics…</p>
                    </div>

                    <!-- Analytics content -->
                    <div v-else-if="analyticsData" class="p-6 space-y-6 overflow-y-auto flex-1">

                        <!-- ── Core metrics ──────────────────────────────── -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                            <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-3 border border-slate-200 dark:border-white/8">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Targeted</p>
                                <p class="text-[20px] font-black text-slate-800 dark:text-white">{{ (aStats.targeted ?? 0).toLocaleString() }}</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-400">users</p>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-500/8 rounded-xl p-3 border border-emerald-500/20">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-1">Delivered</p>
                                <p class="text-[20px] font-black text-emerald-600 dark:text-emerald-400">{{ (aStats.delivered ?? 0).toLocaleString() }}</p>
                                <p class="text-[10px] text-emerald-500/70">{{ aStats.delivery_rate ?? 0 }}% rate</p>
                            </div>
                            <div class="bg-rose-50 dark:bg-rose-500/8 rounded-xl p-3 border border-rose-500/20">
                                <p class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-400 mb-1">Failed</p>
                                <p class="text-[20px] font-black text-rose-500">{{ (aStats.failed ?? 0).toLocaleString() }}</p>
                                <p class="text-[10px] text-rose-400/70">delivery errors</p>
                            </div>
                            <div class="bg-sky-50 dark:bg-sky-500/8 rounded-xl p-3 border border-sky-500/20">
                                <p class="text-[10px] font-black uppercase tracking-widest text-sky-600 dark:text-sky-400 mb-1">Read</p>
                                <p class="text-[20px] font-black text-sky-600 dark:text-sky-400">{{ (aStats.read ?? 0).toLocaleString() }}</p>
                                <p class="text-[10px] text-sky-500/70">{{ aStats.read_rate ?? 0 }}% read rate</p>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-500/8 rounded-xl p-3 border border-amber-500/20">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-1">Unread</p>
                                <p class="text-[20px] font-black text-amber-500">{{ (aStats.unread ?? 0).toLocaleString() }}</p>
                                <p class="text-[10px] text-amber-500/70">still pending</p>
                            </div>
                        </div>

                        <!-- ── Performance bars + timestamps ─────────────── -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <!-- Progress bars -->
                            <div class="lg:col-span-2 bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 border border-slate-200 dark:border-white/8 space-y-3">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Performance Overview</p>
                                <div class="space-y-2.5">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Delivery Rate</span>
                                            <span class="text-[11px] font-black text-emerald-500">{{ aStats.delivery_rate ?? 0 }}%</span>
                                        </div>
                                        <div class="h-2 bg-slate-200 dark:bg-white/8 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-700" :style="{ width: (aStats.delivery_rate ?? 0) + '%' }" />
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Read Rate</span>
                                            <span class="text-[11px] font-black text-sky-500">{{ aStats.read_rate ?? 0 }}%</span>
                                        </div>
                                        <div class="h-2 bg-slate-200 dark:bg-white/8 rounded-full overflow-hidden">
                                            <div class="h-full bg-sky-500 rounded-full transition-all duration-700" :style="{ width: (aStats.read_rate ?? 0) + '%' }" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Timing stats -->
                            <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 border border-slate-200 dark:border-white/8 space-y-3">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Timeline</p>
                                <div class="space-y-3 text-[12px]">
                                    <div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mb-0.5">Sent</p>
                                        <p class="font-semibold text-slate-700 dark:text-slate-300">{{ formatDate(aBcast.sent_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mb-0.5">First Opened</p>
                                        <p class="font-semibold" :class="aStats.first_opened_at ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-600'">
                                            {{ aStats.first_opened_at ? formatDate(aStats.first_opened_at) : 'Not yet opened' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mb-0.5">Last Interaction</p>
                                        <p class="font-semibold" :class="aStats.last_interaction ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 dark:text-slate-600'">
                                            {{ aStats.last_interaction ? formatDateShort(aStats.last_interaction) : 'None' }}
                                        </p>
                                    </div>
                                    <div v-if="aBcast.expires_at">
                                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mb-0.5">Expires</p>
                                        <p class="font-semibold text-amber-500">{{ formatDate(aBcast.expires_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Broadcast details + user lists ────────────── -->
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                            <!-- Broadcast details -->
                            <div class="lg:col-span-2 bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 border border-slate-200 dark:border-white/8 space-y-3">
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Broadcast Details</p>

                                <div class="space-y-2 text-[12px]">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Type</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ typeLabel(aBcast.type) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Priority</span>
                                        <span :class="['font-semibold capitalize', pc(aBcast.priority).text]">{{ aBcast.priority }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Category</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300 capitalize">{{ aBcast.category }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Target</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ targetLabels[aBcast.target_type] ?? aBcast.target_type }}</span>
                                    </div>
                                    <div v-if="aBcast.action_url" class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">CTA Label</span>
                                        <span class="font-semibold text-sky-500 truncate max-w-[140px]">{{ aBcast.action_label || 'View Details' }}</span>
                                    </div>
                                    <div v-if="aBcast.action_url" class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">New Tab</span>
                                        <span class="font-semibold" :class="aBcast.open_in_new_tab ? 'text-emerald-500' : 'text-slate-500'">{{ aBcast.open_in_new_tab ? 'Yes' : 'No' }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Sender</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ aBcast.creator?.name ?? 'System Admin' }}</span>
                                    </div>
                                    <div v-if="aBcast.is_pinned" class="flex justify-between gap-2">
                                        <span class="text-slate-400 dark:text-slate-600">Pinned</span>
                                        <span class="font-semibold text-amber-500">📌 Yes</span>
                                    </div>
                                </div>

                                <!-- CTA preview -->
                                <div v-if="aBcast.action_url" class="mt-3 pt-3 border-t border-slate-200 dark:border-white/8">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">CTA Preview</p>
                                    <a :href="aBcast.action_url" :target="aBcast.open_in_new_tab ? '_blank' : '_self'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11.5px] font-bold"
                                        style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.22); color: #38bdf8; text-decoration: none">
                                        {{ aBcast.action_label || 'View Details' }}
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>

                            <!-- User engagement tabs -->
                            <div class="lg:col-span-3 bg-white dark:bg-[#0d1e35] rounded-xl border border-slate-200 dark:border-white/[0.08] overflow-hidden flex flex-col">
                                <!-- Tabs -->
                                <div class="flex border-b border-slate-200 dark:border-white/8">
                                    <button v-for="tab in analyticsTabList" :key="tab.key"
                                        @click="analyticsTab = tab.key"
                                        :class="['flex-1 py-3 text-[12px] font-semibold transition-colors flex items-center justify-center gap-1.5',
                                            analyticsTab === tab.key
                                                ? 'text-sky-600 dark:text-sky-400 border-b-2 border-sky-500 bg-sky-500/5'
                                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300']">
                                        {{ tab.label }}
                                        <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full leading-none"
                                            :class="analyticsTab === tab.key ? 'bg-sky-500/15 text-sky-500' : 'bg-slate-100 dark:bg-white/8 text-slate-400 dark:text-slate-600'">
                                            {{ tab.count }}
                                        </span>
                                    </button>
                                </div>

                                <!-- Tab content -->
                                <div class="overflow-y-auto flex-1" style="min-height: 120px; max-height: 320px">
                                    <!-- Readers -->
                                    <div v-if="analyticsTab === 'readers'">
                                        <div v-if="aReaders.length === 0" class="py-10 flex flex-col items-center gap-2">
                                            <Eye class="w-8 h-8 text-slate-300 dark:text-slate-700" />
                                            <p class="text-[12px] text-slate-400 dark:text-slate-600">No reads yet</p>
                                        </div>
                                        <div v-for="r in aReaders" :key="r.id"
                                            class="flex items-center gap-3 px-4 py-2.5 border-b last:border-0 hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors"
                                            :style="{ borderColor: 'rgba(255,255,255,0.06)' }">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-black flex-shrink-0"
                                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">
                                                {{ r.user?.name?.slice(0,2).toUpperCase() ?? '??' }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300 truncate">{{ r.user?.name ?? 'Unknown' }}</p>
                                                <p class="text-[10.5px] text-slate-400 dark:text-slate-600 truncate">{{ r.user?.email }}</p>
                                            </div>
                                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 flex-shrink-0">{{ formatDateShort(r.read_at) }}</p>
                                        </div>
                                    </div>

                                    <!-- Ignored -->
                                    <div v-if="analyticsTab === 'ignored'">
                                        <div v-if="aIgnored.length === 0" class="py-10 flex flex-col items-center gap-2">
                                            <Bell class="w-8 h-8 text-slate-300 dark:text-slate-700" />
                                            <p class="text-[12px] text-slate-400 dark:text-slate-600">Everyone read it!</p>
                                        </div>
                                        <div v-for="ig in aIgnored" :key="ig.id"
                                            class="flex items-center gap-3 px-4 py-2.5 border-b last:border-0"
                                            :style="{ borderColor: 'rgba(255,255,255,0.06)' }">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-black flex-shrink-0 opacity-50"
                                                style="background: linear-gradient(135deg, #94a3b8, #64748b)">
                                                {{ ig.user?.name?.slice(0,2).toUpperCase() ?? '??' }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12px] font-semibold text-slate-500 dark:text-slate-400 truncate">{{ ig.user?.name ?? 'Unknown' }}</p>
                                                <p class="text-[10.5px] text-slate-400 dark:text-slate-600 truncate">{{ ig.user?.email }}</p>
                                            </div>
                                            <p class="text-[10.5px] text-slate-400 dark:text-slate-600 flex-shrink-0">delivered {{ formatDateShort(ig.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Create/Edit Modal ───────────────────────────────────────────── -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto"
                style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px)"
                @click.self="showModal = false">
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 w-full max-w-2xl my-8 shadow-2xl"
                    @click.stop>

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/[0.08]">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <component :is="editMode ? Edit2 : Plus" class="w-4 h-4 text-sky-500" />
                            {{ editMode ? 'Edit Notification' : 'Create Notification' }}
                        </h2>
                        <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/8 transition-all">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-5">

                        <!-- Title -->
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Title *</label>
                            <input v-model="form.title" type="text" required placeholder="Notification title"
                                :class="['w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', form.errors.title ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                            <p v-if="form.errors.title" class="mt-1 text-[11px] text-rose-500">{{ form.errors.title }}</p>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Message *</label>
                            <textarea v-model="form.message" rows="3" required placeholder="Notification message…"
                                :class="['w-full px-3 py-2.5 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all resize-none', form.errors.message ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                        </div>

                        <!-- Type / Category / Priority row -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Type</label>
                                <select v-model="form.type" class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                                    <option v-for="t in typeOptions" :key="t" :value="t">{{ typeLabel(t) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Category</label>
                                <select v-model="form.category" class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                                    <option value="system">System</option>
                                    <option value="transaction">Transaction</option>
                                    <option value="security">Security</option>
                                    <option value="promotion">Promotion</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Priority</label>
                                <select v-model="form.priority" class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                                    <option value="info">Info</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                    <option value="promotion">Promotion</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action URL / Label — required pair -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Action URL (optional)</label>
                                <input v-model="form.action_url" type="text" placeholder="https://… or /deposit"
                                    class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest mb-1.5"
                                    :class="form.action_url ? 'text-sky-500' : 'text-slate-400 dark:text-slate-600'">
                                    CTA Button Text {{ form.action_url ? '*' : '(optional)' }}
                                </label>
                                <input v-model="form.action_label" type="text"
                                    :placeholder="form.action_url ? 'Deposit Now, View Order, Claim Reward…' : 'View Details'"
                                    :required="!!form.action_url"
                                    :class="['w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all',
                                        form.action_url ? 'border-sky-400/50 dark:border-sky-500/30' : 'border-slate-200 dark:border-white/8']" />
                                <p v-if="form.action_url && !form.action_label" class="mt-1 text-[10.5px] text-amber-500">⚠ CTA text required when URL is set</p>
                            </div>
                        </div>

                        <!-- CTA preview -->
                        <div v-if="form.action_url && form.action_label" class="flex items-center gap-3">
                            <span class="text-[11px] text-slate-400 dark:text-slate-600">Preview:</span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11.5px] font-bold"
                                style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.22); color: #38bdf8">
                                {{ form.action_label }}
                                <ExternalLink class="w-3 h-3" />
                            </span>
                        </div>

                        <!-- Open in new tab (conditional) -->
                        <div v-if="form.action_url" class="flex items-center gap-3">
                            <button type="button" @click="form.open_in_new_tab = !form.open_in_new_tab"
                                class="w-10 h-5 rounded-full transition-colors relative flex-shrink-0"
                                :class="form.open_in_new_tab ? 'bg-sky-500' : 'bg-slate-300 dark:bg-white/20'">
                                <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all" :class="form.open_in_new_tab ? 'left-5' : 'left-0.5'" />
                            </button>
                            <span class="text-[13px] font-medium text-slate-600 dark:text-slate-400">Open link in new tab</span>
                        </div>

                        <!-- Scheduling / Expiry -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Schedule (optional)</label>
                                <input v-model="form.scheduled_at" type="datetime-local"
                                    class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1.5">Expires At (optional)</label>
                                <input v-model="form.expires_at" type="datetime-local"
                                    class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                        </div>

                        <!-- Pin toggle -->
                        <div class="flex items-center gap-3">
                            <button type="button" @click="form.is_pinned = !form.is_pinned"
                                class="w-10 h-5 rounded-full transition-colors relative flex-shrink-0"
                                :class="form.is_pinned ? 'bg-amber-500' : 'bg-slate-300 dark:bg-white/20'">
                                <span class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all" :class="form.is_pinned ? 'left-5' : 'left-0.5'" />
                            </button>
                            <span class="text-[13px] font-medium text-slate-600 dark:text-slate-400">📌 Pin this notification (always shown at top)</span>
                        </div>

                        <!-- Target audience -->
                        <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 border border-slate-200 dark:border-white/8">
                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-3">
                                <Target class="inline w-3 h-3 mr-1" />
                                Target Audience
                            </label>
                            <select v-model="form.target_type" class="w-full h-10 px-3 text-[13px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all mb-3">
                                <optgroup label="General">
                                    <option value="all">🌍 All Users</option>
                                    <option value="active">✅ Active Users (last 30 days)</option>
                                    <option value="inactive">😴 Inactive Users (over 30 days)</option>
                                    <option value="new_users">🆕 New Users (joined recently)</option>
                                    <option value="recent_active">🔥 Recently Active Users</option>
                                </optgroup>
                                <optgroup label="Account Status">
                                    <option value="verified">✔️ Verified Users (email confirmed)</option>
                                    <option value="unverified">📧 Unverified Users</option>
                                </optgroup>
                                <optgroup label="Balance / Activity">
                                    <option value="with_balance">💵 Users With Balance</option>
                                    <option value="without_balance">🪙 Users Without Balance</option>
                                    <option value="balance_range">💰 Users in Balance Range</option>
                                    <option value="purchase_activity">🛒 Users By Purchase Count</option>
                                </optgroup>
                                <optgroup label="Advanced">
                                    <option value="role">🏷️ By Role</option>
                                    <option value="country">🌐 By Country</option>
                                    <option value="date_joined">📅 By Join Date Range</option>
                                    <option value="specific">👤 Specific Users (by ID)</option>
                                </optgroup>
                            </select>

                            <!-- Target config inputs -->
                            <div v-if="form.target_type === 'new_users'" class="mt-2">
                                <label class="text-[11px] text-slate-400 dark:text-slate-600 mb-1 block">Joined in the last how many days?</label>
                                <input v-model="targetNewUserDays" type="number" min="1" max="365" placeholder="30"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'recent_active'" class="mt-2">
                                <label class="text-[11px] text-slate-400 dark:text-slate-600 mb-1 block">Active in the last how many days?</label>
                                <input v-model="targetRecentDays" type="number" min="1" max="90" placeholder="7"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'role'" class="mt-2">
                                <input v-model="targetRoleVal" type="text" placeholder="user, admin, moderator…"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'country'" class="mt-2">
                                <input v-model="targetCountryVal" type="text" placeholder="Nigeria, United States…"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'balance_range'" class="mt-2 grid grid-cols-2 gap-3">
                                <input v-model="targetMinBalance" type="number" step="0.01" placeholder="Min ($)"
                                    class="h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                <input v-model="targetMaxBalance" type="number" step="0.01" placeholder="Max ($)"
                                    class="h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'specific'" class="mt-2">
                                <input v-model="targetUserIds" type="text" placeholder="User IDs comma-separated: 1, 2, 3"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div v-if="form.target_type === 'date_joined'" class="mt-2 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] text-slate-400 dark:text-slate-600 mb-1 block">From</label>
                                    <input v-model="targetFromDate" type="date"
                                        class="h-9 w-full px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-400 dark:text-slate-600 mb-1 block">To</label>
                                    <input v-model="targetToDate" type="date"
                                        class="h-9 w-full px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                </div>
                            </div>
                            <div v-if="form.target_type === 'purchase_activity'" class="mt-2">
                                <label class="text-[11px] text-slate-400 dark:text-slate-600 mb-1 block">Minimum number of orders</label>
                                <input v-model="targetMinOrders" type="number" min="1" placeholder="1"
                                    class="w-full h-9 px-3 text-[12.5px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                        </div>

                        <!-- Form actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false"
                                class="px-5 py-2.5 rounded-xl text-[13px] font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/8 hover:bg-slate-50 dark:hover:bg-white/5 transition-all active:scale-95">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-[13px] font-semibold text-white shadow-lg transition-all active:scale-95 disabled:opacity-60"
                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 16px rgba(14,165,233,0.3)">
                                <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                                <component :is="editMode ? Check : Send" v-else class="w-3.5 h-3.5" />
                                {{ form.processing ? 'Processing…' : editMode ? 'Update' : (form.scheduled_at ? 'Schedule' : 'Send Now') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

    </AdminLayout>
</template>

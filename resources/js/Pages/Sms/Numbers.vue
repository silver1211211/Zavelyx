<script setup>
import ServiceLogo from '@/Components/ServiceLogo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle, Check, CheckCircle2, Clock, Copy, CreditCard,
    Loader2, Phone, RefreshCw, ShoppingBag, Smartphone,
    XCircle, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
});

const { symbol, convertAmount } = useCurrency();

const orders   = ref(props.orders ?? []);
const polling  = ref(false);
const activeTab = ref('active');
let pollTimer  = null;

// ── Helpers ──────────────────────────────────────────────────────────────────

function isActive(o)   { return ['PENDING', 'RECEIVED'].includes(o.status); }
function isTerminal(o) { return !isActive(o); }

function svcLabel(id) {
    const map = {
        telegram:'Telegram', whatsapp:'WhatsApp', google:'Google', discord:'Discord',
        openai:'OpenAI', tiktok:'TikTok', instagram:'Instagram', facebook:'Facebook',
        twitter:'Twitter / X', amazon:'Amazon', microsoft:'Microsoft',
    };
    return map[(id ?? '').toLowerCase()] ?? (id ?? '').replace(/[-_]/g,' ').replace(/\b\w/g, c => c.toUpperCase());
}

function cLabel(c) {
    return (c ?? '').replace(/_/g,' ').replace(/\b\w/g, x => x.toUpperCase());
}

const OPERATOR_NAMES = {
    any: 'Best Available',
    virtual1: 'Virtual 1', virtual2: 'Virtual 2', virtual3: 'Virtual 3',
    telkomsel: 'Telkomsel', beeline: 'Beeline', megafon: 'MegaFon',
    tele2: 'Tele 2', mts: 'MTS', yota: 'Yota',
    tmobile: 'T-Mobile', att: 'AT&T', verizon: 'Verizon',
    lyca: 'Lyca Mobile', lebara: 'Lebara', three: 'Three',
    orange: 'Orange', vodafone: 'Vodafone', o2: 'O2',
};
function operatorLabel(name) {
    if (!name || name === 'any') return 'Best Available';
    return OPERATOR_NAMES[name.toLowerCase()]
        ?? name.replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function countdown(o) {
    if (!o.expires_at || isTerminal(o)) return null;
    const diff = Math.max(0, Math.floor((new Date(o.expires_at) - Date.now()) / 1000));
    if (diff === 0) return null;
    const m = Math.floor(diff / 60), s = diff % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

// ── Flags ─────────────────────────────────────────────────────────────────────

const FLAGS = {
    usa:'🇺🇸',brazil:'🇧🇷',india:'🇮🇳',indonesia:'🇮🇩',nigeria:'🇳🇬',
    vietnam:'🇻🇳',philippines:'🇵🇭',bangladesh:'🇧🇩',pakistan:'🇵🇰',cambodia:'🇰🇭',
    egypt:'🇪🇬',ghana:'🇬🇭',kenya:'🇰🇪',ethiopia:'🇪🇹',southafrica:'🇿🇦',
    morocco:'🇲🇦',tanzania:'🇹🇿',senegal:'🇸🇳',ivorycoast:'🇨🇮',
    russia:'🇷🇺',china:'🇨🇳',uk:'🇬🇧',france:'🇫🇷',germany:'🇩🇪',
    ukraine:'🇺🇦',myanmar:'🇲🇲',laos:'🇱🇦',thailand:'🇹🇭',malaysia:'🇲🇾',
    iran:'🇮🇷',iraq:'🇮🇶',turkey:'🇹🇷',saudiarabia:'🇸🇦',uae:'🇦🇪',
    israel:'🇮🇱',colombia:'🇨🇴',mexico:'🇲🇽',argentina:'🇦🇷',peru:'🇵🇪',
    kyrgyzstan:'🇰🇬',kazakhstan:'🇰🇿',uzbekistan:'🇺🇿',tajikistan:'🇹🇯',
    moldova:'🇲🇩',romania:'🇷🇴',poland:'🇵🇱',czechia:'🇨🇿',
};
function flag(code) {
    const k = (code ?? '').toLowerCase().replace(/[\s_-]/g, '');
    return FLAGS[k] ?? '🌐';
}

// ── Tabs ─────────────────────────────────────────────────────────────────────

const TABS = [
    { key: 'active',   label: 'Active',      statuses: ['PENDING', 'RECEIVED'] },
    { key: 'waiting',  label: 'Waiting SMS', statuses: ['PENDING'] },
    { key: 'received', label: 'Received',    statuses: ['RECEIVED'] },
    { key: 'done',     label: 'Completed',   statuses: ['FINISHED'] },
    { key: 'cancelled',label: 'Cancelled',   statuses: ['CANCELLED', 'BANNED', 'TIMEOUT'] },
    { key: 'expired',  label: 'Expired',     statuses: ['EXPIRED'] },
    { key: 'all',      label: 'All',         statuses: null },
];

const tabCounts = computed(() => {
    const counts = {};
    for (const t of TABS) {
        counts[t.key] = t.statuses
            ? orders.value.filter(o => t.statuses.includes(o.status)).length
            : orders.value.length;
    }
    return counts;
});

const visibleTabs = computed(() => TABS.filter(t =>
    t.key === 'all' ||
    t.key === 'active' ||
    t.key === 'waiting' ||
    tabCounts.value[t.key] > 0
));

const filteredOrders = computed(() => {
    const tab = TABS.find(t => t.key === activeTab.value);
    if (!tab || !tab.statuses) return orders.value;
    return orders.value.filter(o => tab.statuses.includes(o.status));
});

// ── Status colours ────────────────────────────────────────────────────────────

function statusColor(s) {
    const map = {
        PENDING:   'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/25',
        RECEIVED:  'bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-500/25',
        FINISHED:  'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/25',
        CANCELLED: 'bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/[0.08]',
        BANNED:    'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/25',
        EXPIRED:   'bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/[0.08]',
        TIMEOUT:   'bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/[0.08]',
    };
    return map[s] ?? map.PENDING;
}

function statusDot(s) {
    return {
        PENDING:  'bg-amber-400 animate-pulse',
        RECEIVED: 'bg-sky-400 animate-pulse',
        FINISHED: 'bg-emerald-400',
        CANCELLED:'bg-slate-400',
        BANNED:   'bg-rose-400',
        EXPIRED:  'bg-slate-400',
        TIMEOUT:  'bg-slate-400',
    }[s] ?? 'bg-slate-400';
}

// ── Actions ───────────────────────────────────────────────────────────────────

const copied      = ref(null);
const cancelling  = ref(null);
const finishing   = ref(null);
const actionError = ref({});

function getCsrf() {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
}

async function copyText(text, key) {
    try {
        await navigator.clipboard.writeText(String(text));
        copied.value = key;
        setTimeout(() => { if (copied.value === key) copied.value = null; }, 2000);
    } catch {}
}

async function cancelOrder(order) {
    if (cancelling.value === order.id) return;
    cancelling.value = order.id;
    const errs = { ...actionError.value };
    delete errs[order.id];
    actionError.value = errs;
    try {
        const res = await fetchTimeout(`/sms/orders/${order.id}/cancel`, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
        }, 15000);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error ?? 'Cancel failed.');
        updateOrder(data.order);
        window.dispatchEvent(new Event('balance-refresh'));
    } catch (e) {
        actionError.value = { ...actionError.value, [order.id]: e.message };
    } finally {
        cancelling.value = null;
    }
}

async function finishOrder(order) {
    if (finishing.value === order.id) return;
    finishing.value = order.id;
    const errs = { ...actionError.value };
    delete errs[order.id];
    actionError.value = errs;
    try {
        const res = await fetchTimeout(`/sms/orders/${order.id}/finish`, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
        }, 15000);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error ?? 'Finish failed.');
        updateOrder(data.order);
    } catch (e) {
        actionError.value = { ...actionError.value, [order.id]: e.message };
    } finally {
        finishing.value = null;
    }
}

function updateOrder(updated) {
    const idx = orders.value.findIndex(o => o.id === updated.id);
    if (idx >= 0) orders.value[idx] = updated;
    else orders.value.unshift(updated);
}

// ── Polling ───────────────────────────────────────────────────────────────────

async function pollActiveOrders() {
    if (polling.value) return;
    const active = orders.value.filter(isActive);
    if (!active.length) return;
    polling.value = true;
    await Promise.allSettled(active.map(async (o) => {
        try {
            const res = await fetchTimeout(`/sms/orders/${o.id}/poll`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            }, 12000);
            if (!res.ok) return;
            const data = await res.json();
            if (data.order) updateOrder(data.order);
        } catch {}
    }));
    polling.value = false;
}

onMounted(() => { pollTimer = setInterval(pollActiveOrders, 5000); });
onUnmounted(() => { clearInterval(pollTimer); });
</script>

<template>
    <Head title="My Numbers — Zavelyx" />
    <AuthenticatedLayout>

        <!-- Header -->
        <div class="mb-5 flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-[20px] font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                        style="background:linear-gradient(135deg,#10b981,#0ea5e9)">
                        <ShoppingBag class="w-4 h-4 text-white" :stroke-width="2" />
                    </div>
                    My Numbers
                </h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                    OTP sessions · auto-checks every 5 s
                    <span v-if="polling" class="inline-flex items-center gap-1 text-sky-500">
                        <Loader2 class="w-3 h-3 animate-spin" />
                        Syncing
                    </span>
                </p>
            </div>
            <Link :href="route('sms.buy')"
                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-[13px] font-bold text-white
                    transition-all active:scale-95 hover:shadow-lg flex-shrink-0"
                style="background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 4px 16px rgba(16,185,129,0.25)">
                <Phone class="w-3.5 h-3.5" />
                Buy Number
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="orders.length === 0"
            class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10
                bg-white dark:bg-[#0c1829] p-12 sm:p-16 text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center"
                style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(14,165,233,0.07));border:1px solid rgba(16,185,129,0.15)">
                <Smartphone class="w-8 h-8 text-emerald-500" :stroke-width="1.5" />
            </div>
            <h2 class="text-[16px] font-black text-slate-800 dark:text-white mb-2">No numbers yet</h2>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 max-w-xs mx-auto mb-5 leading-relaxed">
                Buy a virtual number to start receiving OTP codes instantly. Full refund if cancelled.
            </p>
            <Link :href="route('sms.buy')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-[13px] text-white
                    transition-all active:scale-95 hover:shadow-lg"
                style="background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 6px 20px rgba(16,185,129,0.25)">
                <Phone class="w-4 h-4" />
                Buy My First Number
            </Link>
        </div>

        <template v-else>
            <!-- Status tabs -->
            <div class="flex items-center gap-1.5 mb-4 overflow-x-auto scrollbar-none pb-1">
                <button v-for="tab in visibleTabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[12px] font-bold whitespace-nowrap
                        transition-all flex-shrink-0"
                    :class="activeTab === tab.key
                        ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30'
                        : 'bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/[0.12]'">
                    {{ tab.label }}
                    <span v-if="tabCounts[tab.key] > 0"
                        class="ml-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full text-[10px] font-black px-1"
                        :class="activeTab === tab.key
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 dark:bg-white/[0.1] text-slate-600 dark:text-slate-300'">
                        {{ tabCounts[tab.key] }}
                    </span>
                </button>
            </div>

            <!-- Empty tab state -->
            <div v-if="filteredOrders.length === 0"
                class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10
                    bg-white dark:bg-[#0c1829] py-10 text-center">
                <p class="text-[13px] font-semibold text-slate-400 dark:text-slate-400">
                    {{ activeTab === 'waiting'   ? 'No numbers waiting for SMS' :
                       activeTab === 'received'  ? 'No SMS received yet' :
                       activeTab === 'done'      ? 'No completed orders yet' :
                       activeTab === 'cancelled' ? 'No cancelled orders' :
                       activeTab === 'expired'   ? 'No expired numbers' :
                       'No active numbers right now' }}
                </p>
                <p v-if="activeTab === 'active'" class="text-[12px] text-slate-400 dark:text-slate-600 mt-1">
                    <Link :href="route('sms.buy')" class="text-sky-500 hover:underline font-semibold">Buy a number</Link> to get started
                </p>
            </div>

            <!-- Order cards -->
            <div v-else class="space-y-3">
                <Transition
                    v-for="order in filteredOrders" :key="order.id"
                    appear
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0">

                    <div class="rounded-2xl border bg-white dark:bg-[#0c1829] overflow-hidden"
                        :class="isActive(order)
                            ? 'border-sky-200 dark:border-sky-500/25 shadow-sm shadow-sky-500/10'
                            : 'border-slate-200 dark:border-white/[0.07]'">

                        <!-- Card header -->
                        <div class="px-4 py-3.5 flex items-center justify-between gap-3 border-b"
                            :class="isActive(order)
                                ? 'border-sky-100 dark:border-sky-500/10 bg-sky-50/50 dark:bg-sky-500/[0.03]'
                                : 'border-slate-100 dark:border-white/[0.05]'">

                            <div class="flex items-center gap-2.5 min-w-0">
                                <ServiceLogo :service="order.service" :size="30" class="rounded-xl flex-shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-[13px] font-bold text-slate-800 dark:text-white leading-tight">
                                        {{ svcLabel(order.service) }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-px truncate">
                                        <template v-if="order.country === 'any'">Best Available</template>
                                        <template v-else>
                                            {{ flag(order.country) }} {{ cLabel(order.country) }}<template v-if="order.operator && order.operator !== 'any'"> · {{ operatorLabel(order.operator) }}</template>
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <div v-if="isActive(order) && countdown(order)"
                                    class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold
                                        bg-amber-50 dark:bg-amber-500/[0.1] text-amber-600 dark:text-amber-400
                                        border border-amber-200 dark:border-amber-500/20">
                                    <Clock class="w-3 h-3 flex-shrink-0" />
                                    {{ countdown(order) }}
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold border"
                                    :class="statusColor(order.status)">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="statusDot(order.status)" />
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Card body -->
                        <div class="px-4 py-4">

                            <!-- Phone row -->
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1">Phone Number</p>
                                    <p class="text-[20px] sm:text-[24px] font-black text-slate-800 dark:text-white font-mono tracking-wide leading-tight truncate">
                                        {{ order.phone_number }}
                                    </p>
                                    <!-- Mobile countdown -->
                                    <div v-if="isActive(order) && countdown(order)"
                                        class="sm:hidden mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                        <Clock class="w-3 h-3" />
                                        {{ countdown(order) }} left
                                    </div>
                                </div>
                                <button @click="copyText(order.phone_number, order.id)"
                                    class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-bold transition-all active:scale-95"
                                    :class="copied === order.id
                                        ? 'bg-emerald-100 dark:bg-emerald-500/[0.15] text-emerald-600 dark:text-emerald-400'
                                        : 'bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/[0.1]'">
                                    <Check v-if="copied === order.id" class="w-3.5 h-3.5" :stroke-width="2.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    {{ copied === order.id ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>

                            <!-- OTP code — animated entrance -->
                            <Transition
                                enter-active-class="transition-all duration-500 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100">
                                <div v-if="order.otp_code"
                                    class="rounded-2xl px-5 py-5 mb-4 text-center relative overflow-hidden"
                                    style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.05));border:1px solid rgba(16,185,129,0.25)">
                                    <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full blur-2xl opacity-20 pointer-events-none"
                                        style="background:radial-gradient(circle,#10b981,transparent)" />
                                    <div class="flex items-center justify-center gap-2 mb-2">
                                        <Zap class="w-3.5 h-3.5 text-emerald-500" :stroke-width="2.5" />
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">SMS Received</p>
                                    </div>
                                    <p class="text-[44px] sm:text-[52px] font-black text-slate-800 dark:text-white font-mono tracking-[0.2em] leading-none mb-3">
                                        {{ order.otp_code }}
                                    </p>
                                    <button @click="copyText(order.otp_code, order.id + '-otp')"
                                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-[12px] font-bold transition-all active:scale-95"
                                        :class="copied === order.id + '-otp'
                                            ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                                            : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20'">
                                        <Check v-if="copied === order.id + '-otp'" class="w-3.5 h-3.5" :stroke-width="2.5" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        {{ copied === order.id + '-otp' ? 'Copied!' : 'Copy Code' }}
                                    </button>
                                </div>
                            </Transition>

                            <!-- Waiting indicator -->
                            <div v-if="isActive(order) && !order.otp_code"
                                class="rounded-2xl px-4 py-3.5 mb-4 flex items-center gap-3
                                    bg-sky-50 dark:bg-sky-500/[0.06] border border-sky-200 dark:border-sky-500/15">
                                <Loader2 class="w-4 h-4 text-sky-500 animate-spin flex-shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-[12.5px] font-bold text-sky-700 dark:text-sky-400">Waiting for SMS…</p>
                                    <p class="text-[11px] text-sky-600 dark:text-sky-500 mt-px">Auto-checking every 5 seconds</p>
                                </div>
                            </div>

                            <!-- SMS messages log -->
                            <div v-if="order.sms_messages?.length > 0" class="mb-4">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-2">
                                    Messages ({{ order.sms_messages.length }})
                                </p>
                                <div class="space-y-2">
                                    <div v-for="msg in order.sms_messages" :key="msg.id"
                                        class="rounded-xl px-3.5 py-3 bg-slate-50 dark:bg-white/[0.03]
                                            border border-slate-100 dark:border-white/[0.05]">
                                        <div class="flex items-center justify-between mb-1 gap-2">
                                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300 truncate">{{ msg.sender ?? 'Unknown' }}</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-600 flex-shrink-0">
                                                {{ new Date(msg.received_at).toLocaleTimeString() }}
                                            </span>
                                        </div>
                                        <p class="text-[12.5px] text-slate-700 dark:text-slate-200 leading-relaxed">{{ msg.message }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action error -->
                            <div v-if="actionError[order.id]"
                                class="flex items-center gap-2 px-3 py-2.5 rounded-xl mb-3
                                    bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/15">
                                <AlertCircle class="w-3.5 h-3.5 text-rose-500 flex-shrink-0" />
                                <p class="text-[11.5px] text-rose-700 dark:text-rose-400">{{ actionError[order.id] }}</p>
                            </div>

                            <!-- Active action buttons -->
                            <div v-if="isActive(order)" class="flex gap-2">
                                <button @click="finishOrder(order)"
                                    :disabled="finishing === order.id || cancelling === order.id"
                                    class="flex-1 flex items-center justify-center gap-2 font-bold text-[12.5px]
                                        rounded-xl text-white transition-all active:scale-[0.98] disabled:opacity-50"
                                    style="height:42px;background:linear-gradient(135deg,#10b981,#0ea5e9)">
                                    <Loader2 v-if="finishing === order.id" class="w-3.5 h-3.5 animate-spin" />
                                    <CheckCircle2 v-else class="w-3.5 h-3.5" :stroke-width="2.5" />
                                    {{ finishing === order.id ? 'Finishing…' : 'Mark Done' }}
                                </button>
                                <button @click="cancelOrder(order)"
                                    :disabled="cancelling === order.id || finishing === order.id"
                                    class="flex-1 flex items-center justify-center gap-2 font-semibold text-[12.5px]
                                        rounded-xl transition-all active:scale-[0.98] disabled:opacity-50
                                        text-slate-600 dark:text-slate-400
                                        bg-slate-100 dark:bg-white/[0.06]
                                        hover:bg-slate-200 dark:hover:bg-white/[0.1]
                                        border border-slate-200 dark:border-white/[0.08]"
                                    style="height:42px">
                                    <Loader2 v-if="cancelling === order.id" class="w-3.5 h-3.5 animate-spin" />
                                    <XCircle v-else class="w-3.5 h-3.5" />
                                    {{ cancelling === order.id ? 'Cancelling…' : 'Cancel & Refund' }}
                                </button>
                            </div>

                            <!-- Terminal footer -->
                            <div v-else class="flex items-center justify-between gap-2 pt-1">
                                <div class="flex items-center gap-1.5 text-[11.5px]">
                                    <CheckCircle2 v-if="order.status === 'FINISHED'"
                                        class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" :stroke-width="2.5" />
                                    <XCircle v-else-if="order.status === 'CANCELLED'"
                                        class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                    <span class="text-slate-400 dark:text-slate-600">
                                        {{ order.status === 'FINISHED' ? 'Completed' : order.status }}
                                        {{ order.completed_at ? ' · ' + new Date(order.completed_at).toLocaleDateString() : '' }}
                                    </span>
                                </div>
                                <span class="text-[12px] font-bold text-slate-500 dark:text-slate-400 font-mono flex-shrink-0">
                                    {{ symbol }}{{ convertAmount(order.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </template>

    </AuthenticatedLayout>
</template>

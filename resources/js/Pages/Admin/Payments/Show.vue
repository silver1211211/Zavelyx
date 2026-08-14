<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    Code2,
    CreditCard,
    ExternalLink,
    Hash,
    Loader2,
    RefreshCw,
    ThumbsDown,
    ThumbsUp,
    User,
    Wifi,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const STATUS_STYLE = {
    waiting:        { color: 'text-amber-600 dark:text-amber-400',   bg: 'bg-amber-50 dark:bg-amber-500/10',   dot: 'bg-amber-400' },
    confirming:     { color: 'text-sky-600 dark:text-sky-400',       bg: 'bg-sky-50 dark:bg-sky-500/10',       dot: 'bg-sky-400' },
    confirmed:      { color: 'text-sky-600 dark:text-sky-400',       bg: 'bg-sky-50 dark:bg-sky-500/10',       dot: 'bg-sky-400' },
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
    return new Date(iso).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}

const LOG_EVENT_STYLE = {
    credit_succeeded:        { color: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-400' },
    manual_approve:          { color: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-400' },
    credit_failed:           { color: 'text-rose-600 dark:text-rose-400',       dot: 'bg-rose-400' },
    credit_failed_permanent: { color: 'text-rose-600 dark:text-rose-400',       dot: 'bg-rose-400' },
    signature_invalid:       { color: 'text-rose-600 dark:text-rose-400',       dot: 'bg-rose-400' },
    manual_reject:           { color: 'text-rose-600 dark:text-rose-400',       dot: 'bg-rose-400' },
    webhook_received:        { color: 'text-sky-600 dark:text-sky-400',         dot: 'bg-sky-400' },
    credit_queued:           { color: 'text-sky-600 dark:text-sky-400',         dot: 'bg-sky-400' },
    credit_attempted:        { color: 'text-amber-600 dark:text-amber-400',     dot: 'bg-amber-400' },
    retry_scheduled:         { color: 'text-amber-600 dark:text-amber-400',     dot: 'bg-amber-400' },
    poll_triggered:          { color: 'text-violet-600 dark:text-violet-400',   dot: 'bg-violet-400' },
    poll_paid:               { color: 'text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-400' },
};
function ls(event) {
    return LOG_EVENT_STYLE[event] ?? { color: 'text-slate-500 dark:text-slate-400', dot: 'bg-slate-400' };
}

const processing = ref(null);

function invoiceAction(routeName) {
    processing.value = routeName;
    router.post(route(routeName, props.invoice.id), {}, {
        onFinish: () => { processing.value = null; },
        preserveScroll: true,
    });
}

const showRawPayload = ref(false);
const rawJson = computed(() => {
    try {
        return JSON.stringify(props.invoice.gateway_payload, null, 2);
    } catch {
        return String(props.invoice.gateway_payload ?? '');
    }
});
</script>

<template>
    <Head :title="`Deposit ${invoice.reference.slice(0, 8)} — Admin`" />
    <AdminLayout>

        <!-- Back + header -->
        <div class="mb-6 flex items-center gap-4">
            <Link :href="route('admin.payments.index')"
                class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-[12px] font-semibold
                    bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                    text-slate-500 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400
                    hover:border-sky-300 dark:hover:border-sky-500/40 transition-all">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back
            </Link>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Finance / Deposit</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white font-mono">{{ invoice.reference }}</h1>
            </div>
        </div>

        <!-- Flash -->
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Left column: details -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Overview card -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold text-[12px]', ss(invoice.status).bg, ss(invoice.status).color]">
                                <span :class="['w-2 h-2 rounded-full flex-shrink-0', ss(invoice.status).dot]"></span>
                                {{ invoice.status_label }}
                            </span>
                            <span v-if="invoice.is_credited"
                                class="ml-2 text-[11px] font-bold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-500/20">
                                CREDITED
                            </span>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-1.5">
                            <button v-if="!invoice.is_credited"
                                @click="invoiceAction('admin.payments.approve')"
                                :disabled="!!processing"
                                class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-[12px] font-semibold
                                    bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400
                                    border border-emerald-200 dark:border-emerald-500/20
                                    hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors disabled:opacity-50">
                                <Loader2 v-if="processing === 'admin.payments.approve'" class="w-3.5 h-3.5 animate-spin" />
                                <ThumbsUp v-else class="w-3.5 h-3.5" />
                                Approve
                            </button>
                            <button v-if="invoice.can_retry"
                                @click="invoiceAction('admin.payments.retry')"
                                :disabled="!!processing"
                                class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-[12px] font-semibold
                                    bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400
                                    border border-sky-200 dark:border-sky-500/20
                                    hover:bg-sky-100 dark:hover:bg-sky-500/20 transition-colors disabled:opacity-50">
                                <Loader2 v-if="processing === 'admin.payments.retry'" class="w-3.5 h-3.5 animate-spin" />
                                <RefreshCw v-else class="w-3.5 h-3.5" />
                                Retry
                            </button>
                            <button v-if="!invoice.is_credited && !['finished','failed','refunded','expired'].includes(invoice.status)"
                                @click="invoiceAction('admin.payments.reject')"
                                :disabled="!!processing"
                                class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-[12px] font-semibold
                                    bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400
                                    border border-rose-200 dark:border-rose-500/20
                                    hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors disabled:opacity-50">
                                <ThumbsDown class="w-3.5 h-3.5" />
                                Reject
                            </button>
                            <a v-if="invoice.payment_url" :href="invoice.payment_url" target="_blank"
                                class="flex items-center gap-1.5 h-8 px-3 rounded-lg text-[12px] font-semibold
                                    bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.08]
                                    text-slate-500 dark:text-slate-400 hover:text-sky-500 dark:hover:text-sky-400
                                    hover:border-sky-300 dark:hover:border-sky-500/40 transition-colors">
                                <ExternalLink class="w-3.5 h-3.5" />
                                Gateway
                            </a>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="flex items-end gap-3 mb-5 pb-5 border-b border-slate-100 dark:border-sky-500/[0.08]">
                        <div>
                            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Requested</p>
                            <p class="text-3xl font-black text-slate-900 dark:text-white">${{ invoice.price_amount.toFixed(2) }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5 uppercase">{{ invoice.price_currency }}</p>
                        </div>
                        <div v-if="invoice.amount_received" class="ml-6">
                            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Received</p>
                            <p class="text-xl font-bold text-slate-700 dark:text-slate-300">{{ Number(invoice.amount_received).toFixed(8) }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5 uppercase">{{ invoice.pay_currency }}</p>
                        </div>
                        <div v-if="invoice.retry_count > 0" class="ml-6">
                            <p class="text-[11px] font-semibold text-amber-500 uppercase tracking-wide mb-0.5">Retries</p>
                            <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ invoice.retry_count }}</p>
                        </div>
                    </div>

                    <!-- Field grid -->
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Gateway</dt>
                            <dd class="text-[13px] font-semibold text-slate-700 dark:text-slate-300 capitalize">{{ invoice.gateway }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Currency / Network</dt>
                            <dd class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">
                                <span class="uppercase">{{ invoice.pay_currency ?? '—' }}</span>
                                <span v-if="invoice.network" class="text-slate-400 dark:text-slate-600"> / {{ invoice.network }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Gateway Invoice ID</dt>
                            <dd class="font-mono text-[12px] text-slate-600 dark:text-slate-400 break-all">{{ invoice.gateway_invoice_id || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Gateway Payment ID</dt>
                            <dd class="font-mono text-[12px] text-slate-600 dark:text-slate-400 break-all">{{ invoice.gateway_payment_id || '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Blockchain Hash</dt>
                            <dd class="font-mono text-[12px] text-violet-600 dark:text-violet-400 break-all">{{ invoice.blockchain_hash || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Created</dt>
                            <dd class="text-[12.5px] text-slate-600 dark:text-slate-400">{{ fmtDate(invoice.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Callback Received</dt>
                            <dd class="text-[12.5px] text-slate-600 dark:text-slate-400">{{ fmtDate(invoice.callback_received_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Credited At</dt>
                            <dd class="text-[12.5px] text-emerald-600 dark:text-emerald-400 font-semibold">{{ fmtDate(invoice.credited_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Processed At</dt>
                            <dd class="text-[12.5px] text-slate-600 dark:text-slate-400">{{ fmtDate(invoice.processed_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10.5px] font-semibold text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-0.5">Callback IP</dt>
                            <dd class="font-mono text-[12px] text-slate-600 dark:text-slate-400">{{ invoice.ip_address || '—' }}</dd>
                        </div>
                        <div v-if="invoice.failure_reason" class="sm:col-span-2">
                            <dt class="text-[10.5px] font-semibold text-rose-500 uppercase tracking-wide mb-0.5">Failure Reason</dt>
                            <dd class="text-[12px] text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/[0.08] p-2 rounded-lg">{{ invoice.failure_reason }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Raw callback payload -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                            <Code2 class="w-4 h-4 text-sky-500" />
                            Raw Callback Payload
                        </h3>
                        <button @click="showRawPayload = !showRawPayload"
                            class="text-[12px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                            {{ showRawPayload ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <div v-if="showRawPayload">
                        <pre v-if="rawJson" class="text-[11px] font-mono text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-white/[0.03] p-3 rounded-xl overflow-x-auto border border-slate-100 dark:border-white/[0.05] whitespace-pre-wrap">{{ rawJson }}</pre>
                        <p v-else class="text-[12px] text-slate-400 dark:text-slate-600">No payload stored</p>
                    </div>
                    <p v-else class="text-[12px] text-slate-400 dark:text-slate-600">Click "Show" to view raw JSON</p>
                </div>
            </div>

            <!-- Right column: user + logs -->
            <div class="space-y-5">

                <!-- User card -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <h3 class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-3">User</h3>
                    <div v-if="invoice.user">
                        <p class="font-bold text-slate-800 dark:text-slate-200">{{ invoice.user.name }}</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">{{ invoice.user.email }}</p>
                        <Link :href="route('admin.users.show', invoice.user.id)"
                            class="mt-3 flex items-center gap-1.5 text-[12px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                            <User class="w-3.5 h-3.5" />
                            View Profile
                        </Link>
                    </div>
                    <p v-else class="text-[12px] text-slate-400 dark:text-slate-600">User not found</p>
                </div>

                <!-- Processing log -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <h3 class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-4">Processing Log</h3>
                    <div v-if="invoice.logs && invoice.logs.length" class="space-y-3">
                        <div v-for="log in invoice.logs" :key="log.id" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span :class="['w-2 h-2 rounded-full flex-shrink-0 mt-1.5', ls(log.event).dot]"></span>
                                <div class="w-px flex-1 bg-slate-100 dark:bg-white/[0.05] mt-1"></div>
                            </div>
                            <div class="pb-3 flex-1 min-w-0">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span :class="['text-[11px] font-bold', ls(log.event).color]">{{ log.event }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-600">{{ fmtDate(log.created_at) }}</span>
                                </div>
                                <p v-if="log.message" class="text-[11.5px] text-slate-600 dark:text-slate-400 mt-0.5">{{ log.message }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4">
                        <Clock class="w-7 h-7 text-slate-300 dark:text-slate-700 mx-auto mb-2" />
                        <p class="text-[12px] text-slate-400 dark:text-slate-600">No log entries yet</p>
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

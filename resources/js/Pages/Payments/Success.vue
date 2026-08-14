<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowRight, CheckCircle2, Clock, CreditCard,
    ExternalLink, Loader2, RefreshCw,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    invoice: { type: Object, default: null },
});

// ── Reactive state ────────────────────────────────────────────────────────────
const current   = ref({ ...props.invoice });
const polling   = ref(false);
const pollError = ref(false);
let   pollTimer = null;

const PENDING_STATUSES  = ['waiting', 'confirming', 'confirmed', 'sending'];
const TERMINAL_STATUSES = ['finished', 'failed', 'expired', 'refunded'];

const isPending  = computed(() => current.value && PENDING_STATUSES.includes(current.value.status));
const isFinished = computed(() => current.value?.status === 'finished' && current.value?.is_credited);
const isFailed   = computed(() => ['failed', 'expired'].includes(current.value?.status));

// ── Status polling ────────────────────────────────────────────────────────────
async function pollStatus() {
    if (!current.value?.reference) return;
    try {
        polling.value = true;
        const res = await fetch(`/api/deposits/${current.value.reference}/status`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (!res.ok) { pollError.value = true; return; }
        const data = await res.json();
        current.value = { ...current.value, ...data };
        pollError.value = false;

        // Stop polling on terminal states
        if (data.is_terminal || data.is_credited) {
            stopPolling();
            // Trigger sidebar balance refresh
            if (data.is_credited) {
                window.dispatchEvent(new Event('balance-refresh'));
            }
        }
    } catch {
        pollError.value = true;
    } finally {
        polling.value = false;
    }
}

function startPolling() {
    if (pollTimer) return;
    // Poll immediately, then every 5 seconds
    pollStatus();
    pollTimer = setInterval(pollStatus, 5000);
}

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

onMounted(() => {
    // Start polling only if payment is still pending
    if (isPending.value) startPolling();
});

onUnmounted(() => stopPolling());

// Helpers
function fmtAmount(v) { return Number(v || 0).toFixed(2); }
</script>

<template>
    <Head title="Payment Status — Zavelyx" />
    <AuthenticatedLayout>

        <div class="max-w-lg mx-auto pt-8 pb-16 text-center">

            <!-- ── Status icon ─────────────────────────────────────────────── -->
            <div class="mb-6 flex justify-center">

                <!-- Confirmed & credited -->
                <div v-if="isFinished"
                    class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-emerald-50 dark:bg-emerald-500/[0.1] border-2 border-emerald-200 dark:border-emerald-500/30
                        animate-[pulse_2s_ease-in-out_1]">
                    <CheckCircle2 class="w-10 h-10 text-emerald-500" />
                </div>

                <!-- Failed / expired -->
                <div v-else-if="isFailed"
                    class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-rose-50 dark:bg-rose-500/[0.1] border-2 border-rose-200 dark:border-rose-500/30">
                    <AlertCircle class="w-10 h-10 text-rose-500" />
                </div>

                <!-- Pending — animated ring -->
                <div v-else-if="isPending" class="relative">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-sky-50 dark:bg-sky-500/[0.1] border-2 border-sky-200 dark:border-sky-500/30">
                        <Clock class="w-10 h-10 text-sky-500" />
                    </div>
                    <!-- Spinner ring around the icon -->
                    <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-sky-500 animate-spin" />
                </div>

                <!-- Generic processing -->
                <div v-else
                    class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-slate-100 dark:bg-white/[0.05] border-2 border-slate-200 dark:border-white/[0.08]">
                    <CreditCard class="w-10 h-10 text-slate-400" />
                </div>
            </div>

            <!-- ── Message ──────────────────────────────────────────────────── -->
            <template v-if="isFinished">
                <h1 class="text-[26px] font-black text-slate-900 dark:text-white mb-2">Payment Confirmed!</h1>
                <p class="text-[14px] text-slate-500 dark:text-slate-400 mb-2">
                    <strong class="text-emerald-600 dark:text-emerald-400">${{ fmtAmount(current.price_amount) }}</strong>
                    has been credited to your wallet.
                </p>
                <p v-if="current.pay_currency" class="text-[13px] text-slate-400 dark:text-slate-600">
                    Paid via <span class="uppercase font-bold">{{ current.pay_currency }}</span>
                    <template v-if="current.network"> on {{ current.network }}</template>
                </p>
                <!-- Blockchain hash -->
                <div v-if="current.blockchain_hash"
                    class="mt-3 mx-auto max-w-sm px-3 py-2 rounded-xl bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07]">
                    <p class="text-[10px] text-slate-400 mb-1 uppercase tracking-wider">Transaction Hash</p>
                    <p class="text-[11px] font-mono text-slate-600 dark:text-slate-400 break-all">{{ current.blockchain_hash }}</p>
                </div>
            </template>

            <template v-else-if="isFailed">
                <h1 class="text-[26px] font-black text-slate-900 dark:text-white mb-2">Payment {{ current.status === 'expired' ? 'Expired' : 'Failed' }}</h1>
                <p class="text-[14px] text-slate-500 dark:text-slate-400 mb-2">
                    <template v-if="current.status === 'expired'">
                        The payment window expired. Please create a new deposit if you still wish to add funds.
                    </template>
                    <template v-else>
                        Your payment could not be processed. No funds have been charged.
                    </template>
                </p>
            </template>

            <template v-else-if="isPending">
                <h1 class="text-[26px] font-black text-slate-900 dark:text-white mb-2">
                    <template v-if="current.status === 'waiting'">Waiting for Payment</template>
                    <template v-else>Payment Received</template>
                </h1>
                <p class="text-[14px] text-slate-500 dark:text-slate-400 mb-2">
                    <template v-if="current.status === 'waiting'">
                        Send <strong>${{ fmtAmount(current.price_amount) }}</strong> in crypto to complete your deposit.
                        This page updates automatically.
                    </template>
                    <template v-else>
                        Your payment of <strong>${{ fmtAmount(current.price_amount) }}</strong> is being confirmed on the blockchain.
                    </template>
                </p>

                <!-- Live status badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[12px] font-bold
                    bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 text-sky-700 dark:text-sky-400">
                    <Loader2 class="w-3.5 h-3.5 animate-spin" />
                    {{ current.status_label ?? current.status }}
                    <span v-if="polling" class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse" />
                </div>

                <p class="mt-3 text-[12px] text-slate-400 dark:text-slate-600">
                    This page checks for updates every 5 seconds.
                    Your balance will be credited automatically.
                </p>

                <p v-if="pollError" class="mt-1 text-[11px] text-amber-500">
                    Connection issue — retrying…
                </p>
            </template>

            <template v-else>
                <h1 class="text-[26px] font-black text-slate-900 dark:text-white mb-2">Payment Processing</h1>
                <p class="text-[14px] text-slate-500 dark:text-slate-400">
                    Your payment is being verified. Your balance will be credited once confirmed.
                </p>
            </template>

            <!-- ── Progress steps (for pending state) ───────────────────────── -->
            <div v-if="isPending" class="mt-6 mx-auto max-w-xs">
                <div class="flex items-center gap-2">

                    <!-- Step 1: Payment sent -->
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black"
                            :class="['confirming','confirmed','sending'].includes(current.status)
                                ? 'bg-emerald-500 text-white'
                                : 'bg-sky-500 text-white'">
                            <CheckCircle2 v-if="['confirming','confirmed','sending'].includes(current.status)" class="w-4 h-4" />
                            <span v-else>1</span>
                        </div>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400">Sent</p>
                    </div>

                    <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />

                    <!-- Step 2: Confirming -->
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black"
                            :class="['confirming','confirmed','sending'].includes(current.status)
                                ? 'bg-sky-500 text-white'
                                : 'bg-slate-200 dark:bg-white/[0.1] text-slate-500'">
                            <Loader2 v-if="current.status === 'confirming'" class="w-4 h-4 animate-spin" />
                            <CheckCircle2 v-else-if="['confirmed','sending'].includes(current.status)" class="w-4 h-4" />
                            <span v-else>2</span>
                        </div>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400">Confirming</p>
                    </div>

                    <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />

                    <!-- Step 3: Credited -->
                    <div class="flex flex-col items-center gap-1 flex-1">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black
                            bg-slate-200 dark:bg-white/[0.1] text-slate-500">
                            3
                        </div>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400">Credited</p>
                    </div>
                </div>
            </div>

            <!-- ── Actions ──────────────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                <Link :href="route('deposit.index')"
                    class="flex items-center gap-2 h-11 px-5 rounded-xl text-[13px] font-bold
                        bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07]
                        text-slate-600 dark:text-slate-300
                        hover:border-sky-300 dark:hover:border-sky-500/40 hover:text-sky-600 dark:hover:text-sky-400
                        transition-all">
                    <Clock class="w-4 h-4" />
                    View Deposits
                </Link>
                <Link :href="route('deposit.index')"
                    class="flex items-center gap-2 h-11 px-5 rounded-xl text-[13px] font-bold
                        bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07]
                        text-slate-600 dark:text-slate-300
                        hover:border-sky-300 dark:hover:border-sky-500/40 hover:text-sky-600 dark:hover:text-sky-400
                        transition-all">
                    <RefreshCw class="w-4 h-4" />
                    New Deposit
                </Link>
                <Link :href="route('dashboard')"
                    class="flex items-center gap-2 h-11 px-5 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95"
                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.4)">
                    Go to Dashboard
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

        </div>

    </AuthenticatedLayout>
</template>

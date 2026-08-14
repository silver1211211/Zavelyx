<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowLeft, ArrowRight, CheckCircle2, Clock,
    Copy, ExternalLink, Loader2, RefreshCw, TriangleAlert,
} from 'lucide-vue-next';
import QRCode from 'qrcode';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
});

// ── State ─────────────────────────────────────────────────────────────────────
const current    = ref({ ...props.invoice });
const polling    = ref(false);
const pollError  = ref(false);
const copiedAddr = ref(false);
const copiedMemo = ref(false);
const qrDataUrl  = ref('');
const imgError   = ref(false);
let   pollTimer  = null;
let   cntTimer   = null;

const PENDING_STATUSES = ['waiting', 'confirming', 'confirmed', 'sending'];

const isPending    = computed(() => PENDING_STATUSES.includes(current.value?.status));
const isFinished   = computed(() => current.value?.status === 'finished' && current.value?.is_credited);
const isCreditPending = computed(() => current.value?.status === 'finished' && !current.value?.is_credited);
const isFailed     = computed(() => ['failed', 'expired'].includes(current.value?.status));
const isExpired  = computed(() => current.value?.status === 'expired');
const hasAddress = computed(() => !!current.value?.pay_address);
const hasMemo    = computed(() => !!current.value?.memo);

// Coin icon helpers
const coinSymbol = computed(() => (current.value?.pay_currency ?? '').split('_')[0].toLowerCase());
const coinIconUrl = computed(() =>
    coinSymbol.value
        ? `https://cdn.jsdelivr.net/gh/atomiclabs/cryptocurrency-icons@1.0.0/svg/color/${coinSymbol.value}.svg`
        : ''
);
const COIN_COLORS = {
    btc: '#F7931A', eth: '#627EEA', usdt: '#26A17B', usdc: '#2775CA',
    bnb: '#F3BA2F', sol: '#9945FF', xrp: '#00AAE4',
    doge: '#C2A633', ltc: '#BFBBBB', trx: '#FF0013', ton: '#0098EA',
    dai: '#F5AC37',
};
const coinColor = computed(() => COIN_COLORS[coinSymbol.value] ?? '#6366f1');

// Memo label depends on coin (XRP uses destination tag, TON uses comment)
const memoLabel = computed(() => {
    const sym = coinSymbol.value;
    if (sym === 'xrp') return 'Destination Tag';
    if (sym === 'ton') return 'Comment / Memo';
    return 'Memo';
});

// ── Countdown ─────────────────────────────────────────────────────────────────
const secondsLeft = ref(0);

function startCountdown() {
    if (!current.value.expires_at) return;
    const expiry = new Date(current.value.expires_at).getTime();
    function tick() { secondsLeft.value = Math.max(0, Math.floor((expiry - Date.now()) / 1000)); }
    tick();
    cntTimer = setInterval(tick, 1000);
}
function stopCountdown() {
    if (cntTimer) { clearInterval(cntTimer); cntTimer = null; }
}
const countdown = computed(() => {
    const s = secondsLeft.value;
    if (s <= 0) return '00:00';
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
});
const countdownUrgent = computed(() => secondsLeft.value > 0 && secondsLeft.value <= 120);

// ── QR code ───────────────────────────────────────────────────────────────────
async function generateQr() {
    const addr = current.value.pay_address;
    if (!addr) return;
    if (current.value.qr_code_url) { qrDataUrl.value = current.value.qr_code_url; return; }
    try {
        qrDataUrl.value = await QRCode.toDataURL(addr, {
            width: 220, margin: 1,
            color: { dark: '#0f172a', light: '#ffffff' },
        });
    } catch { qrDataUrl.value = ''; }
}

// ── Copy helpers ──────────────────────────────────────────────────────────────
async function copyAddress() {
    try {
        await navigator.clipboard.writeText(current.value.pay_address ?? '');
        copiedAddr.value = true;
        setTimeout(() => { copiedAddr.value = false; }, 2000);
    } catch {}
}
async function copyMemo() {
    try {
        await navigator.clipboard.writeText(String(current.value.memo ?? ''));
        copiedMemo.value = true;
        setTimeout(() => { copiedMemo.value = false; }, 2000);
    } catch {}
}

// ── Polling ───────────────────────────────────────────────────────────────────
async function pollStatus() {
    if (!current.value?.reference) return;
    if (polling.value) return;
    try {
        polling.value = true;
        const res = await fetchTimeout(`/api/deposits/${current.value.reference}/status`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }, 12000);
        if (!res.ok) { pollError.value = true; return; }
        const data = await res.json();
        const hadAddress = hasAddress.value;
        current.value  = { ...current.value, ...data };
        pollError.value = false;
        if (!hadAddress && hasAddress.value) generateQr();
        // Stop only when the user's balance is confirmed credited, OR when the
        // invoice has definitively failed/expired (not simply "finished" — finished
        // without is_credited means crediting is still in progress, keep polling).
        const truelyDone = data.is_credited || (data.is_terminal && !data.is_finished);
        if (truelyDone) {
            stopPolling(); stopCountdown();
            if (data.is_credited) window.dispatchEvent(new Event('balance-refresh'));
        }
    } catch {
        pollError.value = true;
    } finally {
        polling.value = false;
    }
}
function startPolling() {
    if (pollTimer) return;
    pollStatus();
    pollTimer = setInterval(pollStatus, 5000);
}
function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

onMounted(() => {
    generateQr();
    if (isPending.value || isCreditPending.value) { startPolling(); startCountdown(); }
});
onUnmounted(() => { stopPolling(); stopCountdown(); });

// ── Format helpers ────────────────────────────────────────────────────────────
function fmtAmount(v) { return Number(v || 0).toFixed(8).replace(/\.?0+$/, ''); }
function fmtUsd(v)    { return Number(v || 0).toFixed(2); }
</script>

<template>
    <Head :title="`Pay ${current.pay_currency ?? 'Crypto'} — Invoice`" />
    <AuthenticatedLayout>
        <div class="max-w-md mx-auto pt-4 pb-16 px-4 sm:px-0">

            <!-- Back link -->
            <Link :href="route('deposit.index')"
                class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-sky-500 transition-colors mb-6">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to Deposits
            </Link>

            <!-- ── SUCCESS ─────────────────────────────────────────────────── -->
            <div v-if="isFinished" class="text-center py-8">
                <div class="mb-5 flex justify-center">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-emerald-50 dark:bg-emerald-500/[0.1] border-2 border-emerald-200 dark:border-emerald-500/30">
                        <CheckCircle2 class="w-10 h-10 text-emerald-500" />
                    </div>
                </div>
                <h1 class="text-[24px] font-black text-slate-900 dark:text-white mb-2">Payment Confirmed!</h1>
                <p class="text-[14px] text-slate-500 dark:text-slate-400 mb-1">
                    <strong class="text-emerald-600 dark:text-emerald-400">${{ fmtUsd(current.price_amount) }}</strong>
                    has been credited to your wallet.
                </p>
                <p v-if="current.pay_currency" class="text-[13px] text-slate-400 mb-6">
                    Paid via <span class="uppercase font-bold">{{ current.pay_currency }}</span>
                    <template v-if="current.network"> · {{ current.network }}</template>
                </p>
                <div v-if="current.blockchain_hash"
                    class="mx-auto max-w-sm px-3 py-2 rounded-xl bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07] mb-8">
                    <p class="text-[10px] text-slate-400 mb-1 uppercase tracking-wider">Transaction Hash</p>
                    <p class="text-[11px] font-mono text-slate-600 dark:text-slate-400 break-all">{{ current.blockchain_hash }}</p>
                </div>
                <Link :href="route('dashboard')"
                    class="inline-flex items-center gap-2 h-11 px-6 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95"
                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.4)">
                    Go to Dashboard
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

            <!-- ── FAILED / EXPIRED ────────────────────────────────────────── -->
            <div v-else-if="isFailed" class="text-center py-8">
                <div class="mb-5 flex justify-center">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center
                        bg-rose-50 dark:bg-rose-500/[0.1] border-2 border-rose-200 dark:border-rose-500/30">
                        <AlertCircle class="w-10 h-10 text-rose-500" />
                    </div>
                </div>
                <h1 class="text-[24px] font-black text-slate-900 dark:text-white mb-2">
                    Invoice {{ isExpired ? 'Expired' : 'Failed' }}
                </h1>
                <p class="text-[14px] text-slate-400 mb-8">
                    <template v-if="isExpired">The payment window has closed. Please create a new deposit.</template>
                    <template v-else>This payment could not be processed. No funds were charged.</template>
                </p>
                <Link :href="route('deposit.index')"
                    class="inline-flex items-center gap-2 h-11 px-6 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95"
                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.4)">
                    Try Again
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

            <!-- ── PENDING ─────────────────────────────────────────────────── -->
            <template v-else>

                <!-- Status + timer row -->
                <div class="flex items-center justify-between mb-5">
                    <div :class="['inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-[11px] font-bold border',
                        isCreditPending
                            ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                            : 'bg-sky-50 dark:bg-sky-500/10 border-sky-200 dark:border-sky-500/20 text-sky-600 dark:text-sky-400']">
                        <span :class="['w-1.5 h-1.5 rounded-full animate-pulse',
                            isCreditPending ? 'bg-emerald-500' : 'bg-sky-500']" />
                        {{ isCreditPending ? 'Crediting your account…' : (current.status_label ?? 'Waiting for payment') }}
                        <Loader2 class="w-3 h-3 animate-spin ml-0.5" />
                    </div>

                    <div v-if="current.expires_at && secondsLeft > 0"
                        :class="['flex items-center gap-1.5 text-[12px] font-mono font-bold',
                            countdownUrgent ? 'text-rose-500' : 'text-slate-500 dark:text-slate-400']">
                        <Clock class="w-3.5 h-3.5" />
                        {{ countdown }}
                    </div>
                    <div v-else-if="secondsLeft === 0 && current.expires_at"
                        class="text-[11px] text-rose-400 font-semibold">
                        Window closed
                    </div>
                </div>

                <!-- Coin + amount header -->
                <div class="bg-white dark:bg-[#0c1829] rounded-2xl border border-slate-200/80 dark:border-white/[0.05] p-5 mb-3 shadow-sm">
                    <div class="flex items-center gap-4">
                        <!-- Coin avatar -->
                        <div class="relative shrink-0">
                            <img v-if="coinIconUrl && !imgError"
                                :src="coinIconUrl"
                                :alt="current.pay_currency"
                                class="w-14 h-14 rounded-full shadow-md object-contain bg-white p-1"
                                @error="imgError = true" />
                            <div v-else
                                class="w-14 h-14 rounded-full flex items-center justify-center shadow-md text-white text-[15px] font-black"
                                :style="`background: ${coinColor}`">
                                {{ (current.pay_currency ?? '??').split('_')[0].slice(0, 4) }}
                            </div>
                        </div>
                        <!-- Amount -->
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400 mb-0.5">Send Exactly</p>
                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                <span class="text-[26px] font-black text-slate-900 dark:text-white font-mono leading-none">
                                    {{ fmtAmount(current.pay_amount) }}
                                </span>
                                <span class="text-[15px] font-black text-sky-500 uppercase">{{ current.pay_currency }}</span>
                            </div>
                            <p class="text-[12px] text-slate-400 mt-0.5">≈ ${{ fmtUsd(current.price_amount) }} USD</p>
                        </div>
                    </div>
                    <div v-if="current.network" class="mt-3 pt-3 border-t border-slate-100 dark:border-white/[0.05]">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">Network</span>
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400">
                                {{ current.network }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QR Code card -->
                <div class="bg-white dark:bg-[#0c1829] rounded-2xl border border-slate-200/80 dark:border-white/[0.05] overflow-hidden mb-3 shadow-sm">
                    <!-- QR -->
                    <div class="flex justify-center py-6 bg-white">
                        <div v-if="qrDataUrl" class="p-3 rounded-2xl border border-slate-100 shadow-sm">
                            <img :src="qrDataUrl" alt="QR Code" class="w-[200px] h-[200px] block" />
                        </div>
                        <div v-else-if="hasAddress" class="w-[200px] h-[200px] rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center gap-2">
                            <Loader2 class="w-6 h-6 text-slate-300 animate-spin" />
                            <p class="text-[11px] text-slate-400">Generating QR…</p>
                        </div>
                        <div v-else class="w-[200px] h-[200px] rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center justify-center gap-2">
                            <Loader2 class="w-6 h-6 text-slate-300 animate-spin" />
                            <p class="text-[11px] text-slate-400">Loading address…</p>
                        </div>
                    </div>

                    <!-- Scan hint -->
                    <p class="text-center text-[10.5px] text-slate-400 -mt-2 pb-3">
                        Scan QR or copy address below
                    </p>

                    <div class="px-5 pb-5 space-y-3">
                        <!-- Wallet address -->
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400 mb-1.5">Payment Address</p>
                            <div class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07]">
                                <p class="flex-1 text-[11.5px] font-mono text-slate-700 dark:text-slate-300 break-all leading-relaxed">
                                    <span v-if="hasAddress">{{ current.pay_address }}</span>
                                    <span v-else class="text-slate-400 italic">Loading…</span>
                                </p>
                                <button v-if="hasAddress" @click="copyAddress"
                                    :class="['shrink-0 p-2 rounded-lg transition-all',
                                        copiedAddr
                                            ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-500'
                                            : 'bg-slate-100 dark:bg-white/[0.08] text-slate-500 hover:text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-500/10']">
                                    <CheckCircle2 v-if="copiedAddr" class="w-4 h-4" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="copiedAddr" class="text-[10.5px] text-emerald-500 mt-1 text-center font-semibold">Address copied!</p>
                        </div>

                        <!-- Memo / Destination Tag (XRP, TON, etc.) -->
                        <div v-if="hasMemo"
                            class="rounded-xl border border-amber-200 dark:border-amber-500/30 overflow-hidden">
                            <div class="px-3 py-2 bg-amber-50 dark:bg-amber-500/10 flex items-center gap-1.5">
                                <TriangleAlert class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <p class="text-[10px] font-black uppercase tracking-[0.12em] text-amber-600 dark:text-amber-400">
                                    {{ memoLabel }} — Required
                                </p>
                            </div>
                            <div class="flex items-center gap-2 p-3 bg-white dark:bg-white/[0.02]">
                                <p class="flex-1 text-[13px] font-mono font-bold text-slate-800 dark:text-slate-200 break-all">
                                    {{ current.memo }}
                                </p>
                                <button @click="copyMemo"
                                    :class="['shrink-0 p-2 rounded-lg transition-all',
                                        copiedMemo
                                            ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-500'
                                            : 'bg-slate-100 dark:bg-white/[0.08] text-slate-500 hover:text-sky-500 hover:bg-sky-50 dark:hover:bg-sky-500/10']">
                                    <CheckCircle2 v-if="copiedMemo" class="w-4 h-4" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="copiedMemo" class="text-[10.5px] text-emerald-500 pb-2 text-center font-semibold">Copied!</p>
                        </div>
                    </div>
                </div>

                <!-- Important notice -->
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/[0.06] border border-amber-100 dark:border-amber-500/[0.15] mb-3">
                    <p class="text-[11.5px] font-bold text-amber-700 dark:text-amber-400 mb-1.5">Important</p>
                    <ul class="space-y-1 text-[11px] text-amber-600 dark:text-amber-500/80">
                        <li>• Send <strong>exactly</strong> {{ fmtAmount(current.pay_amount) }} {{ current.pay_currency }} — no more, no less.</li>
                        <li v-if="current.network">• Use the <strong>{{ current.network }}</strong> network only. Sending on the wrong network will result in permanent loss.</li>
                        <li v-if="hasMemo">• You <strong>must</strong> include the {{ memoLabel }} (<strong>{{ current.memo }}</strong>) or your payment cannot be credited.</li>
                        <li>• Keep this page open — it detects your payment automatically.</li>
                        <li>• Your balance is credited after blockchain confirmation.</li>
                    </ul>
                </div>

                <!-- Poll status row -->
                <div class="flex items-center justify-center gap-1.5 mb-5">
                    <div :class="['flex items-center gap-1.5 text-[11px] font-semibold',
                        pollError ? 'text-amber-500' : 'text-slate-400 dark:text-slate-400']">
                        <Loader2 v-if="polling" class="w-3 h-3 animate-spin" />
                        <RefreshCw v-else class="w-3 h-3" />
                        <span v-if="pollError">Connection issue — retrying…</span>
                        <span v-else>Auto-checking every 5 seconds</span>
                    </div>
                </div>

                <!-- OxaPay fallback link — only shown if payment_url exists -->
                <div v-if="current.payment_url" class="text-center">
                    <a :href="current.payment_url" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 text-[11px] text-slate-400 hover:text-sky-500 transition-colors">
                        <ExternalLink class="w-3 h-3" />
                        Trouble paying? Open on OxaPay
                    </a>
                </div>

            </template>

        </div>
    </AuthenticatedLayout>
</template>

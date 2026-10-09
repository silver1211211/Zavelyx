<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { coinAsset, networkAsset } from '@/utils/cryptoAssets';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ArrowRight, CheckCircle2, Clock, History, Loader2, Plus, Wallet } from 'lucide-vue-next';

const props = defineProps({
    deposits: { type: Object, default: () => ({ data: [], links: [] }) },
    filter: { type: String, default: 'all' },
});

const filters = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
    { value: 'failed', label: 'Failed' },
    { value: 'expired', label: 'Expired' },
];

const styles = {
    waiting: ['Waiting for payment', 'bg-amber-50 text-amber-600 border-amber-200', Clock],
    confirming: ['Confirming', 'bg-sky-50 text-sky-600 border-sky-200', Loader2],
    confirmed: ['Confirmed', 'bg-sky-50 text-sky-600 border-sky-200', Loader2],
    sending: ['Processing', 'bg-sky-50 text-sky-600 border-sky-200', Loader2],
    partially_paid: ['Partially paid', 'bg-amber-50 text-amber-600 border-amber-200', AlertCircle],
    finished: ['Completed', 'bg-emerald-50 text-emerald-600 border-emerald-200', CheckCircle2],
    failed: ['Failed', 'bg-rose-50 text-rose-600 border-rose-200', AlertCircle],
    expired: ['Expired', 'bg-slate-100 text-slate-500 border-slate-200', Clock],
    refunded: ['Refunded', 'bg-violet-50 text-violet-600 border-violet-200', ArrowLeft],
};
const status = value => styles[value] ?? styles.waiting;
const iconUrl = coinAsset;
const networkIconUrl = networkAsset;
const date = value => new Date(value).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
function setFilter(value) {
    router.get(route('deposit.history'), value === 'all' ? {} : { status: value }, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <Head title="Deposit History" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto pb-16">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <Link :href="route('deposit.index')" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-sky-500 mb-3">
                        <ArrowLeft class="w-3.5 h-3.5" /> Back to Deposit
                    </Link>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center"><History class="w-4 h-4" /></span>
                        Recent Deposits
                    </h1>
                    <p class="text-[13px] text-slate-400 mt-1">Track payments and reopen active deposit instructions.</p>
                </div>
                <Link :href="route('deposit.index')" class="inline-flex items-center justify-center gap-2 h-11 px-4 rounded-xl text-white text-[12px] font-bold bg-gradient-to-r from-sky-500 to-indigo-500 shadow-lg shadow-sky-500/20">
                    <Plus class="w-4 h-4" /> New Deposit
                </Link>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2 mb-4" style="scrollbar-width:none">
                <button v-for="item in filters" :key="item.value" @click="setFilter(item.value)"
                    :class="['shrink-0 px-4 py-2 rounded-xl text-[12px] font-bold transition-all', filter === item.value ? 'bg-sky-500 text-white shadow-md shadow-sky-500/20' : 'bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07] text-slate-500 dark:text-slate-400']">
                    {{ item.label }}
                </button>
            </div>

            <div v-if="!deposits.data?.length" class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[var(--surface-card)] py-16 text-center">
                <Wallet class="w-10 h-10 text-slate-300 mx-auto mb-3" />
                <p class="font-bold text-slate-700 dark:text-slate-300">No deposits found</p>
                <p class="text-[12px] text-slate-400 mt-1">Deposits matching this status will appear here.</p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="deposit in deposits.data" :key="deposit.id" class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[var(--surface-card)] p-4 sm:p-5 shadow-sm">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="relative w-11 h-11 rounded-full bg-slate-50 dark:bg-white/[0.05] p-1.5 shrink-0 border border-slate-100 dark:border-white/[0.05] shadow-sm">
                            <img v-if="deposit.pay_currency" :src="iconUrl(deposit.pay_currency)" :alt="deposit.pay_currency" class="w-full h-full object-contain" />
                            <Wallet v-else class="w-full h-full text-sky-500" />
                            <img v-if="networkIconUrl(deposit.network)"
                                :src="networkIconUrl(deposit.network)" :alt="`${deposit.network} network`"
                                class="absolute -right-1 -bottom-1 w-5 h-5 rounded-full bg-white dark:bg-[#101d30] p-0.5 border-2 border-white dark:border-[#101d30] shadow-md" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-[16px] font-black text-slate-900 dark:text-white">${{ Number(deposit.price_amount).toFixed(2) }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ deposit.pay_currency || 'Crypto' }}<span v-if="deposit.network"> · {{ deposit.network }}</span></p>
                                </div>
                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10.5px] font-bold', status(deposit.status)[1]]">
                                    <component :is="status(deposit.status)[2]" :class="['w-3 h-3', ['confirming','confirmed','sending'].includes(deposit.status) ? 'animate-spin' : '']" />
                                    {{ status(deposit.status)[0] }}
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/[0.05] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="text-[11px] text-slate-400">
                                    <p>{{ date(deposit.created_at) }}</p>
                                    <p class="font-mono mt-0.5">{{ deposit.reference.slice(0, 8) }}…</p>
                                </div>
                                <a v-if="deposit.can_resume" :href="deposit.resume_url" class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-xl bg-sky-500 text-white text-[11px] font-bold hover:bg-sky-600 transition-colors">
                                    View payment details <ArrowRight class="w-3.5 h-3.5" />
                                </a>
                                <Link v-else-if="deposit.gateway === 'oxapay_invoice'" :href="route('deposit.invoice.pay', deposit.reference)" class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-xl border border-slate-200 dark:border-white/[0.08] text-[11px] font-bold text-slate-500 dark:text-slate-300">
                                    View details <ArrowRight class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="deposits.links?.length > 3" class="flex justify-center gap-1 mt-6">
                <Link v-for="link in deposits.links" :key="link.label" :href="link.url || ''" preserve-scroll
                    :class="['px-3 py-2 rounded-lg text-[11px] font-bold', link.active ? 'bg-sky-500 text-white' : link.url ? 'bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07] text-slate-500' : 'text-slate-300 pointer-events-none']"
                    v-html="link.label" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

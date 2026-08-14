<script setup>
import { useCurrency } from '@/composables/useCurrency';
import { Link } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ExternalLink, X, Zap } from 'lucide-vue-next';

const props = defineProps({
    show:  { type: Boolean, required: true },
    order: { type: Object,  default: null },
});

const emit = defineEmits(['update:show', 'view-orders', 'place-another']);

const { symbol, convertAmount } = useCurrency();

function close()        { emit('update:show', false); }
function viewOrders()   { emit('update:show', false); emit('view-orders'); }
function placeAnother() { emit('update:show', false); emit('place-another'); }
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-250 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
        <div v-if="show && order"
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
            style="background:rgba(2,10,22,0.85);backdrop-filter:blur(24px) saturate(1.3)"
            @click.self="close"
        >
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-[0.94]"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-[0.94]"
                appear
            >
            <div v-if="show"
                class="relative w-full sm:max-w-md rounded-t-[28px] sm:rounded-3xl
                    bg-white dark:bg-[#0b1929]
                    border-t sm:border border-white/60 dark:border-white/[0.08]
                    shadow-[0_-24px_80px_rgba(0,0,0,0.4)] sm:shadow-[0_40px_120px_rgba(0,0,0,0.7)]"
                style="max-height:92vh;overflow-y:auto;-webkit-overflow-scrolling:touch"
            >
                <!-- Gradient top bar -->
                <div class="h-1 w-full flex-shrink-0"
                    :style="order.provider_error
                        ? 'background:linear-gradient(90deg,#f59e0b,#ef4444)'
                        : 'background:linear-gradient(90deg,#10b981,#0ea5e9,#6366f1)'" />

                <!-- Close -->
                <button @click="close"
                    class="absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center rounded-full
                        bg-slate-100 dark:bg-white/[0.09]
                        text-slate-500 dark:text-slate-400
                        hover:bg-slate-200 dark:hover:bg-white/[0.16]
                        hover:text-slate-800 dark:hover:text-white
                        transition-colors">
                    <X class="w-4 h-4" />
                </button>

                <!-- Mobile handle -->
                <div class="sm:hidden flex justify-center pt-3">
                    <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-white/20" />
                </div>

                <div class="px-5 pt-5 pb-6 sm:px-7 sm:pt-6 sm:pb-8">

                    <!-- Icon + heading -->
                    <div class="flex flex-col items-center mb-5">
                        <div class="w-[60px] h-[60px] rounded-2xl flex items-center justify-center mb-3 flex-shrink-0"
                            :style="order.provider_error
                                ? 'background:linear-gradient(135deg,#f59e0b,#ef4444);box-shadow:0 0 36px rgba(239,68,68,0.35)'
                                : 'background:linear-gradient(135deg,#10b981,#0ea5e9);box-shadow:0 0 36px rgba(14,165,233,0.35)'"
                        >
                            <component
                                :is="order.provider_error ? AlertCircle : CheckCircle2"
                                class="w-7 h-7 text-white" :stroke-width="2.5" />
                        </div>
                        <h2 class="text-[19px] sm:text-[21px] font-black text-slate-900 dark:text-white text-center leading-tight">
                            {{ order.provider_error ? 'Order Created – Review Needed' : 'Order Placed Successfully' }}
                        </h2>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 text-center mt-1 max-w-xs">
                            {{ order.provider_error
                                ? 'Your wallet was charged but the provider returned an issue.'
                                : 'Your order is queued and will start processing shortly.' }}
                        </p>
                    </div>

                    <!-- Details card -->
                    <div class="rounded-2xl border border-slate-200 dark:border-white/[0.07] overflow-hidden mb-4
                        bg-slate-50/50 dark:bg-white/[0.02]">
                        <div class="divide-y divide-slate-100 dark:divide-white/[0.05]">

                            <div v-if="order.order_id" class="flex items-center justify-between px-4 py-2.5 text-[12px]">
                                <span class="text-slate-500 dark:text-slate-400">Order ID</span>
                                <span class="font-black text-slate-800 dark:text-slate-100 font-mono tracking-tight">
                                    #{{ order.order_id }}
                                </span>
                            </div>

                            <div class="flex items-start justify-between px-4 py-2.5 text-[12px] gap-3">
                                <span class="text-slate-500 dark:text-slate-400 flex-shrink-0">Service</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-right leading-snug" style="word-break:break-word">
                                    {{ order.service_name }}
                                </span>
                            </div>

                            <div v-if="order.category_name" class="flex items-center justify-between px-4 py-2.5 text-[12px]">
                                <span class="text-slate-500 dark:text-slate-400">Category</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ order.category_name }}</span>
                            </div>

                            <div v-if="order.link" class="flex items-start justify-between px-4 py-2.5 text-[12px] gap-3">
                                <span class="text-slate-500 dark:text-slate-400 flex-shrink-0">Link</span>
                                <span class="font-medium text-sky-600 dark:text-sky-400 text-right text-[11px] leading-relaxed" style="word-break:break-all;max-width:220px">
                                    {{ order.link }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between px-4 py-2.5 text-[12px]">
                                <span class="text-slate-500 dark:text-slate-400">Quantity</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono tabular-nums">
                                    {{ Number(order.quantity)?.toLocaleString() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between px-4 py-3 text-[12.5px]
                                bg-sky-50/80 dark:bg-sky-500/[0.07]">
                                <span class="font-bold text-slate-700 dark:text-slate-200">Charged</span>
                                <span class="font-black text-sky-600 dark:text-sky-400 tabular-nums font-mono text-[15px]">
                                    {{ symbol }}{{ convertAmount(order.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between px-4 py-2.5 text-[12px]">
                                <span class="text-slate-500 dark:text-slate-400">Status</span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold"
                                    :class="{
                                        'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400': order.status === 'pending',
                                        'bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-400': order.status === 'processing',
                                        'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400': order.status === 'completed',
                                        'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400': ['failed','cancelled'].includes(order.status ?? ''),
                                        'bg-slate-100 dark:bg-white/[0.08] text-slate-600 dark:text-slate-400': !['pending','processing','completed','failed','cancelled'].includes(order.status ?? ''),
                                    }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-amber-500 animate-pulse': order.status === 'pending',
                                            'bg-sky-500 animate-pulse': order.status === 'processing',
                                            'bg-emerald-500': order.status === 'completed',
                                            'bg-rose-500': ['failed','cancelled'].includes(order.status ?? ''),
                                            'bg-slate-400': !['pending','processing','completed','failed','cancelled'].includes(order.status ?? ''),
                                        }" />
                                    {{ (order.status ?? 'pending').charAt(0).toUpperCase() + (order.status ?? 'pending').slice(1) }}
                                </span>
                            </div>

                            <div v-if="order.remaining_balance != null" class="flex items-center justify-between px-4 py-2.5 text-[12px]">
                                <span class="text-slate-500 dark:text-slate-400">New Balance</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">
                                    {{ symbol }}{{ convertAmount(order.remaining_balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Provider error block -->
                    <div v-if="order.provider_error"
                        class="flex items-start gap-3 px-4 py-3.5 rounded-2xl mb-4
                            bg-amber-50 dark:bg-amber-500/[0.08]
                            border border-amber-200 dark:border-amber-500/20">
                        <AlertCircle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <p class="text-[11.5px] font-bold text-amber-700 dark:text-amber-400 mb-0.5">Provider Note</p>
                            <p class="text-[11px] text-amber-600 dark:text-amber-500 leading-relaxed">{{ order.provider_error }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                        <Link :href="route('orders.index')"
                            @click="viewOrders"
                            class="w-full flex items-center justify-center gap-2 font-bold text-[14px] rounded-2xl
                                text-white transition-all active:scale-[0.98] hover:brightness-110"
                            style="height:52px;background:linear-gradient(135deg,#0ea5e9,#6366f1);box-shadow:0 8px 28px rgba(14,165,233,0.28)">
                            <ExternalLink class="w-4 h-4" /> View My Orders
                        </Link>
                        <button @click="placeAnother"
                            class="w-full font-semibold text-[13px] rounded-2xl transition-all active:scale-[0.98]
                                text-slate-600 dark:text-slate-300
                                bg-slate-100 dark:bg-white/[0.06]
                                hover:bg-slate-200 dark:hover:bg-white/[0.1]
                                border border-slate-200 dark:border-white/[0.08]"
                            style="height:46px">
                            <Zap class="w-3.5 h-3.5 inline mr-1.5 -mt-0.5" :stroke-width="2.5" />
                            Place Another Order
                        </button>
                    </div>

                </div>
            </div>
            </Transition>
        </div>
        </Transition>
    </Teleport>
</template>

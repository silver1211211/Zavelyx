<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, XCircle } from 'lucide-vue-next';
</script>

<template>
    <Head title="Payment Cancelled" />
    <AuthenticatedLayout>

        <div class="max-w-lg mx-auto pt-8 pb-16 text-center">

            <div class="mb-6 flex justify-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center
                    bg-rose-50 dark:bg-rose-500/[0.1] border-2 border-rose-200 dark:border-rose-500/30">
                    <XCircle class="w-10 h-10 text-rose-400" />
                </div>
            </div>

            <h1 class="text-[26px] font-black text-slate-900 dark:text-white mb-2">Payment Cancelled</h1>
            <p class="text-[14px] text-slate-500 dark:text-slate-400 leading-relaxed">
                You cancelled the payment. No funds have been charged.
                You can try again whenever you're ready.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-8">
                <Link :href="route('dashboard')"
                    class="flex items-center gap-2 h-11 px-5 rounded-xl text-[13px] font-bold
                        bg-white dark:bg-white/[0.04] border border-slate-200 dark:border-white/[0.07]
                        text-slate-600 dark:text-slate-300
                        hover:border-slate-300 dark:hover:border-white/[0.12] transition-all">
                    <ArrowLeft class="w-4 h-4" />
                    Back to Dashboard
                </Link>
                <Link :href="route('deposit.index')"
                    class="flex items-center gap-2 h-11 px-5 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95"
                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.4)">
                    <CreditCard class="w-4 h-4" />
                    Try Again
                </Link>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

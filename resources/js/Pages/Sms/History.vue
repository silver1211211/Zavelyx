<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { History, Zap } from 'lucide-vue-next';
</script>

<template>
    <Head title="OTP History — Zavelyx" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                    style="background: linear-gradient(135deg, #8b5cf6, #a855f7)">
                    <History class="w-4 h-4 text-white" :stroke-width="2" />
                </div>
                OTP History
            </h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                View received verification codes and your past activation history.
            </p>
        </div>

        <!-- Coming soon placeholder -->
        <div class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-[#0c1829] p-16 text-center">
            <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center"
                style="background: linear-gradient(135deg, rgba(139,92,246,0.12), rgba(168,85,247,0.08)); border: 1px solid rgba(139,92,246,0.18)">
                <History class="w-8 h-8 text-violet-500" :stroke-width="1.5" />
            </div>
            <h2 class="text-lg font-black text-slate-800 dark:text-white mb-2">OTP History</h2>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed mb-5">
                Your OTP activation history will appear here once you start using virtual numbers.
            </p>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-bold text-violet-600 dark:text-violet-400"
                style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2)">
                <Zap class="w-3.5 h-3.5" :stroke-width="2.5" />
                Launching soon
            </div>
        </div>
    </AuthenticatedLayout>
</template>

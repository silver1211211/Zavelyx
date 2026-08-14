<script setup>
import { useToast } from '@/composables/useToast';
import { AlertTriangle, CheckCircle2, Info, RefreshCw, Wallet, X, Zap } from 'lucide-vue-next';
import { computed } from 'vue';

const { toasts, dismiss } = useToast();

const CONFIG = {
    success: {
        icon: CheckCircle2,
        bar:  'bg-emerald-500',
        glow: 'shadow-[0_0_20px_rgba(16,185,129,0.15)]',
        iconClass: 'text-emerald-400',
        ring: 'ring-emerald-500/20',
    },
    error: {
        icon: AlertTriangle,
        bar:  'bg-red-500',
        glow: 'shadow-[0_0_20px_rgba(239,68,68,0.15)]',
        iconClass: 'text-red-400',
        ring: 'ring-red-500/20',
    },
    warning: {
        icon: AlertTriangle,
        bar:  'bg-amber-500',
        glow: 'shadow-[0_0_20px_rgba(245,158,11,0.15)]',
        iconClass: 'text-amber-400',
        ring: 'ring-amber-500/20',
    },
    info: {
        icon: Info,
        bar:  'bg-sky-500',
        glow: 'shadow-[0_0_20px_rgba(14,165,233,0.15)]',
        iconClass: 'text-sky-400',
        ring: 'ring-sky-500/20',
    },
    balance: {
        icon: Wallet,
        bar:  'bg-violet-500',
        glow: 'shadow-[0_0_20px_rgba(139,92,246,0.15)]',
        iconClass: 'text-violet-400',
        ring: 'ring-violet-500/20',
    },
    sync: {
        icon: RefreshCw,
        bar:  'bg-cyan-500',
        glow: 'shadow-[0_0_20px_rgba(6,182,212,0.15)]',
        iconClass: 'text-cyan-400',
        ring: 'ring-cyan-500/20',
    },
    order: {
        icon: Zap,
        bar:  'bg-sky-500',
        glow: 'shadow-[0_0_20px_rgba(14,165,233,0.2)]',
        iconClass: 'text-sky-400',
        ring: 'ring-sky-500/20',
    },
};

function cfg(type) {
    return CONFIG[type] ?? CONFIG.info;
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[300] flex flex-col gap-2 pointer-events-none max-w-[360px] w-full">
            <TransitionGroup
                enter-active-class="transition-all duration-350 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                enter-from-class="opacity-0 translate-x-8 scale-95"
                enter-to-class="opacity-100 translate-x-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-x-0 scale-100 max-h-[120px] mb-0"
                leave-to-class="opacity-0 translate-x-8 scale-95 max-h-0 mb-[-8px]"
                move-class="transition-all duration-200"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'pointer-events-auto relative overflow-hidden rounded-2xl',
                        'bg-white/95 dark:bg-[var(--surface-card)]/95 backdrop-blur-xl',
                        'border border-slate-200 dark:border-white/[0.08]',
                        'shadow-[0_8px_32px_rgba(15,23,42,0.12)] dark:shadow-[0_8px_48px_rgba(0,0,0,0.7)]',
                        cfg(toast.type).glow,
                        'ring-1',
                        cfg(toast.type).ring,
                    ]"
                >
                    <!-- Left accent bar -->
                    <div :class="['absolute left-0 top-0 bottom-0 w-[3px] rounded-l-full', cfg(toast.type).bar]" />

                    <div class="flex items-start gap-3 pl-4 pr-3 py-3.5">
                        <!-- Icon -->
                        <div :class="[
                            'w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 mt-px',
                            'bg-slate-100 dark:bg-white/[0.05] border border-slate-200 dark:border-white/[0.06]',
                        ]">
                            <component :is="cfg(toast.type).icon"
                                :class="['w-4 h-4', cfg(toast.type).iconClass]"
                                :stroke-width="2" />
                        </div>

                        <!-- Text -->
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-[13px] font-bold text-slate-900 dark:text-white leading-snug">{{ toast.title }}</p>
                            <p v-if="toast.message" class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">{{ toast.message }}</p>
                        </div>

                        <!-- Dismiss -->
                        <button @click="dismiss(toast.id)"
                            class="w-6 h-6 flex items-center justify-center rounded-lg
                                text-slate-400 hover:text-slate-600 dark:text-slate-600 dark:hover:text-slate-300
                                hover:bg-slate-100 dark:hover:bg-white/[0.08]
                                active:scale-90 transition-all flex-shrink-0 mt-px">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Auto-dismiss progress bar -->
                    <div v-if="toast.duration > 0"
                        :class="['absolute bottom-0 left-0 h-[2px] rounded-full opacity-40', cfg(toast.type).bar]"
                        :style="{
                            width: '100%',
                            animation: `shrink ${toast.duration}ms linear forwards`,
                        }" />
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes shrink {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>

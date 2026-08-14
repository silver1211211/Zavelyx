<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center overflow-hidden bg-white pt-6 sm:justify-center sm:pt-0 dark:bg-[#070d1a]"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 right-0 h-[400px] w-[400px] rounded-full bg-sky-400/8 dark:bg-sky-500/10 blur-[100px]"></div>
            <div class="absolute bottom-0 -left-40 h-[320px] w-[320px] rounded-full bg-blue-400/6 dark:bg-blue-500/8 blur-[80px]"></div>
        </div>

        <div class="relative">
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-sky-500 dark:text-sky-400" />
            </Link>
        </div>

        <div
            class="relative mt-6 w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white px-6 py-4 shadow-[0_18px_45px_rgba(14,165,233,0.10)] sm:max-w-md dark:border-white/[0.09] dark:bg-[var(--surface-card)] dark:shadow-[0_20px_60px_rgba(0,0,0,0.5)]"
        >
            <slot />
        </div>
    </div>
</template>

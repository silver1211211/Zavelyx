<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    CreditCard,
    Globe,
    Key,
    Lock,
    MessageCircle,
    Monitor,
    Palette,
    Settings,
    Shield,
} from 'lucide-vue-next';

const sections = [
    {
        icon: Monitor,
        iconBg: 'bg-sky-500/10 dark:bg-sky-500/15',
        iconColor: 'text-sky-600 dark:text-sky-400',
        label: 'General Settings',
        description: 'Site name, tagline, SEO meta tags, homepage content, announcements, and maintenance mode.',
        routeName: 'admin.settings.general.index',
        badge: null,
    },
    {
        icon: Globe,
        iconBg: 'bg-indigo-500/10 dark:bg-indigo-500/15',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
        label: 'Currency Settings',
        description: 'Manage currencies, exchange rates, and display options.',
        routeName: 'admin.settings.currencies.index',
        badge: null,
    },
    {
        icon: Key,
        iconBg: 'bg-violet-500/10 dark:bg-violet-500/15',
        iconColor: 'text-violet-600 dark:text-violet-400',
        label: 'API & Providers',
        description: 'Connect SMM panel APIs, configure providers, and import services.',
        routeName: 'admin.settings.api.index',
        badge: null,
    },
    {
        icon: CreditCard,
        iconBg: 'bg-emerald-500/10 dark:bg-emerald-500/15',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        label: 'Payment Gateways',
        description: 'Add and configure payment processors, API keys, IPN secrets, fees, and deposit limits.',
        routeName: 'admin.gateways.index',
        badge: null,
    },
    {
        icon: MessageCircle,
        iconBg: 'bg-teal-500/10 dark:bg-teal-500/15',
        iconColor: 'text-teal-600 dark:text-teal-400',
        label: 'Contact Settings',
        description: 'Set the global support link used across the platform — email, Telegram, WhatsApp, or custom URL.',
        routeName: 'admin.settings.contact.index',
        badge: null,
    },
    {
        icon: Palette,
        iconBg: 'bg-pink-500/10 dark:bg-pink-500/15',
        iconColor: 'text-pink-600 dark:text-pink-400',
        label: 'Theme & Appearance',
        description: 'Choose color presets, customize primary/accent colors, glow intensity, and border radius.',
        routeName: 'admin.settings.theme.index',
        badge: null,
    },
    {
        icon: Shield,
        iconBg: 'bg-amber-500/10 dark:bg-amber-500/15',
        iconColor: 'text-amber-600 dark:text-amber-400',
        label: 'Security',
        description: 'Admin credentials, password policy, rate limits, login attempts, and platform protection.',
        routeName: 'admin.settings.security.index',
        badge: null,
    },
    {
        icon: Lock,
        iconBg: 'bg-rose-500/10 dark:bg-rose-500/15',
        iconColor: 'text-rose-600 dark:text-rose-400',
        label: 'Access Control',
        description: 'Manage user roles, assign permissions, and review login history.',
        routeName: 'admin.settings.access-control.index',
        badge: null,
    },
];
</script>

<template>
    <Head title="Settings — Admin" />
    <AdminLayout>
        <div class="mb-8">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">System</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Settings</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">Configure currencies, integrations, appearance, and platform behavior.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template v-for="section in sections" :key="section.label">
                <!-- Active card -->
                <Link
                    v-if="section.routeName"
                    :href="route(section.routeName)"
                    class="group relative flex flex-col gap-4 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 hover:border-sky-400 dark:hover:border-sky-500/40 hover:shadow-lg hover:shadow-sky-500/5 dark:hover:shadow-sky-500/10 transition-all duration-200"
                >
                    <div class="flex items-start justify-between">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', section.iconBg]">
                            <component :is="section.icon" :class="['w-5 h-5', section.iconColor]" />
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-300 dark:text-slate-700 group-hover:text-sky-500 dark:group-hover:text-sky-400 group-hover:translate-x-0.5 transition-all" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">{{ section.label }}</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ section.description }}</p>
                    </div>
                </Link>

                <!-- Coming soon card -->
                <div
                    v-else
                    class="relative flex flex-col gap-4 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 opacity-60 cursor-default"
                >
                    <div class="flex items-start justify-between">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', section.iconBg]">
                            <component :is="section.icon" :class="['w-5 h-5', section.iconColor]" />
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-1 bg-slate-100 dark:bg-white/5 text-slate-400 dark:text-slate-600 rounded-lg">
                            Soon
                        </span>
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">{{ section.label }}</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ section.description }}</p>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>

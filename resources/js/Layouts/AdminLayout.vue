<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { getPreferredTheme, setThemeInstant } from '@/utils/theme';
import {
    ArrowUpDown,
    Bell,
    ChevronDown,
    ChevronRight,
    Code2,
    ContactRound,
    CreditCard,
    DollarSign,
    FolderOpen,
    Globe,
    LayoutDashboard,
    Lock,
    LogOut,
    Menu,
    MessageSquare,
    Moon,
    Palette,
    Phone,
    Search,
    Settings2,
    ShieldCheck,
    ShoppingCart,
    Sun,
    Terminal,
    Users,
    Wallet,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const sidebarOpen  = ref(false);
const isDark       = ref(false);
const profileOpen  = ref(false);
const adminUsername = ref('Admin');
const openTickets  = computed(() => usePage().props.admin_open_tickets ?? 0);
const siteSettings = computed(() => usePage().props.site_settings ?? {});
const adminLogoUrl = computed(() => siteSettings.value.logo_admin || siteSettings.value.logo_url || '');

const navSections = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', routeName: 'admin.dashboard', icon: LayoutDashboard },
        ],
    },
    {
        label: 'Management',
        items: [
            { label: 'Users',         routeName: 'admin.users.index',         icon: Users },
            { label: 'Orders',        routeName: 'admin.orders.index',        icon: ShoppingCart },
            { label: 'Services',      routeName: 'admin.services.index',      icon: Settings2 },
            { label: 'Notifications', routeName: 'admin.notifications.index', icon: Bell },
        ],
    },
    {
        label: 'SMS Numbers',
        items: [
            { label: 'Number Orders',    routeName: 'admin.number-orders.index',    icon: Phone },
            { label: 'Number Providers', routeName: 'admin.number-providers.index', icon: Settings2 },
        ],
    },
    {
        label: 'Finance & Support',
        items: [
            { label: 'Payments',  routeName: 'admin.payments.index',  icon: CreditCard },
            { label: 'Gateways',  routeName: 'admin.gateways.index',  icon: Wallet },
            { label: 'Tickets',   routeName: 'admin.tickets.index',   icon: MessageSquare },
        ],
    },
];

// Settings sub-pages — shown as an expandable group
const settingsItems = [
    { label: 'General',        routeName: 'admin.settings.general.index',        icon: Globe },
    { label: 'Security',       routeName: 'admin.settings.security.index',       icon: ShieldCheck },
    { label: 'Theme',          routeName: 'admin.settings.theme.index',          icon: Palette },
    { label: 'API Providers',  routeName: 'admin.api-settings.index',            icon: Code2 },
    { label: 'Currencies',     routeName: 'admin.website-settings.index',        icon: DollarSign },
    { label: 'Contact',        routeName: 'admin.settings.contact.index',        icon: ContactRound },
    { label: 'Access Control', routeName: 'admin.settings.access-control.index', icon: Lock },
];

const settingsRouteNames = settingsItems.map(i => i.routeName);

const isSettingsActive = computed(() => {
    try {
        return settingsRouteNames.some(r => route().current(r) || route().current(r + '.*'));
    } catch { return false; }
});

const settingsOpen = ref(false);

function isActive(routeName) {
    try {
        return route().current(routeName) || route().current(routeName + '.*');
    } catch {
        return false;
    }
}

function toggleTheme() {
    isDark.value = !isDark.value;
    setThemeInstant(isDark.value ? 'dark' : 'light');
}

function logout() {
    router.post(route('admin.logout'));
}

onMounted(() => {
    isDark.value = getPreferredTheme() === 'dark';
    setThemeInstant(isDark.value ? 'dark' : 'light');
    // Auto-expand settings if currently on a settings page
    settingsOpen.value = isSettingsActive.value;
});
</script>

<template>
    <div class="min-h-screen bg-[#f0f4f8] dark:bg-[#060d18] transition-colors duration-300">

        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" />
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-[220px] flex flex-col',
                'bg-white dark:bg-[#09111f]',
                'border-r border-slate-200 dark:border-sky-500/10',
                'transition-transform duration-300 ease-in-out',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-200 dark:border-sky-500/10 flex-shrink-0">
                <!-- Custom logo image -->
                <img v-if="adminLogoUrl" :src="adminLogoUrl" alt="Logo"
                    class="h-8 max-w-[120px] object-contain flex-shrink-0" />
                <!-- Default icon+wordmark -->
                <template v-else>
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shadow-lg flex-shrink-0"
                        style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 40%, transparent)">
                        <Zap class="w-3.5 h-3.5 text-white" :stroke-width="2.5" />
                    </div>
                    <div>
                        <p class="text-[13px] font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                            {{ siteSettings.name || 'Zavelyx' }}
                        </p>
                        <p class="text-[9px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-600 leading-tight">Admin Panel</p>
                    </div>
                </template>
                <button class="ml-auto lg:hidden p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors" @click="sidebarOpen = false">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-3 px-2.5 space-y-0.5">
                <template v-for="section in navSections" :key="section.label">
                    <p class="px-2.5 pt-4 pb-1 text-[9.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">
                        {{ section.label }}
                    </p>
                    <template v-for="item in section.items" :key="item.label">
                        <!-- Soon items (not yet built) -->
                        <div
                            v-if="item.soon"
                            class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[12.5px] font-medium text-slate-400 dark:text-slate-600 cursor-default"
                        >
                            <component :is="item.icon" class="w-[15px] h-[15px] flex-shrink-0 text-slate-300 dark:text-slate-700" />
                            <span>{{ item.label }}</span>
                            <span class="ml-auto text-[9px] font-semibold px-1.5 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-400 dark:text-slate-600 rounded-md">Soon</span>
                        </div>
                        <!-- Active nav links -->
                        <Link
                            v-else
                            :href="route(item.routeName)"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[12.5px] font-medium transition-all duration-150',
                                isActive(item.routeName)
                                    ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.12)]'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-slate-200',
                            ]"
                            @click="sidebarOpen = false"
                        >
                            <component
                                :is="item.icon"
                                :class="['w-[15px] h-[15px] flex-shrink-0', isActive(item.routeName) ? 'text-sky-500' : 'text-slate-400 dark:text-slate-400']"
                                :stroke-width="isActive(item.routeName) ? 2.5 : 2"
                            />
                            <span>{{ item.label }}</span>
                            <span v-if="item.routeName === 'admin.tickets.index' && openTickets > 0"
                                class="ml-auto min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-rose-500 text-white text-[10px] font-bold leading-none">
                                {{ openTickets > 99 ? '99+' : openTickets }}
                            </span>
                        </Link>
                    </template>
                </template>

                <!-- ── Settings expandable section ──────────────────── -->
                <p class="px-2.5 pt-4 pb-1 text-[9.5px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">
                    Settings
                </p>

                <!-- Toggle button -->
                <button
                    @click="settingsOpen = !settingsOpen"
                    :class="[
                        'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[12.5px] font-medium transition-all duration-150',
                        isSettingsActive
                            ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.12)]'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-slate-200',
                    ]"
                >
                    <Settings2
                        :class="['w-[15px] h-[15px] flex-shrink-0', isSettingsActive ? 'text-sky-500' : 'text-slate-400 dark:text-slate-400']"
                        :stroke-width="isSettingsActive ? 2.5 : 2"
                    />
                    <span class="flex-1 text-left">Settings</span>
                    <ChevronRight
                        :class="['w-3.5 h-3.5 transition-transform duration-200 text-slate-400', settingsOpen ? 'rotate-90' : '']"
                    />
                </button>

                <!-- Sub-items -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                    enter-from-class="opacity-0 max-h-0"
                    enter-to-class="opacity-100 max-h-96"
                    leave-active-class="transition-all duration-150 ease-in overflow-hidden"
                    leave-from-class="opacity-100 max-h-96"
                    leave-to-class="opacity-0 max-h-0"
                >
                    <div v-if="settingsOpen" class="ml-3 pl-3 border-l border-slate-200 dark:border-white/8 mt-0.5 space-y-0.5">
                        <Link
                            v-for="item in settingsItems"
                            :key="item.routeName"
                            :href="route(item.routeName)"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-2 rounded-lg text-[12px] font-medium transition-all duration-150',
                                isActive(item.routeName)
                                    ? 'bg-sky-50 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400'
                                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-slate-300',
                            ]"
                            @click="sidebarOpen = false"
                        >
                            <component
                                :is="item.icon"
                                :class="['w-3.5 h-3.5 flex-shrink-0', isActive(item.routeName) ? 'text-sky-500' : 'text-slate-400 dark:text-slate-600']"
                                :stroke-width="isActive(item.routeName) ? 2.5 : 2"
                            />
                            {{ item.label }}
                        </Link>
                    </div>
                </Transition>
            </nav>

            <!-- Sidebar footer -->
            <div class="flex-shrink-0 border-t border-slate-200 dark:border-sky-500/10 p-3">
                <button
                    @click="logout"
                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[12.5px] font-medium text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all"
                >
                    <LogOut class="w-[15px] h-[15px]" />
                    Sign Out
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="lg:pl-[220px] flex flex-col min-h-screen">

            <!-- Topbar -->
            <header class="sticky top-0 z-30 h-16 flex items-center gap-3 px-4 sm:px-6 bg-white/90 dark:bg-[#09111f]/90 backdrop-blur-xl border-b border-slate-200 dark:border-sky-500/10">

                <!-- Mobile toggle -->
                <button
                    class="lg:hidden p-2 rounded-xl border border-slate-200 dark:border-white/8 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white transition-colors"
                    @click="sidebarOpen = true"
                >
                    <Menu class="w-5 h-5" />
                </button>

                <!-- Search -->
                <div class="hidden md:flex flex-1 max-w-xs">
                    <div class="relative w-full">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                        <input
                            type="search"
                            placeholder="Search users, orders..."
                            class="w-full h-9 pl-9 pr-4 text-[13px] bg-slate-100 dark:bg-white/5 border border-transparent dark:border-white/5 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:bg-white dark:focus:bg-white/8 transition-all"
                        />
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <!-- Admin badge -->
                    <span class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 bg-sky-500/10 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 text-[11px] font-semibold rounded-lg border border-sky-500/15">
                        <span class="w-1.5 h-1.5 bg-sky-500 rounded-full animate-pulse"></span>
                        Admin
                    </span>

                    <!-- Notifications -->
                    <button class="relative p-2 rounded-xl border border-slate-200 dark:border-white/8 bg-white dark:bg-[#0d1e35] text-slate-500 dark:text-slate-400 hover:border-sky-300 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-300 transition-all">
                        <Bell class="w-4.5 h-4.5" />
                    </button>

                    <!-- Theme toggle -->
                    <button
                        @click="toggleTheme"
                        class="p-2 rounded-xl border border-slate-200 dark:border-white/8 bg-white dark:bg-[#0d1e35] text-slate-500 dark:text-slate-400 hover:border-sky-300 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-300 transition-all"
                    >
                        <Sun v-if="isDark" class="w-4.5 h-4.5" />
                        <Moon v-else class="w-4.5 h-4.5" />
                    </button>

                    <!-- Admin profile -->
                    <div class="relative">
                        <button
                            @click="profileOpen = !profileOpen"
                            class="flex items-center gap-2 py-1 pl-1 pr-2.5 rounded-xl border border-slate-200 dark:border-white/8 bg-white dark:bg-[#0d1e35] hover:border-sky-300 dark:hover:border-sky-500/30 transition-all group"
                        >
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                                A
                            </div>
                            <span class="hidden sm:block text-[13px] font-medium text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">Admin</span>
                            <ChevronDown class="hidden sm:block w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': profileOpen }" />
                        </button>

                        <Transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-1"
                        >
                            <div
                                v-if="profileOpen"
                                class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/15 rounded-2xl shadow-xl dark:shadow-[0_8px_40px_rgba(0,0,0,0.5)] overflow-hidden z-50"
                            >
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-sky-500/10">
                                    <p class="text-[12px] font-semibold text-slate-900 dark:text-white">Administrator</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">admin@zavelyx.com</p>
                                </div>
                                <div class="py-1">
                                    <button
                                        @click="logout(); profileOpen = false"
                                        class="flex w-full items-center gap-3 px-4 py-2.5 text-[13px] text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors"
                                    >
                                        <LogOut class="w-3.5 h-3.5" />
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <!-- Click outside to close profile -->
        <div v-if="profileOpen" class="fixed inset-0 z-40" @click="profileOpen = false" />
    </div>
</template>

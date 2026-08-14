<script setup>
import ToastNotifications from '@/Components/ToastNotifications.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useToast } from '@/composables/useToast';
import { getStoredTheme, setThemeInstant } from '@/utils/theme';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowUpDown, Bell, BellDot, Check, CheckCheck,
    ChevronDown, ChevronLeft, ChevronRight,
    Code2, CreditCard, ExternalLink, Grid3x3,
    LayoutDashboard, LogOut, Menu, MessageSquare,
    Moon, Phone, Plus, Settings2, ShoppingBag,
    ShoppingCart, Sun, Terminal, Users, X, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const page = usePage();
const siteSettings   = computed(() => page.props.site_settings ?? {});
const dashLogoUrl    = computed(() => siteSettings.value.logo_dashboard || siteSettings.value.logo_url || '');
const { currencies, displayCurrency, current, symbol, setCurrency, formatAmount, formatMoney } = useCurrency();
const toast = useToast();

// ── Theme ─────────────────────────────────────────────────────────────────────
const isDark = ref(true);
function applyTheme(dark) {
    isDark.value = dark;
    setThemeInstant(dark ? 'dark' : 'light');
}
function toggleTheme() { applyTheme(!isDark.value); }

// ── Sidebar state ─────────────────────────────────────────────────────────────
const sidebarOpen      = ref(false);
const sidebarCollapsed = ref(false);
const profileOpen      = ref(false);
const currencyOpen     = ref(false);
const notifOpen        = ref(false);

// ── Nav ───────────────────────────────────────────────────────────────────────
const NAV = [
    { type: 'link',  label: 'Dashboard',    route: 'dashboard',          icon: LayoutDashboard },
    { type: 'sep',   label: 'ORDERS' },
    { type: 'link',  label: 'New Order',    route: 'orders.create',      icon: Plus,        badge: 'New' },
    { type: 'link',  label: 'My Orders',    route: 'orders.index',       icon: ShoppingCart },
    { type: 'sep',   label: 'PLATFORM' },
    { type: 'link',  label: 'Services',     route: 'services.index',     icon: Grid3x3 },
    { type: 'link',  label: 'API Center',   route: 'api-center.index',   icon: Terminal },
    { type: 'sep',   label: 'SMS SERVICES' },
    { type: 'link',  label: 'Buy Number',   route: 'sms.buy',            icon: Phone,       badge: 'New' },
    { type: 'link',  label: 'My Numbers',   route: 'sms.numbers',        icon: ShoppingBag },
    { type: 'sep',   label: 'ACCOUNT' },
    { type: 'link',  label: 'Deposit',      route: 'deposit.index',      icon: CreditCard,  badge: 'Fund' },
    { type: 'link',  label: 'Transactions', route: 'transactions.index', icon: ArrowUpDown },
    { type: 'link',  label: 'Referrals',    route: 'referrals.index',    icon: Users },
    { type: 'link',  label: 'Support',      route: 'tickets.index',      icon: MessageSquare },
    { type: 'link',  label: 'Settings',     route: 'settings.index',     icon: Settings2 },
];

const authUser     = computed(() => page.props.auth?.user);
const userInitials = computed(() => {
    const n = authUser.value?.name ?? '';
    return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?';
});

// ── Live balance ──────────────────────────────────────────────────────────────
const liveBalance    = ref(null);
const rawBalance     = computed(() => liveBalance.value ?? authUser.value?.wallet?.balance ?? 0);
const displayBalance = computed(() => formatAmount(rawBalance.value));

let balanceTimer = null;
async function pollBalance() {
    try {
        const res = await fetch('/wallet/balance', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
            const d    = await res.json();
            const prev = liveBalance.value;
            const next = parseFloat(d.balance) || 0;
            if (prev !== null && Math.abs(next - prev) > 0.000001) {
                toast.info('Balance updated', symbol.value + next.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }), 3500);
            }
            liveBalance.value = next;
        }
    } catch {}
}

// ── Flash → toasts ────────────────────────────────────────────────────────────
const flash = computed(() => page.props.flash ?? {});
watch(() => flash.value.success, v => { if (v) toast.success('Success', v); });
watch(() => flash.value.error,   v => { if (v) toast.error('Error', v); });
watch(() => flash.value.order_placed, d => {
    if (d && !route().current('orders.create')) {
        toast.push({ type: 'order', title: 'Order Placed!', message: d.service_name, duration: 5500 });
    }
});

// ── Notifications ─────────────────────────────────────────────────────────────
const notifications    = ref([]);
const unreadCount      = ref(page.props.auth?.unread_count ?? 0);
const notifLoading     = ref(false);
// IDs marked as read this session — survives fetchNotifications() refreshes (reactive Set for Vue tracking)
const markedReadIds = reactive(new Set());

// Bell shake animation when there are unread
const bellShake = computed(() => unreadCount.value > 0);

function applyLocalRead(list) {
    if (markedReadIds.size === 0) return list;
    return list.map(n => markedReadIds.has(n.id) ? { ...n, is_read: true } : n);
}

async function fetchNotifications() {
    if (notifLoading.value) return;
    notifLoading.value = true;
    try {
        const res = await fetch('/notifications/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
            const d = await res.json();
            notifications.value = applyLocalRead(d.notifications);
            unreadCount.value   = d.unread_count;
        }
    } catch {} finally {
        notifLoading.value = false;
    }
}

async function markNotifRead(n) {
    if (n.is_read || markedReadIds.has(n.id)) return;
    markedReadIds.add(n.id);
    n.is_read = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
    try {
        const res = await fetch(`/notifications/${n.id}/read`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
        });
        if (res.ok) {
            const d = await res.json();
            unreadCount.value = d.unread_count;
        }
    } catch {}
}

function handleNotifClick(n) {
    markNotifRead(n);
    if (n.action_url) {
        if (n.open_in_new_tab) {
            window.open(n.action_url, '_blank', 'noopener,noreferrer');
        } else {
            notifOpen.value = false;
            router.visit(n.action_url);
        }
    }
}

async function markAllRead() {
    try {
        const res = await fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
        });
        if (res.ok) {
            notifications.value.forEach(n => { markedReadIds.add(n.id); n.is_read = true; });
            unreadCount.value = 0;
        }
    } catch {}
}

function toggleNotif() {
    notifOpen.value = !notifOpen.value;
    profileOpen.value  = false;
    currencyOpen.value = false;
    if (notifOpen.value) fetchNotifications();
}

// Poll unread count silently every 30s
let notifTimer = null;
async function pollUnreadCount() {
    try {
        const res = await fetch('/notifications/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
            const d = await res.json();
            unreadCount.value = d.unread_count;
            if (notifOpen.value) notifications.value = applyLocalRead(d.notifications);
        }
    } catch {}
}

const priorityColor = p => ({
    success:   { bg: 'bg-emerald-500/10', text: 'text-emerald-500', border: 'border-emerald-500/20', dot: 'bg-emerald-500' },
    warning:   { bg: 'bg-amber-500/10',   text: 'text-amber-500',   border: 'border-amber-500/20',   dot: 'bg-amber-500' },
    error:     { bg: 'bg-rose-500/10',    text: 'text-rose-500',    border: 'border-rose-500/20',    dot: 'bg-rose-500' },
    promotion: { bg: 'bg-violet-500/10',  text: 'text-violet-500',  border: 'border-violet-500/20',  dot: 'bg-violet-500' },
    info:      { bg: 'bg-sky-500/10',     text: 'text-sky-500',     border: 'border-sky-500/20',     dot: 'bg-sky-500' },
}[p] ?? { bg: 'bg-sky-500/10', text: 'text-sky-500', border: 'border-sky-500/20', dot: 'bg-sky-500' });

function timeAgo(dateStr) {
    const d = new Date(dateStr);
    const diff = Math.floor((Date.now() - d) / 1000);
    if (diff < 60)      return 'Just now';
    if (diff < 3600)    return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)   return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800)  return `${Math.floor(diff / 86400)}d ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function isActive(r) {
    try { return route().current(r) || route().current(r + '.*'); } catch { return false; }
}
function selectCurrency(code) { setCurrency(code); currencyOpen.value = false; }
function closeSidebar()   { sidebarOpen.value = false; }
function closeDropdowns() { profileOpen.value = false; currencyOpen.value = false; notifOpen.value = false; }
function toggleCollapse() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sb-collapsed', sidebarCollapsed.value ? '1' : '0');
}
function onKey(e) { if (e.key === 'Escape') { closeSidebar(); closeDropdowns(); } }

// Listen for read-state sync events from the notification center page
function onNotifSync(e) {
    if (typeof e.detail?.unread_count === 'number') {
        unreadCount.value = e.detail.unread_count;
        // Refresh the popup list too if it's open
        if (notifOpen.value) fetchNotifications();
    }
}

onMounted(() => {
    applyTheme(getStoredTheme('dark') === 'dark');
    sidebarCollapsed.value = localStorage.getItem('sb-collapsed') === '1';
    document.addEventListener('keydown', onKey);
    document.addEventListener('notif-sync', onNotifSync);
    pollBalance();
    balanceTimer = setInterval(pollBalance, 5_000);
    notifTimer   = setInterval(pollUnreadCount, 30_000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { pollBalance(); pollUnreadCount(); } });
    window.addEventListener('balance-refresh', pollBalance);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKey);
    document.removeEventListener('notif-sync', onNotifSync);
    clearInterval(balanceTimer);
    clearInterval(notifTimer);
    window.removeEventListener('balance-refresh', pollBalance);
});

const FLAGS = { USD:'🇺🇸', NGN:'🇳🇬', EUR:'🇪🇺', GBP:'🇬🇧', CAD:'🇨🇦', AUD:'🇦🇺', JPY:'🇯🇵', CNY:'🇨🇳', INR:'🇮🇳', BRL:'🇧🇷', GHS:'🇬🇭', KES:'🇰🇪' };
const flag  = code => FLAGS[code] ?? '🌐';
const sidebarW = computed(() => sidebarCollapsed.value ? '68px' : '252px');

// Account level badge colours
const levelColors = {
    basic:    'text-slate-400 bg-slate-500/10',
    verified: 'text-sky-400 bg-sky-500/10',
    premium:  'text-violet-400 bg-violet-500/10',
    vip:      'text-amber-400 bg-amber-500/10',
};
const userLevel = computed(() => authUser.value?.account_level ?? 'basic');
</script>

<template>
    <ToastNotifications />

    <div class="min-h-screen">

        <!-- Mobile overlay -->
        <Transition enter-active-class="transition-opacity duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-[3px] lg:hidden" @click="closeSidebar" />
        </Transition>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- SIDEBAR                                                            -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <aside
            :style="{ width: sidebarW, background: `linear-gradient(180deg, var(--sb-from) 0%, var(--sb-to) 100%)`, borderRight: '1px solid var(--sb-border)' }"
            :class="['fixed inset-y-0 left-0 z-50 flex flex-col select-none transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)]',
                sidebarOpen ? 'translate-x-0 shadow-[4px_0_60px_rgba(0,0,0,0.4)]' : '-translate-x-full lg:translate-x-0']"
        >
            <!-- Ambient glow -->
            <div class="absolute top-0 left-0 right-0 h-48 pointer-events-none transition-opacity duration-300"
                :style="{ opacity: isDark ? '0.30' : '0' }"
                style="background: radial-gradient(ellipse at 50% -20%, rgba(14,165,233,0.15) 0%, transparent 70%)" />

            <!-- Logo -->
            <div :class="['h-[60px] flex items-center flex-shrink-0 border-b relative z-10 transition-all duration-300', sidebarCollapsed ? 'justify-center px-0' : 'justify-between px-4']"
                :style="{ borderColor: 'var(--sb-border)' }">
                <Link :href="route('dashboard')" :class="['flex items-center gap-2.5 group min-w-0', sidebarCollapsed ? 'justify-center' : '']" @click="closeSidebar">
                    <!-- Custom logo image -->
                    <img v-if="dashLogoUrl" :src="dashLogoUrl" alt="Logo"
                        :class="['object-contain flex-shrink-0 transition-all duration-200', sidebarCollapsed ? 'h-8 w-8' : 'h-8 max-w-[120px]']" />
                    <!-- Default icon + wordmark -->
                    <template v-else>
                        <div class="w-8 h-8 rounded-[10px] flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover:shadow-[0_0_20px_rgba(14,165,233,0.5)]"
                            style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 30%, transparent)">
                            <Zap class="w-4 h-4 text-white" :stroke-width="2.5" />
                        </div>
                        <span v-if="!sidebarCollapsed" class="text-[15px] font-black tracking-tight truncate" :class="isDark ? 'text-white' : 'text-slate-800'">
                            {{ siteSettings.name || 'NexaHub' }}
                        </span>
                    </template>
                </Link>
                <button v-if="!sidebarCollapsed" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.07] active:scale-90 transition-all" @click="closeSidebar">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 relative z-10" :class="sidebarCollapsed ? 'px-2' : 'px-3 space-y-px'">
                <template v-for="item in NAV" :key="item.label">
                    <div v-if="item.type === 'sep' && !sidebarCollapsed" class="pt-4 pb-1 first:pt-2 px-2 flex items-center gap-2">
                        <p class="text-[9px] font-black tracking-[0.16em] whitespace-nowrap" :style="{ color: 'var(--sb-sep-text)' }">{{ item.label }}</p>
                        <div class="flex-1 h-px" :style="{ background: 'var(--sb-sep-line)' }" />
                    </div>
                    <div v-else-if="item.type === 'sep' && sidebarCollapsed" class="my-1.5 mx-auto w-4 h-px" :style="{ background: 'var(--sb-dot)' }" />
                    <div v-else class="relative group/nav" :class="sidebarCollapsed ? 'mb-1' : ''">
                        <Link :href="route(item.route)"
                            :class="['relative flex items-center rounded-xl text-[13px] font-medium transition-all duration-150 overflow-hidden',
                                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto' : 'gap-3 px-3 py-[9px]',
                                isActive(item.route)
                                    ? 'text-sky-600 dark:text-sky-300'
                                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 active:scale-[0.98]']"
                            :style="isActive(item.route) ? 'background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.06)); box-shadow: inset 0 0 0 1px rgba(14,165,233,0.18), 0 0 16px rgba(14,165,233,0.07)' : ''"
                            @click="closeSidebar">
                            <div v-if="isActive(item.route) && !sidebarCollapsed"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-[60%] rounded-r-full"
                                style="background: linear-gradient(180deg, #38bdf8, #6366f1); box-shadow: 0 0 10px rgba(56,189,248,0.8)" />
                            <div v-if="!isActive(item.route)" class="absolute inset-0 rounded-xl opacity-0 group-hover/nav:opacity-100 transition-opacity duration-150" :style="{ background: 'var(--sb-hover)' }" />
                            <component :is="item.icon"
                                :class="['flex-shrink-0 relative transition-colors duration-150', sidebarCollapsed ? 'w-[18px] h-[18px]' : 'w-[17px] h-[17px]',
                                    isActive(item.route) ? 'text-sky-500 dark:text-sky-400' : 'text-slate-400 dark:text-slate-400 group-hover/nav:text-slate-600 dark:group-hover/nav:text-slate-300']"
                                :stroke-width="isActive(item.route) ? 2.5 : 1.8" />
                            <span v-if="!sidebarCollapsed" class="flex-1 truncate relative">{{ item.label }}</span>
                            <span v-if="item.badge && !isActive(item.route) && !sidebarCollapsed"
                                class="ml-auto text-[9px] font-black uppercase tracking-wide relative px-1.5 py-px rounded-full leading-none"
                                style="color: #38bdf8; background: rgba(14,165,233,0.12); border: 1px solid rgba(14,165,233,0.25)">
                                {{ item.badge }}
                            </span>
                        </Link>
                        <div v-if="sidebarCollapsed"
                            class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1.5 rounded-xl pointer-events-none z-50 text-[12px] font-semibold text-white whitespace-nowrap opacity-0 group-hover/nav:opacity-100 transition-all duration-150 scale-95 group-hover/nav:scale-100"
                            style="background: rgba(13,24,45,0.97); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 8px 24px rgba(0,0,0,0.6)">
                            {{ item.label }}
                            <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent" style="border-right-color: rgba(13,24,45,0.97)" />
                        </div>
                    </div>
                </template>
            </nav>

            <!-- User footer -->
            <div class="flex-shrink-0 border-t relative z-10" :style="{ borderColor: 'var(--sb-border)' }">
                <div v-if="!sidebarCollapsed" class="px-3 pt-3 pb-1">
                    <div class="flex items-center gap-2.5 px-2 py-2 rounded-xl cursor-default" :style="{ background: 'var(--sb-user-bg)' }">
                        <div class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden"
                            style="box-shadow: 0 0 12px rgba(14,165,233,0.4)">
                            <img v-if="authUser?.avatar" :src="`/storage/${authUser.avatar}`" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-white text-[11px] font-black"
                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">{{ userInitials }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-bold truncate leading-tight text-slate-700 dark:text-slate-200">{{ authUser?.name }}</p>
                            <p class="text-[11px] truncate leading-tight mt-px flex items-center gap-1" :style="{ color: 'var(--sb-sep-text)' }">
                                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse inline-block flex-shrink-0" />
                                {{ symbol }}{{ displayBalance }}
                            </p>
                        </div>
                        <Link :href="route('settings.index')" @click="closeSidebar"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.08] active:scale-90 transition-all flex-shrink-0">
                            <Settings2 class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" @click="closeSidebar"
                        class="mt-1.5 w-full flex items-center gap-2 px-2 py-2 rounded-xl text-[12.5px] font-semibold text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/[0.08] active:scale-[0.98] transition-all">
                        <LogOut class="w-3.5 h-3.5 flex-shrink-0" />
                        Sign out
                    </Link>
                </div>
                <div v-if="sidebarCollapsed" class="px-2 pb-1 flex flex-col items-center gap-1">
                    <Link :href="route('logout')" method="post" as="button"
                        class="relative group/nav w-10 h-10 flex items-center justify-center rounded-xl text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/[0.08] active:scale-90 transition-all" title="Sign out">
                        <LogOut class="w-[17px] h-[17px]" />
                    </Link>
                </div>
                <button @click="toggleCollapse"
                    :class="['w-full flex items-center justify-center h-10 transition-colors group', !sidebarCollapsed ? 'border-t' : '']"
                    :style="{ borderColor: 'var(--sb-sep-line)' }" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <component :is="sidebarCollapsed ? ChevronRight : ChevronLeft"
                        class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600 group-hover:text-slate-700 dark:group-hover:text-slate-400 transition-colors" />
                </button>
            </div>
        </aside>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- MAIN                                                               -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div :style="{ paddingLeft: `max(${sidebarW}, 0px)` }" class="hidden lg:block transition-all duration-300 ease-[cubic-bezier(0.25,0.8,0.25,1)]" />
        <div :class="['lg:transition-all lg:duration-300 lg:ease-[cubic-bezier(0.25,0.8,0.25,1)] flex flex-col min-h-screen', sidebarCollapsed ? 'lg:pl-[68px]' : 'lg:pl-[252px]']">

            <!-- ── Navbar ───────────────────────────────────────────────────── -->
            <header class="sticky top-0 z-30 h-[60px] flex items-center gap-2 sm:gap-3 px-3 sm:px-5"
                :style="{ background: 'var(--nav-bg)', backdropFilter: 'blur(20px) saturate(1.5)', borderBottom: '1px solid var(--nav-border)' }">

                <!-- Mobile hamburger -->
                <button class="lg:hidden w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.07] active:scale-95 transition-all flex-shrink-0"
                    :style="{ border: '1px solid var(--nav-btn-br)' }" @click="sidebarOpen = true" aria-label="Open menu">
                    <Menu class="w-[18px] h-[18px]" />
                </button>

                <div class="flex-1 hidden md:block" />

                <div class="ml-auto lg:ml-0 flex items-center gap-1.5">

                    <!-- Balance chip -->
                    <div class="hidden sm:flex items-center gap-1.5 h-8 px-3 rounded-xl select-none"
                        style="background: rgba(14,165,233,0.08); border: 1px solid rgba(14,165,233,0.15)">
                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse flex-shrink-0" />
                        <span class="text-[12px] font-bold font-mono text-sky-600 dark:text-sky-300">{{ symbol }}{{ displayBalance }}</span>
                    </div>

                    <!-- Currency picker -->
                    <div v-if="currencies.length >= 1" class="relative">
                        <button @click="currencyOpen = !currencyOpen; profileOpen = false; notifOpen = false"
                            class="flex items-center gap-1 sm:gap-1.5 h-9 px-2 sm:px-2.5 rounded-xl text-slate-600 dark:text-slate-300 active:scale-95 transition-all"
                            :style="{ border: '1px solid var(--nav-btn-br)', background: 'var(--nav-btn-bg)' }">
                            <span class="text-[15px] leading-none">{{ flag(displayCurrency) }}</span>
                            <span class="hidden sm:block text-[12px] font-bold">{{ displayCurrency }}</span>
                            <ChevronDown class="w-3 h-3 text-slate-400 dark:text-slate-600 transition-transform duration-200 hidden sm:block" :class="{ 'rotate-180': currencyOpen }" />
                        </button>
                        <Transition enter-active-class="transition-all duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-100 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 -translate-y-1">
                            <div v-if="currencyOpen" class="absolute right-0 top-full mt-2 w-52 z-50 rounded-2xl overflow-hidden"
                                :style="{ background: 'var(--dd-bg)', border: '1px solid var(--dd-border)', boxShadow: 'var(--dd-shadow)' }">
                                <div class="px-3 py-2.5 border-b" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <p class="text-[9.5px] font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-600">Select Currency</p>
                                </div>
                                <div class="py-1">
                                    <button v-for="c in currencies" :key="c.code" @click="selectCurrency(c.code)"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 transition-colors active:bg-sky-500/5"
                                        :style="displayCurrency === c.code ? 'background: rgba(14,165,233,0.08)' : ''"
                                        @mouseover="$event.currentTarget.style.background = displayCurrency === c.code ? 'rgba(14,165,233,0.1)' : 'var(--dd-hover)'"
                                        @mouseleave="$event.currentTarget.style.background = displayCurrency === c.code ? 'rgba(14,165,233,0.08)' : ''">
                                        <span class="text-[17px] leading-none flex-shrink-0">{{ flag(c.code) }}</span>
                                        <div class="flex-1 text-left min-w-0">
                                            <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-200">{{ c.code }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-600 truncate">{{ c.name }}</p>
                                        </div>
                                        <span class="text-[11px] font-mono text-slate-500 flex-shrink-0">{{ c.symbol }}</span>
                                    </button>
                                </div>
                                <div v-if="currencies.length > 1" class="px-3 py-2 border-t" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-600">1 USD = {{ current?.symbol }}{{ Number(current?.exchange_rate ?? 1).toLocaleString() }} {{ current?.code }}</p>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Theme toggle -->
                    <button @click="toggleTheme"
                        class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95 transition-all"
                        :style="{ border: '1px solid var(--nav-btn-br)', background: 'var(--nav-btn-bg)' }" :title="isDark ? 'Light mode' : 'Dark mode'">
                        <Sun v-if="isDark" class="w-4 h-4" />
                        <Moon v-else class="w-4 h-4" />
                    </button>

                    <!-- ── Notification Bell ────────────────────────────────── -->
                    <div class="relative">
                        <button @click="toggleNotif"
                            class="relative w-9 h-9 flex items-center justify-center rounded-xl transition-all active:scale-95"
                            :class="notifOpen ? 'text-sky-500 dark:text-sky-400' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                            :style="{ border: notifOpen ? '1px solid rgba(14,165,233,0.4)' : '1px solid var(--nav-btn-br)', background: notifOpen ? 'rgba(14,165,233,0.08)' : 'var(--nav-btn-bg)' }"
                            aria-label="Notifications">
                            <component :is="unreadCount > 0 ? BellDot : Bell"
                                class="w-4 h-4 transition-all"
                                :class="{ 'bell-shake': bellShake, 'text-sky-500 dark:text-sky-400': unreadCount > 0 && !notifOpen }" />
                            <!-- Red glow badge -->
                            <Transition enter-active-class="transition-all duration-200" enter-from-class="scale-0 opacity-0" enter-to-class="scale-100 opacity-100"
                                leave-active-class="transition-all duration-150" leave-from-class="scale-100 opacity-100" leave-to-class="scale-0 opacity-0">
                                <span v-if="unreadCount > 0"
                                    class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center text-[9px] font-black text-white rounded-full px-1 leading-none"
                                    style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 0 10px rgba(239,68,68,0.7), 0 2px 6px rgba(239,68,68,0.5)">
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </Transition>
                        </button>

                        <!-- Notification Dropdown -->
                        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 scale-95 translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="notifOpen" class="absolute right-0 top-full mt-2 w-[360px] z-50 rounded-2xl overflow-hidden flex flex-col max-h-[480px]"
                                :style="{ background: 'var(--dd-bg)', border: '1px solid var(--dd-border)', boxShadow: 'var(--dd-shadow)' }">

                                <!-- Header -->
                                <div class="flex items-center justify-between px-4 py-3 border-b flex-shrink-0" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[13px] font-bold text-slate-800 dark:text-white">Notifications</p>
                                        <span v-if="unreadCount > 0"
                                            class="text-[10px] font-black px-1.5 py-0.5 rounded-full text-white leading-none"
                                            style="background: linear-gradient(135deg, #ef4444, #dc2626)">
                                            {{ unreadCount }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button v-if="unreadCount > 0" @click="markAllRead"
                                            class="flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-sky-500 hover:bg-sky-500/10 transition-colors">
                                            <CheckCheck class="w-3 h-3" />
                                            All read
                                        </button>
                                        <Link :href="route('notifications.index')" @click="notifOpen = false"
                                            class="flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                                            <ExternalLink class="w-3 h-3" />
                                            View all
                                        </Link>
                                    </div>
                                </div>

                                <!-- Notification list -->
                                <div class="overflow-y-auto flex-1">
                                    <!-- Loading -->
                                    <div v-if="notifLoading" class="py-8 flex items-center justify-center">
                                        <div class="w-5 h-5 border-2 border-sky-500/30 border-t-sky-500 rounded-full animate-spin" />
                                    </div>

                                    <!-- Empty -->
                                    <div v-else-if="notifications.length === 0" class="py-12 flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background: rgba(14,165,233,0.08)">
                                            <Bell class="w-6 h-6 text-sky-500/50" />
                                        </div>
                                        <p class="text-[13px] font-medium text-slate-400 dark:text-slate-600">All caught up!</p>
                                        <p class="text-[11px] text-slate-300 dark:text-slate-700">No notifications yet.</p>
                                    </div>

                                    <!-- Items -->
                                    <div v-else>
                                        <div v-for="n in notifications" :key="n.id"
                                            class="group/notif relative flex gap-3 px-4 py-3 transition-colors border-b last:border-0 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/[0.02]"
                                            :class="n.is_read ? '' : 'bg-sky-500/[0.03] dark:bg-sky-500/[0.05]'"
                                            :style="{ borderColor: 'var(--dd-sep)' }"
                                            @click="handleNotifClick(n)">

                                            <!-- Icon -->
                                            <div class="flex-shrink-0 pt-0.5">
                                                <div :class="['w-8 h-8 rounded-xl flex items-center justify-center text-sm', priorityColor(n.priority).bg]">
                                                    <span>{{ n.type === 'deposit_success' ? '💰' : n.type === 'security_alert' ? '🔒' : n.type === 'promotional' || n.type === 'flash_sale' ? '🎁' : n.type === 'bonus_reward' || n.type === 'loyalty_vip' ? '⭐' : n.type === 'maintenance' || n.type === 'service_outage' ? '⚙️' : n.priority === 'error' ? '❌' : n.priority === 'success' ? '✅' : n.priority === 'warning' ? '⚠️' : '🔔' }}</span>
                                                </div>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-[12.5px] font-semibold leading-snug" :class="n.is_read ? 'text-slate-600 dark:text-slate-400' : 'text-slate-800 dark:text-white'">
                                                        {{ n.title }}
                                                        <span v-if="n.is_pinned" class="ml-1 text-[9px] text-amber-500">📌</span>
                                                    </p>
                                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                                        <span v-if="!n.is_read" class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityColor(n.priority).dot" />
                                                        <span class="text-[10px] text-slate-400 dark:text-slate-600 whitespace-nowrap">{{ timeAgo(n.created_at) }}</span>
                                                    </div>
                                                </div>
                                                <p class="text-[11.5px] text-slate-400 dark:text-slate-400 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                                <!-- Bottom row: CTA + mark read -->
                                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                                    <!-- Mark as read button (unread only) -->
                                                    <button v-if="!n.is_read" @click.stop="markNotifRead(n)"
                                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-semibold text-slate-400 hover:text-emerald-500 hover:bg-emerald-500/10 transition-all active:scale-95 border border-transparent hover:border-emerald-500/20">
                                                        <Check class="w-3 h-3" />
                                                        Mark read
                                                    </button>
                                                    <!-- Premium CTA button -->
                                                    <div v-if="n.action_url" @click.stop>
                                                        <a :href="n.action_url"
                                                           :target="n.open_in_new_tab ? '_blank' : '_self'"
                                                           :rel="n.open_in_new_tab ? 'noopener noreferrer' : undefined"
                                                           @click="markNotifRead(n)"
                                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all active:scale-95"
                                                           style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.2); color: #38bdf8; text-decoration: none">
                                                            {{ n.action_label || 'View Details' }}
                                                            <ExternalLink v-if="n.open_in_new_tab" class="w-2.5 h-2.5" />
                                                            <ChevronRight v-else class="w-2.5 h-2.5" />
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="border-t flex-shrink-0 px-4 py-2.5" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <Link :href="route('notifications.index')" @click="notifOpen = false"
                                        class="block w-full text-center text-[12px] font-semibold text-sky-500 hover:text-sky-400 transition-colors">
                                        View notification center →
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- ── Profile Dropdown ─────────────────────────────────── -->
                    <div class="relative">
                        <button @click="profileOpen = !profileOpen; currencyOpen = false; notifOpen = false"
                            class="flex items-center gap-1.5 py-1 pl-1 pr-2 sm:pr-2.5 rounded-xl active:scale-95 transition-all"
                            :style="{ border: profileOpen ? '1px solid rgba(14,165,233,0.4)' : '1px solid var(--nav-btn-br)', background: profileOpen ? 'rgba(14,165,233,0.06)' : 'var(--nav-btn-bg)' }">
                            <div class="w-7 h-7 rounded-full flex-shrink-0 overflow-hidden" style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">
                                <img v-if="authUser?.avatar" :src="`/storage/${authUser.avatar}`" class="w-full h-full object-cover" />
                                <span v-else class="w-full h-full flex items-center justify-center text-white text-[11px] font-black">{{ userInitials }}</span>
                            </div>
                            <span class="hidden sm:block text-[13px] font-semibold text-slate-600 dark:text-slate-300 max-w-[72px] truncate">
                                {{ authUser?.name?.split(' ')[0] }}
                            </span>
                            <ChevronDown class="hidden sm:block w-3.5 h-3.5 text-slate-400 dark:text-slate-600 transition-transform duration-200" :class="{ 'rotate-180': profileOpen }" />
                        </button>

                        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 scale-95 translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="profileOpen" class="absolute right-0 top-full mt-2 w-[300px] z-50 rounded-2xl overflow-hidden"
                                :style="{ background: 'var(--dd-bg)', border: '1px solid var(--dd-border)', boxShadow: 'var(--dd-shadow)' }">

                                <!-- User info header -->
                                <div class="px-4 py-3.5 border-b" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0">
                                            <img v-if="authUser?.avatar" :src="`/storage/${authUser.avatar}`" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-white text-[13px] font-black"
                                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">{{ userInitials }}</div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-bold text-slate-800 dark:text-white truncate">{{ authUser?.name }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 truncate">{{ authUser?.email }}</p>
                                            <span class="inline-flex items-center mt-0.5 text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5 rounded-full"
                                                :class="levelColors[userLevel]">
                                                {{ userLevel }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Balance mini -->
                                    <div class="mt-2.5 flex items-center justify-between px-2 py-1.5 rounded-xl" style="background: rgba(14,165,233,0.06); border: 1px solid rgba(14,165,233,0.12)">
                                        <span class="text-[11px] text-slate-400 dark:text-slate-400">Balance</span>
                                        <span class="text-[12px] font-bold font-mono text-sky-500">{{ symbol }}{{ displayBalance }}</span>
                                    </div>
                                </div>

                                <!-- Menu items -->
                                <div class="p-2 space-y-0.5">
                                    <Link :href="route('settings.index')" @click="profileOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all group/item hover:bg-slate-50 dark:hover:bg-white/[0.05]">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105"
                                            style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.1)); border: 1px solid rgba(14,165,233,0.2)">
                                            <Settings2 class="w-3.5 h-3.5 text-sky-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-semibold leading-none">Settings</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Profile, security & preferences</p>
                                        </div>
                                    </Link>
                                    <Link :href="route('api-center.index')" @click="profileOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all group/item hover:bg-slate-50 dark:hover:bg-white/[0.05]">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105"
                                            style="background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(99,102,241,0.1)); border: 1px solid rgba(139,92,246,0.2)">
                                            <Code2 class="w-3.5 h-3.5 text-violet-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-semibold leading-none">API Center</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Keys, docs & usage logs</p>
                                        </div>
                                    </Link>
                                    <Link :href="route('notifications.index')" @click="profileOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all group/item hover:bg-slate-50 dark:hover:bg-white/[0.05]">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105 relative"
                                            style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.08)); border: 1px solid rgba(245,158,11,0.2)">
                                            <Bell class="w-3.5 h-3.5 text-amber-500" />
                                            <span v-if="unreadCount > 0"
                                                class="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 text-[9px] font-black text-white flex items-center justify-center rounded-full px-1 leading-none"
                                                style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 0 8px rgba(239,68,68,0.6)">
                                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-semibold leading-none">Notifications</p>
                                            <p class="text-[11px] mt-0.5" :class="unreadCount > 0 ? 'text-amber-500 font-semibold' : 'text-slate-400 dark:text-slate-400'">
                                                {{ unreadCount > 0 ? `${unreadCount} unread message${unreadCount > 1 ? 's' : ''}` : 'All caught up' }}
                                            </p>
                                        </div>
                                    </Link>
                                    <Link :href="route('transactions.index')" @click="profileOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all group/item hover:bg-slate-50 dark:hover:bg-white/[0.05]">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105"
                                            style="background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(14,165,233,0.08)); border: 1px solid rgba(16,185,129,0.2)">
                                            <ArrowUpDown class="w-3.5 h-3.5 text-emerald-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-semibold leading-none">Transactions</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Full account activity ledger</p>
                                        </div>
                                    </Link>
                                    <Link :href="route('deposit.index')" @click="profileOpen = false"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all group/item hover:bg-slate-50 dark:hover:bg-white/[0.05]">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105"
                                            style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(16,185,129,0.1)); border: 1px solid rgba(14,165,233,0.2)">
                                            <CreditCard class="w-3.5 h-3.5 text-sky-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-semibold leading-none">Deposit Funds</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Add balance via crypto</p>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Sign out -->
                                <div class="p-2 pt-0 border-t" :style="{ borderColor: 'var(--dd-sep)' }">
                                    <Link :href="route('logout')" method="post" as="button" @click="profileOpen = false"
                                        class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-rose-500 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-500/[0.07] transition-all group/item">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200 group-hover/item:scale-105"
                                            style="background: linear-gradient(135deg, rgba(239,68,68,0.12), rgba(220,38,38,0.08)); border: 1px solid rgba(239,68,68,0.2)">
                                            <LogOut class="w-3.5 h-3.5 text-rose-500" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[13px] font-semibold leading-none">Sign out</p>
                                            <p class="text-[11px] text-rose-400/70 mt-0.5">End your session</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-5 lg:p-6">
                <slot />
            </main>
        </div>

        <!-- Click-outside overlay -->
        <div v-if="profileOpen || currencyOpen || notifOpen" class="fixed inset-0 z-20" @click="closeDropdowns" />
    </div>
</template>

<style>
@keyframes bell-shake {
    0%,100% { transform: rotate(0deg); }
    10%,50% { transform: rotate(-14deg); }
    30%,70% { transform: rotate(14deg); }
    90%     { transform: rotate(-7deg); }
}
.bell-shake { animation: bell-shake 1.2s ease-in-out infinite; transform-origin: 50% 0; }
</style>

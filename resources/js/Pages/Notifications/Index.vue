<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle, Bell, BellDot, CheckCheck, CheckCircle,
    ChevronRight, ExternalLink, Filter, Gift,
    Info, Megaphone, Pin, RefreshCw, Search,
    Shield, Star, X, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    notifications: { type: Object, default: () => ({ data: [], meta: {} }) },
    unreadCount:   { type: Number, default: 0 },
});

// ── Filtering ─────────────────────────────────────────────────────────────────
const activeFilter   = ref('all');
const activeCategory = ref('all');
const searchQuery    = ref('');

// Local reactive copy of notifications so UI updates instantly
const localNotifs    = ref([...(props.notifications.data ?? [])]);
const localUnread    = ref(props.unreadCount);
// IDs clicked this session — guarantees button never reappears (reactive Set for Vue tracking)
const markedReadIds = reactive(new Set());

onMounted(async () => {
    if (localUnread.value > 0) {
        await markAllRead();
    } else {
        document.dispatchEvent(new CustomEvent('notif-sync', { detail: { unread_count: 0 } }));
    }
});

const filters = [
    { key: 'all',    label: 'All' },
    { key: 'unread', label: 'Unread' },
    { key: 'read',   label: 'Read' },
    { key: 'pinned', label: 'Pinned' },
];

const categories = [
    { key: 'all',         label: 'All Categories', icon: Bell },
    { key: 'transaction', label: 'Transactions',   icon: Zap },
    { key: 'security',    label: 'Security',        icon: Shield },
    { key: 'promotion',   label: 'Promotions',      icon: Gift },
    { key: 'system',      label: 'System',          icon: Info },
];

const filteredNotifs = computed(() => {
    let list = localNotifs.value;

    if (activeFilter.value === 'unread') list = list.filter(n => !n.is_read);
    else if (activeFilter.value === 'read') list = list.filter(n => n.is_read);
    else if (activeFilter.value === 'pinned') list = list.filter(n => n.is_pinned);

    if (activeCategory.value !== 'all') list = list.filter(n => n.category === activeCategory.value);

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(n => n.title.toLowerCase().includes(q) || n.message.toLowerCase().includes(q));
    }

    return list;
});

// Grouped by date
const grouped = computed(() => {
    const groups = {};
    filteredNotifs.value.forEach(n => {
        const date = new Date(n.created_at);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);

        let label;
        if (date.toDateString() === today.toDateString())     label = 'Today';
        else if (date.toDateString() === yesterday.toDateString()) label = 'Yesterday';
        else label = date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

        if (!groups[label]) groups[label] = [];
        groups[label].push(n);
    });
    return groups;
});

// ── Actions ───────────────────────────────────────────────────────────────────
const processing = ref(new Set());

function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

function broadcastUnreadCount(count) {
    // Notify the layout (bell counter) in real-time
    document.dispatchEvent(new CustomEvent('notif-sync', { detail: { unread_count: count } }));
}

async function markRead(n) {
    if (n.is_read || markedReadIds.has(n.id) || processing.value.has(n.id)) return;
    markedReadIds.add(n.id);
    n.is_read = true;
    n.read_at = new Date().toISOString();
    localUnread.value = Math.max(0, localUnread.value - 1);
    broadcastUnreadCount(localUnread.value);
    processing.value.add(n.id);
    try {
        const res = await fetch(`/notifications/${n.id}/read`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
        });
        if (res.ok) {
            const d = await res.json();
            localUnread.value = d.unread_count;
            broadcastUnreadCount(d.unread_count);
        }
    } finally { processing.value.delete(n.id); }
}

async function markAllRead() {
    const res = await fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
    });
    if (res.ok) {
        localNotifs.value.forEach(n => { n.is_read = true; });
        localUnread.value = 0;
        broadcastUnreadCount(0);
    }
}

function handleCardClick(n) {
    markRead(n);
}

function handleCtaClick(n) {
    markRead(n);
}

// ── Styles ────────────────────────────────────────────────────────────────────
const priorityConfig = {
    success:   { bg: 'bg-emerald-500/10 dark:bg-emerald-500/15', text: 'text-emerald-600 dark:text-emerald-400', border: 'border-l-emerald-500', dot: 'bg-emerald-500', badge: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' },
    warning:   { bg: 'bg-amber-500/10 dark:bg-amber-500/15',     text: 'text-amber-600 dark:text-amber-400',     border: 'border-l-amber-500',   dot: 'bg-amber-500',   badge: 'bg-amber-500/15 text-amber-600 dark:text-amber-400' },
    error:     { bg: 'bg-rose-500/10 dark:bg-rose-500/15',       text: 'text-rose-600 dark:text-rose-400',       border: 'border-l-rose-500',    dot: 'bg-rose-500',    badge: 'bg-rose-500/15 text-rose-600 dark:text-rose-400' },
    promotion: { bg: 'bg-violet-500/10 dark:bg-violet-500/15',   text: 'text-violet-600 dark:text-violet-400',   border: 'border-l-violet-500',  dot: 'bg-violet-500',  badge: 'bg-violet-500/15 text-violet-600 dark:text-violet-400' },
    info:      { bg: 'bg-sky-500/10 dark:bg-sky-500/15',         text: 'text-sky-600 dark:text-sky-400',         border: 'border-l-sky-500',     dot: 'bg-sky-500',     badge: 'bg-sky-500/15 text-sky-600 dark:text-sky-400' },
};
const pc = p => priorityConfig[p] ?? priorityConfig.info;

const typeIcon = type => {
    const map = {
        deposit_success: '💰', deposit_failed: '❌', number_purchased: '📱',
        otp_received: '🔑', refund_processed: '↩️', promotional: '🎁',
        maintenance: '⚙️', security_alert: '🔒', new_feature: '✨',
        verification_reminder: '📧', inactive_reminder: '😴',
        bonus_reward: '🎉', balance_low: '⚠️', admin_custom: '📢',
        flash_sale: '⚡', service_outage: '🔴', provider_maintenance: '🔧',
        cashback: '💸', loyalty_vip: '👑', welcome: '👋',
    };
    return map[type] ?? '🔔';
};

const typeLabel = type => type?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) ?? 'Notification';

function timeAgo(dateStr) {
    const d = new Date(dateStr);
    const diff = Math.floor((Date.now() - d) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return `${Math.floor(diff / 60)} min ago`;
    if (diff < 86400)  return `${Math.floor(diff / 3600)} hr ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const unread = computed(() => localNotifs.value.filter(n => !n.is_read).length);
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>

        <!-- Page header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.15))">
                        <BellDot class="w-4.5 h-4.5 text-sky-500" />
                    </div>
                    Notifications
                    <span v-if="unread > 0"
                        class="text-[11px] font-black px-2 py-0.5 rounded-full text-white"
                        style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 2px 8px rgba(239,68,68,0.4)">
                        {{ unread }} new
                    </span>
                </h1>
                <p class="text-[13px] text-slate-400 dark:text-slate-400 mt-0.5">Stay updated with your account activity and announcements.</p>
            </div>
            <div class="flex items-center gap-2">
                <button v-if="unread > 0" @click="markAllRead"
                    class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-[12px] font-semibold text-sky-600 dark:text-sky-400 border border-sky-500/20 hover:bg-sky-500/10 transition-all active:scale-95">
                    <CheckCheck class="w-3.5 h-3.5" />
                    Mark all read
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

            <!-- ── Sidebar filters ──────────────────────────────────────────── -->
            <div class="lg:col-span-1 space-y-3">

                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search notifications…"
                        class="w-full h-10 pl-9 pr-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                </div>

                <!-- Status filter -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-2">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 px-2 pt-1 pb-2">Status</p>
                    <button v-for="f in filters" :key="f.key" @click="activeFilter = f.key"
                        :class="['w-full flex items-center justify-between px-3 py-2 rounded-xl text-[12.5px] font-medium transition-all',
                            activeFilter === f.key ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5']">
                        <span>{{ f.label }}</span>
                        <span v-if="f.key === 'unread' && unread > 0"
                            class="text-[10px] font-black px-1.5 py-0.5 rounded-full text-white leading-none"
                            style="background: #ef4444">{{ unread }}</span>
                    </button>
                </div>

                <!-- Category filter -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-2">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 px-2 pt-1 pb-2">Category</p>
                    <button v-for="cat in categories" :key="cat.key" @click="activeCategory = cat.key"
                        :class="['w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[12.5px] font-medium transition-all',
                            activeCategory === cat.key ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5']">
                        <component :is="cat.icon" class="w-3.5 h-3.5 flex-shrink-0" />
                        {{ cat.label }}
                    </button>
                </div>
            </div>

            <!-- ── Notification list ────────────────────────────────────────── -->
            <div class="lg:col-span-3 space-y-6">

                <!-- Empty state -->
                <div v-if="Object.keys(grouped).length === 0"
                    class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-16 flex flex-col items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.08))">
                        <Bell class="w-8 h-8 text-sky-500/40" />
                    </div>
                    <div class="text-center">
                        <p class="text-[15px] font-bold text-slate-700 dark:text-slate-300">No notifications found</p>
                        <p class="text-[13px] text-slate-400 dark:text-slate-600 mt-1">
                            {{ searchQuery ? 'Try a different search term.' : 'You\'re all caught up!' }}
                        </p>
                    </div>
                </div>

                <!-- Grouped notifications -->
                <div v-for="(items, dateLabel) in grouped" :key="dateLabel" class="space-y-2">
                    <!-- Date separator -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 whitespace-nowrap px-2 py-0.5 bg-slate-50 dark:bg-white/[0.03] rounded-full border border-slate-200 dark:border-white/[0.06]">{{ dateLabel }}</span>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-white/[0.06]" />
                    </div>

                    <!-- Cards -->
                    <div v-for="n in items" :key="n.id"
                        class="group relative rounded-2xl border transition-all duration-200 overflow-hidden cursor-pointer border-l-4"
                        :class="[
                            n.is_read
                                ? 'bg-white dark:bg-[#0d1e35] border-slate-200 dark:border-sky-500/10 hover:border-slate-300 dark:hover:border-sky-500/20'
                                : 'bg-sky-500/[0.02] dark:bg-sky-500/[0.04] border-sky-500/25 dark:border-sky-500/30 shadow-sm shadow-sky-500/5',
                            pc(n.priority).border,
                        ]"
                        @click="handleCardClick(n)">

                        <!-- Unread glow (subtle) -->
                        <div v-if="!n.is_read" class="absolute inset-0 pointer-events-none"
                            style="background: linear-gradient(135deg, rgba(14,165,233,0.025), transparent)" />

                        <div class="relative p-4 flex gap-4">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-lg relative', pc(n.priority).bg]">
                                    {{ typeIcon(n.type) }}
                                    <!-- Unread dot badge -->
                                    <span v-if="!n.is_read"
                                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white dark:border-[#0d1e35]"
                                        :class="pc(n.priority).dot" />
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-start gap-x-3 gap-y-1 mb-1">
                                    <h3 class="text-[13.5px] font-bold leading-snug" :class="n.is_read ? 'text-slate-600 dark:text-slate-400' : 'text-slate-900 dark:text-white'">
                                        {{ n.title }}
                                        <span v-if="n.is_pinned" class="ml-1">📌</span>
                                    </h3>
                                    <div class="flex items-center gap-1.5 ml-auto flex-shrink-0">
                                        <!-- Priority badge -->
                                        <span :class="['text-[9.5px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full leading-none', pc(n.priority).badge]">
                                            {{ n.priority }}
                                        </span>
                                        <!-- Category chip -->
                                        <span class="text-[9.5px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full leading-none bg-slate-100 dark:bg-white/8 text-slate-500 dark:text-slate-400">
                                            {{ n.category }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-[12.5px] leading-relaxed" :class="n.is_read ? 'text-slate-400 dark:text-slate-400' : 'text-slate-600 dark:text-slate-300'">{{ n.message }}</p>

                                <div class="flex items-center gap-3 mt-2.5 flex-wrap">
                                    <!-- Type label -->
                                    <span class="text-[10.5px] text-slate-400 dark:text-slate-600">{{ typeLabel(n.type) }}</span>
                                    <span class="text-[10.5px] text-slate-300 dark:text-slate-700">·</span>
                                    <!-- Timestamp -->
                                    <span class="text-[10.5px] text-slate-400 dark:text-slate-600">{{ timeAgo(n.created_at) }}</span>

                                    <div class="ml-auto flex items-center gap-2">
                                        <!-- Mark read -->
                                        <button v-if="!n.is_read && !markedReadIds.has(n.id)" @click.stop="markRead(n)"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 hover:text-emerald-500 transition-colors">
                                            <CheckCircle class="w-3 h-3" />
                                            Mark read
                                        </button>

                                        <!-- Premium CTA button -->
                                        <a v-if="n.action_url"
                                           :href="n.action_url"
                                           :target="n.open_in_new_tab ? '_blank' : '_self'"
                                           :rel="n.open_in_new_tab ? 'noopener noreferrer' : undefined"
                                           @click.stop="handleCtaClick(n)"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11.5px] font-bold transition-all active:scale-95"
                                           style="background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(99,102,241,0.08)); border: 1px solid rgba(14,165,233,0.22); color: #38bdf8; text-decoration: none">
                                            {{ n.action_label || 'View Details' }}
                                            <ExternalLink v-if="n.open_in_new_tab" class="w-3 h-3" />
                                            <ChevronRight v-else class="w-3 h-3" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.meta?.last_page > 1" class="flex items-center justify-center gap-2 pt-2">
                    <Link v-if="notifications.links?.prev" :href="notifications.links.prev"
                        class="px-4 py-2 rounded-xl text-[12px] font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/8 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                        ← Previous
                    </Link>
                    <span class="text-[12px] text-slate-400 dark:text-slate-600">
                        Page {{ notifications.meta?.current_page }} of {{ notifications.meta?.last_page }}
                    </span>
                    <Link v-if="notifications.links?.next" :href="notifications.links.next"
                        class="px-4 py-2 rounded-xl text-[12px] font-semibold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/8 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                        Next →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

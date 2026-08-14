<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle,
    ChevronRight,
    Clock,
    Mail,
    MessageSquare,
    Pin,
    Search,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    tickets:    Object,
    stats:      Object,
    filters:    Object,
    statuses:   Array,
    priorities: Array,
    categories: Array,
});

const flash = computed(() => usePage().props.flash ?? {});

const search   = ref(props.filters.search   ?? '');
const status   = ref(props.filters.status   ?? '');
const priority = ref(props.filters.priority ?? '');
const category = ref(props.filters.category ?? '');

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 350);
});

function applyFilters() {
    router.get(route('admin.tickets.index'), {
        search:   search.value   || undefined,
        status:   status.value   || undefined,
        priority: priority.value || undefined,
        category: category.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = ''; status.value = ''; priority.value = ''; category.value = '';
    router.get(route('admin.tickets.index'), {}, { preserveState: false });
}

const hasFilters = computed(() => search.value || status.value || priority.value || category.value);

// ── Status & display config ──────────────────────────────────────────────────

const statusConfig = {
    new: {
        label: 'Pending',
        color: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
        dot:   'bg-sky-500',
        stat:  'text-sky-600 dark:text-sky-400',
    },
    in_review: {
        label: 'In Review',
        color: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        dot:   'bg-amber-500',
        stat:  'text-amber-600 dark:text-amber-400',
    },
    waiting_for_user: {
        label: 'Waiting',
        color: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
        dot:   'bg-violet-500',
        stat:  'text-violet-600 dark:text-violet-400',
    },
    user_replied: {
        label: 'Pending',
        color: 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
        dot:   'bg-orange-500',
        stat:  'text-orange-600 dark:text-orange-400',
    },
    escalated: {
        label: 'Escalated',
        color: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
        dot:   'bg-rose-500',
        stat:  'text-rose-600 dark:text-rose-400',
    },
    resolved: {
        label: 'Resolved',
        color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        dot:   'bg-emerald-500',
        stat:  'text-emerald-600 dark:text-emerald-400',
    },
    closed: {
        label: 'Closed',
        color: 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-500',
        dot:   'bg-slate-400',
        stat:  'text-slate-500 dark:text-slate-400',
    },
};

const statOrder = ['unread', 'new', 'user_replied', 'in_review', 'waiting_for_user', 'escalated', 'resolved', 'closed', 'total'];

const statConfig = {
    unread: { label: 'Unread', stat: 'text-red-600 dark:text-red-400', isUnread: true },
    total:  { label: 'Total',  stat: 'text-slate-900 dark:text-white'               },
};

const orderedStats = computed(() => {
    const result = [];
    for (const key of statOrder) {
        if (key in props.stats) result.push([key, props.stats[key]]);
    }
    return result;
});

const priorityConfig = {
    low:      { label: 'Low',      color: 'text-slate-500 dark:text-slate-400'          },
    normal:   { label: 'Normal',   color: 'text-sky-600 dark:text-sky-400'              },
    high:     { label: 'High',     color: 'text-amber-600 dark:text-amber-400'          },
    critical: { label: 'Critical', color: 'text-rose-600 dark:text-rose-400 font-bold'  },
};

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function labelCategory(c) {
    const map = {
        general:'General', payment:'Payment', sms:'SMS', otp:'OTP', refund:'Refund',
        account:'Account', api:'API', technical:'Technical', abuse:'Abuse', other:'Other',
    };
    return map[c] ?? c;
}

function statLabel(key) {
    if (statConfig[key]) return statConfig[key].label;
    const cfg = statusConfig[key];
    if (cfg) return cfg.label;
    return key.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
}

function statColor(key) {
    if (statConfig[key]) return statConfig[key].stat;
    return statusConfig[key]?.stat ?? 'text-slate-900 dark:text-white';
}
</script>

<template>
    <Head title="Tickets — Admin" />
    <AdminLayout>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Support</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Tickets</h1>
            </div>
            <!-- Unread counter -->
            <div v-if="stats.unread > 0"
                class="flex items-center gap-2 px-3 py-2 bg-red-500/10 border border-red-500/20 rounded-xl text-[12px] font-semibold text-red-600 dark:text-red-400">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                {{ stats.unread }} unread
            </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-9 gap-2.5 mb-6">
            <div v-for="[key, count] in orderedStats" :key="key"
                @click="key !== 'total' && key !== 'unread' ? (status = key, applyFilters()) : clearFilters()"
                class="cursor-pointer rounded-xl border bg-white dark:bg-[#0d1e35] p-3 text-center transition-all hover:-translate-y-0.5 hover:shadow-md"
                :class="[
                    status === key ? 'border-sky-400 dark:border-sky-500/50 shadow-sky-500/10 shadow-md' : 'border-slate-200 dark:border-sky-500/12',
                    key === 'unread' && count > 0 ? 'ring-1 ring-red-400/50 dark:ring-red-500/40 border-red-200 dark:border-red-500/20' : '',
                ]"
            >
                <div class="flex items-center justify-center gap-1 mb-0.5">
                    <p :class="['text-xl font-black tabular-nums', statColor(key)]">{{ count }}</p>
                    <Mail v-if="key === 'unread' && count > 0" class="w-3 h-3 text-red-400 dark:text-red-500" />
                    <Zap v-else-if="['new','user_replied','escalated'].includes(key) && count > 0" class="w-3 h-3 text-orange-400 dark:text-orange-500" />
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">
                    {{ statLabel(key) }}
                </p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px]">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                <input v-model="search" type="text" placeholder="Search subject, reference, user…"
                    class="w-full h-9 pl-9 pr-4 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
            </div>
            <select v-model="status" @change="applyFilters"
                class="h-9 px-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                <option value="">All Statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ statusConfig[s]?.label ?? s }}</option>
            </select>
            <select v-model="priority" @change="applyFilters"
                class="h-9 px-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                <option value="">All Priorities</option>
                <option v-for="p in priorities" :key="p" :value="p">{{ p.charAt(0).toUpperCase() + p.slice(1) }}</option>
            </select>
            <select v-model="category" @change="applyFilters"
                class="h-9 px-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ labelCategory(c) }}</option>
            </select>
            <button v-if="hasFilters" @click="clearFilters"
                class="flex items-center gap-1.5 h-9 px-3 text-[12px] font-medium text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 rounded-xl hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <X class="w-3.5 h-3.5" /> Clear
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
            <div v-if="tickets.data.length === 0" class="py-16 flex flex-col items-center gap-3 text-center">
                <MessageSquare class="w-10 h-10 text-slate-300 dark:text-slate-700" />
                <p class="text-[13px] text-slate-400 dark:text-slate-600">No tickets found.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                            <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Ticket</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Category</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Priority</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Replies</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Updated</th>
                            <th class="text-right px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                        <tr v-for="ticket in tickets.data" :key="ticket.id"
                            :class="['transition-colors', ticket.admin_unread
                                ? 'bg-red-50/50 dark:bg-red-500/5 hover:bg-red-50/80 dark:hover:bg-red-500/8 border-l-2 border-l-red-400 dark:border-l-red-500'
                                : 'hover:bg-slate-50 dark:hover:bg-white/3']">
                            <td class="px-5 py-3.5">
                                <Link :href="route('admin.tickets.show', ticket.id)" class="group">
                                    <div class="flex items-center gap-2">
                                        <Pin v-if="ticket.pinned" class="w-3 h-3 text-amber-500 flex-shrink-0" />
                                        <!-- Unread pulsing dot -->
                                        <span v-if="ticket.admin_unread" class="relative flex h-2 w-2 flex-shrink-0">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                        </span>
                                        <p :class="['group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors line-clamp-1',
                                            ticket.admin_unread
                                                ? 'font-black text-slate-900 dark:text-white'
                                                : 'font-semibold text-slate-800 dark:text-slate-200']">
                                            {{ ticket.subject }}
                                        </p>
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-600 font-mono mt-0.5">#{{ ticket.reference }}</p>
                                </Link>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ ticket.user?.name ?? '—' }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-400">{{ ticket.user?.email ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 capitalize">{{ labelCategory(ticket.category) }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span v-if="statusConfig[ticket.status]"
                                    :class="['inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-semibold', statusConfig[ticket.status].color]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[ticket.status].dot]"></span>
                                    {{ statusConfig[ticket.status].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span v-if="priorityConfig[ticket.priority]"
                                    :class="['text-[12px] font-semibold capitalize', priorityConfig[ticket.priority].color]">
                                    {{ priorityConfig[ticket.priority].label }}
                                    <AlertTriangle v-if="ticket.priority === 'critical'" class="inline w-3 h-3 ml-0.5" />
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400">{{ ticket.replies_count }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-400">
                                    <Clock class="w-3 h-3" />
                                    {{ formatDate(ticket.last_replied_at ?? ticket.created_at) }}
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <Link :href="route('admin.tickets.show', ticket.id)"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                                    View <ChevronRight class="w-3 h-3" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100 dark:border-sky-500/8">
                <p class="text-[12px] text-slate-400 dark:text-slate-400">
                    Showing {{ tickets.from }}–{{ tickets.to }} of {{ tickets.total }}
                </p>
                <div class="flex items-center gap-1">
                    <Link v-for="link in tickets.links" :key="link.label"
                        :href="link.url ?? '#'"
                        :class="['px-2.5 py-1 text-[12px] rounded-lg transition-colors', link.active
                            ? 'bg-sky-500 text-white font-semibold'
                            : link.url ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' : 'text-slate-300 dark:text-slate-700 cursor-default pointer-events-none']"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

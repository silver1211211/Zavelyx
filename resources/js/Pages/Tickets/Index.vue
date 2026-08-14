<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Clock,
    Loader2,
    MessageSquare,
    Pin,
    Plus,
    Search,
    Send,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    tickets: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const showNewForm = ref(false);
const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');

let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 400);
});
watch(statusFilter, () => applyFilters());

function applyFilters() {
    router.get(route('tickets.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const form = useForm({
    subject:  '',
    message:  '',
    priority: 'normal',
    category: 'general',
});

function submit() {
    form.post(route('tickets.store'), {
        onSuccess: () => { form.reset(); showNewForm.value = false; },
        preserveScroll: true,
    });
}

// ── Status & display config ──────────────────────────────────────────────────

const statusConfig = {
    new:              { label: 'New',              badgeClass: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',              dot: 'bg-sky-500',      icon: null },
    in_review:        { label: 'In Review',        badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',        dot: 'bg-amber-500',    icon: null },
    waiting_for_user: { label: 'Awaiting Reply',   badgeClass: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',    dot: 'bg-violet-500',   icon: null },
    user_replied:     { label: 'Reply Sent',       badgeClass: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',    dot: 'bg-orange-500',   icon: null },
    escalated:        { label: 'Escalated',        badgeClass: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',          dot: 'bg-rose-500',     icon: null },
    resolved:         { label: 'Resolved',         badgeClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500',  icon: null },
    closed:           { label: 'Closed',           badgeClass: 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400', dot: 'bg-slate-400', icon: null },
};

const iconBgClass = {
    new:              'bg-sky-500/10',
    in_review:        'bg-amber-500/10',
    waiting_for_user: 'bg-violet-500/10',
    user_replied:     'bg-orange-500/10',
    escalated:        'bg-rose-500/10',
    resolved:         'bg-emerald-500/10',
    closed:           'bg-slate-100 dark:bg-white/5',
};

const iconColorClass = {
    new:              'text-sky-500',
    in_review:        'text-amber-500',
    waiting_for_user: 'text-violet-500',
    user_replied:     'text-orange-500',
    escalated:        'text-rose-500',
    resolved:         'text-emerald-500',
    closed:           'text-slate-400',
};

const priorityConfig = {
    low:      { label: 'Low',      class: 'text-slate-400 dark:text-slate-600' },
    normal:   { label: 'Normal',   class: 'text-sky-500'                       },
    high:     { label: 'High',     class: 'text-rose-500'                      },
    critical: { label: 'Critical', class: 'text-rose-600 font-bold'            },
};

const categoryLabels = {
    general: 'General', payment: 'Payment', sms: 'SMS', otp: 'OTP',
    refund: 'Refund', account: 'Account', api: 'API',
    technical: 'Technical', abuse: 'Abuse', other: 'Other',
};

// Statuses where user action is needed (e.g. awaiting a reply)
const awaitingUserReply = (status) => status === 'waiting_for_user';

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const tickets = computed(() => Array.isArray(props.tickets) ? props.tickets : (props.tickets?.data ?? []));
</script>

<template>
    <Head title="Support Tickets" />
    <AuthenticatedLayout>

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Support Tickets</h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">Get help from our support team.</p>
            </div>
            <button
                @click="showNewForm = !showNewForm"
                class="flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/30 transition-all"
            >
                <component :is="showNewForm ? X : Plus" class="w-4 h-4" />
                {{ showNewForm ? 'Cancel' : 'New Ticket' }}
            </button>
        </div>

        <!-- New ticket form -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showNewForm" class="mb-5 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">Create New Ticket</h2>
                <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-4">Describe your issue and our team will respond shortly.</p>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Subject</label>
                            <input v-model="form.subject" type="text" placeholder="Briefly describe your issue…"
                                :class="['w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', form.errors.subject ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                            <p v-if="form.errors.subject" class="mt-1 text-[11px] text-rose-500">{{ form.errors.subject }}</p>
                        </div>

                        <div>
                            <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Priority</label>
                            <select v-model="form.priority"
                                class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all appearance-none">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Category</label>
                            <select v-model="form.category"
                                class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all appearance-none">
                                <option v-for="(label, key) in categoryLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Message</label>
                        <textarea v-model="form.message" rows="4" placeholder="Describe your issue in detail…"
                            :class="['w-full px-3 py-2.5 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all resize-none', form.errors.message ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']"></textarea>
                        <p v-if="form.errors.message" class="mt-1 text-[11px] text-rose-500">{{ form.errors.message }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showNewForm = false" class="px-4 py-2 text-[13px] font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex items-center gap-2 px-5 py-2 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/30 transition-all">
                            <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                            <Send v-else class="w-3.5 h-3.5" />
                            {{ form.processing ? 'Submitting…' : 'Submit Ticket' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- Search + filter bar -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                <input v-model="search" type="search" placeholder="Search tickets…"
                    class="w-full h-9 pl-9 pr-4 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
            </div>
            <select v-model="statusFilter"
                class="h-9 px-3 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                <option value="">All Statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
        </div>

        <!-- Tickets list -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">

            <div v-if="tickets.length === 0" class="py-16 flex flex-col items-center gap-3 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/5 flex items-center justify-center">
                    <MessageSquare class="w-6 h-6 text-slate-400 dark:text-slate-600" />
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-slate-700 dark:text-slate-300">No tickets found</p>
                    <p class="text-[12px] text-slate-400 dark:text-slate-600 mt-0.5">
                        {{ search || statusFilter ? 'Try adjusting your search or filter.' : 'Create a ticket to get help from our support team.' }}
                    </p>
                </div>
                <button v-if="!search && !statusFilter" @click="showNewForm = true"
                    class="mt-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/30 transition-all">
                    New Ticket
                </button>
            </div>

            <div v-else class="divide-y divide-slate-100 dark:divide-sky-500/8">
                <Link v-for="ticket in tickets" :key="ticket.id"
                    :href="route('tickets.show', ticket.id)"
                    class="relative flex items-start gap-4 px-5 py-4 hover:bg-slate-50 dark:hover:bg-white/3 transition-colors cursor-pointer">

                    <!-- "Awaiting reply" pulse indicator -->
                    <span v-if="awaitingUserReply(ticket.status)" class="absolute top-4 right-5 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
                    </span>

                    <!-- Status icon -->
                    <div :class="['w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0', iconBgClass[ticket.status] ?? 'bg-slate-100 dark:bg-white/5']">
                        <Pin v-if="ticket.pinned" :class="['w-4 h-4', iconColorClass[ticket.status] ?? 'text-slate-400']" />
                        <MessageSquare v-else :class="['w-4 h-4', iconColorClass[ticket.status] ?? 'text-slate-400']" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[14px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ ticket.subject }}</p>
                            <!-- Status badge -->
                            <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0', statusConfig[ticket.status]?.badgeClass ?? 'bg-slate-100 text-slate-500']">
                                <span :class="['w-1 h-1 rounded-full', statusConfig[ticket.status]?.dot ?? 'bg-slate-400']"></span>
                                {{ statusConfig[ticket.status]?.label ?? ticket.status }}
                            </span>
                            <!-- "Awaiting your reply" callout -->
                            <span v-if="awaitingUserReply(ticket.status)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-600 dark:text-violet-400 text-[10px] font-semibold">
                                <AlertCircle class="w-3 h-3" />
                                Reply Needed
                            </span>
                        </div>

                        <p class="text-[12px] text-slate-400 dark:text-slate-600 mt-0.5 line-clamp-1">{{ ticket.message }}</p>

                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-slate-400 dark:text-slate-600 flex-wrap">
                            <span class="flex items-center gap-1">
                                <Clock class="w-3 h-3" />
                                {{ formatDate(ticket.last_replied_at ?? ticket.created_at) }}
                            </span>
                            <span class="h-3 w-px bg-slate-200 dark:bg-white/10" />
                            <span :class="priorityConfig[ticket.priority]?.class">{{ priorityConfig[ticket.priority]?.label ?? ticket.priority }} priority</span>
                            <span class="h-3 w-px bg-slate-200 dark:bg-white/10" />
                            <span class="capitalize">{{ categoryLabels[ticket.category] ?? ticket.category }}</span>
                            <template v-if="ticket.replies_count > 0">
                                <span class="h-3 w-px bg-slate-200 dark:bg-white/10" />
                                <span>{{ ticket.replies_count }} {{ ticket.replies_count === 1 ? 'reply' : 'replies' }}</span>
                            </template>
                        </div>
                    </div>

                    <div class="flex-shrink-0 text-[12px] font-mono text-slate-300 dark:text-slate-700">#{{ ticket.id }}</div>
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.tickets?.links?.length > 3" class="flex items-center justify-center gap-1 mt-5">
            <template v-for="link in props.tickets.links" :key="link.label">
                <Link v-if="link.url" :href="link.url"
                    :class="['px-3 py-1.5 rounded-lg text-[12px] font-medium transition-colors',
                        link.active ? 'bg-sky-500 text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5']"
                    v-html="link.label" />
                <span v-else :class="['px-3 py-1.5 rounded-lg text-[12px] font-medium text-slate-300 dark:text-slate-700']" v-html="link.label" />
            </template>
        </div>

    </AuthenticatedLayout>
</template>

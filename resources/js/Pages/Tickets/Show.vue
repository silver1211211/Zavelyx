<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    Image,
    Loader2,
    MessageSquare,
    Paperclip,
    RefreshCw,
    Send,
    Shield,
    Star,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    ticket: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash ?? {});

const replyForm = useForm({ message: '', attachments: [] });
const closeForm  = useForm({});
const reopenForm = useForm({});

const fileInput    = ref(null);
const selectedFiles = ref([]);

function pickFiles() { fileInput.value?.click(); }

function onFileChange(e) {
    const files = Array.from(e.target.files);
    selectedFiles.value = [...selectedFiles.value, ...files].slice(0, 5);
    replyForm.attachments = selectedFiles.value;
    e.target.value = '';
}

function removeFile(index) {
    selectedFiles.value.splice(index, 1);
    replyForm.attachments = [...selectedFiles.value];
}

function sendReply() {
    replyForm.post(route('tickets.reply', props.ticket.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { replyForm.reset(); selectedFiles.value = []; },
    });
}

function closeTicket() {
    closeForm.patch(route('tickets.close', props.ticket.id), { preserveScroll: true });
}

function reopenTicket() {
    reopenForm.patch(route('tickets.reopen', props.ticket.id), { preserveScroll: true });
}

// ── Status config ────────────────────────────────────────────────────────────

const statusConfig = {
    new:              {
        label: 'New',
        badgeClass: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
        dot: 'bg-sky-500',
        banner: { class: 'bg-sky-50 dark:bg-sky-500/8 border-sky-200 dark:border-sky-500/20 text-sky-700 dark:text-sky-300', icon: Clock, text: 'Your ticket has been received and is awaiting review by our support team.' },
    },
    in_review:        {
        label: 'In Review',
        badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
        banner: { class: 'bg-amber-50 dark:bg-amber-500/8 border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-300', icon: Eye, text: 'Our support team is reviewing your ticket and will respond shortly.' },
    },
    waiting_for_user: {
        label: 'Awaiting Your Reply',
        badgeClass: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
        dot: 'bg-violet-500',
        banner: { class: 'bg-violet-50 dark:bg-violet-500/8 border-violet-200 dark:border-violet-500/20 text-violet-700 dark:text-violet-300', icon: AlertCircle, text: 'Support has replied to your ticket. Please review and respond to continue.' },
    },
    user_replied:     {
        label: 'Reply Sent',
        badgeClass: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
        dot: 'bg-orange-500',
        banner: null,
    },
    escalated:        {
        label: 'Escalated',
        badgeClass: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        dot: 'bg-rose-500',
        banner: { class: 'bg-rose-50 dark:bg-rose-500/8 border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-300', icon: AlertCircle, text: 'This ticket has been escalated and is being handled with priority.' },
    },
    resolved:         {
        label: 'Resolved',
        badgeClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
        banner: { class: 'bg-emerald-50 dark:bg-emerald-500/8 border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-300', icon: CheckCircle2, text: 'This ticket has been resolved. If you need further assistance, reply below to reopen it.' },
    },
    closed:           {
        label: 'Closed',
        badgeClass: 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400',
        dot: 'bg-slate-400',
        banner: null,
    },
};

const priorityConfig = {
    low:      { label: 'Low',      class: 'text-slate-500 dark:text-slate-400' },
    normal:   { label: 'Normal',   class: 'text-sky-500'                       },
    high:     { label: 'High',     class: 'text-orange-500'                    },
    critical: { label: 'Critical', class: 'text-rose-500 font-bold'            },
};

const eventConfig = {
    created:          { icon: MessageSquare, colorClass: 'text-sky-500',     bgClass: 'bg-sky-500/10'     },
    viewed:           { icon: Eye,           colorClass: 'text-amber-500',   bgClass: 'bg-amber-500/10'   },
    admin_replied:    { icon: Shield,        colorClass: 'text-violet-500',  bgClass: 'bg-violet-500/10'  },
    user_replied:     { icon: Send,          colorClass: 'text-sky-500',     bgClass: 'bg-sky-500/10'     },
    status_changed:   { icon: RefreshCw,     colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10'   },
    priority_changed: { icon: Star,          colorClass: 'text-amber-500',   bgClass: 'bg-amber-500/10'   },
    category_changed: { icon: RefreshCw,     colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10'   },
    escalated:        { icon: AlertCircle,   colorClass: 'text-rose-500',    bgClass: 'bg-rose-500/10'    },
    resolved:         { icon: CheckCircle2,  colorClass: 'text-emerald-500', bgClass: 'bg-emerald-500/10' },
    reopened:         { icon: RefreshCw,     colorClass: 'text-orange-500',  bgClass: 'bg-orange-500/10'  },
    closed:           { icon: XCircle,       colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10'   },
};

// Computed state
const currentStatus = computed(() => statusConfig[props.ticket.status] ?? statusConfig['new']);
const isFullyClosed = computed(() => props.ticket.status === 'closed');
const isResolved    = computed(() => props.ticket.status === 'resolved');
const canReply      = computed(() => !isFullyClosed.value);
const showBanner    = computed(() => currentStatus.value.banner != null);

// Public replies only (exclude internal notes)
const publicReplies = computed(() => (props.ticket.replies ?? []).filter(r => !r.is_internal));

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit',
    });
}

function formatRelative(str) {
    if (!str) return '';
    const d = new Date(str);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return formatDate(str);
}

function fileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

const categoryLabel = {
    general: 'General', payment: 'Payment', sms: 'SMS', otp: 'OTP',
    refund: 'Refund', account: 'Account', api: 'API',
    technical: 'Technical', abuse: 'Abuse', other: 'Other',
};
</script>

<template>
    <Head :title="`Ticket #${ticket.id}`" />
    <AuthenticatedLayout>

        <!-- Back + header -->
        <div class="mb-5">
            <Link :href="route('tickets.index')" class="inline-flex items-center gap-1.5 text-[12px] text-slate-400 dark:text-slate-600 hover:text-sky-500 dark:hover:text-sky-400 transition-colors mb-3">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to tickets
            </Link>

            <div class="flex items-start gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ ticket.subject }}</h1>
                    <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                        <!-- Status badge -->
                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold', currentStatus.badgeClass]">
                            <span :class="['w-1.5 h-1.5 rounded-full', currentStatus.dot]"></span>
                            {{ currentStatus.label }}
                        </span>
                        <span :class="['text-[12px] font-semibold', priorityConfig[ticket.priority]?.class]">
                            {{ priorityConfig[ticket.priority]?.label ?? ticket.priority }} priority
                        </span>
                        <span class="text-[12px] text-slate-400 dark:text-slate-600 capitalize">{{ categoryLabel[ticket.category] ?? ticket.category }}</span>
                        <span class="text-[12px] text-slate-400 dark:text-slate-600">Opened {{ formatDate(ticket.created_at) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="isFullyClosed"
                        @click="reopenTicket"
                        :disabled="reopenForm.processing"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 rounded-xl hover:border-sky-300 hover:text-sky-500 dark:hover:border-sky-500/30 dark:hover:text-sky-400 transition-all"
                    >
                        <RefreshCw class="w-3.5 h-3.5" /> Reopen
                    </button>
                    <button
                        v-else-if="!isResolved"
                        @click="closeTicket"
                        :disabled="closeForm.processing"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10 rounded-xl hover:border-rose-300 hover:text-rose-500 dark:hover:border-rose-500/30 dark:hover:text-rose-400 transition-all"
                    >
                        <XCircle class="w-3.5 h-3.5" /> Close Ticket
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-4 flex items-center gap-2 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl text-[13px] text-emerald-700 dark:text-emerald-400">
            <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
            {{ flash.success }}
        </div>

        <!-- Status banner -->
        <div v-if="showBanner" :class="['mb-4 flex items-start gap-3 px-4 py-3.5 rounded-xl border text-[13px]', currentStatus.banner.class]">
            <component :is="currentStatus.banner.icon" class="w-4 h-4 flex-shrink-0 mt-0.5" />
            <p>{{ currentStatus.banner.text }}</p>
        </div>

        <!-- Conversation thread -->
        <div class="space-y-4 mb-5">

            <!-- Original message -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 dark:border-sky-500/8">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-blue-500 flex items-center justify-center text-white text-[12px] font-bold flex-shrink-0">
                        {{ ticket.user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ ticket.user?.name ?? 'You' }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ formatDate(ticket.created_at) }}</p>
                    </div>
                </div>
                <div class="p-5">
                    <p class="text-[13px] text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ ticket.message }}</p>

                    <!-- Original attachments -->
                    <div v-if="ticket.attachments?.filter(a => !a.ticket_reply_id).length" class="mt-4 flex flex-wrap gap-2">
                        <a v-for="att in ticket.attachments.filter(a => !a.ticket_reply_id)" :key="att.id"
                            :href="att.url" target="_blank"
                            class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg text-[12px] text-slate-600 dark:text-slate-400 hover:border-sky-300 hover:text-sky-600 transition-all">
                            <Image v-if="att.is_image" class="w-3.5 h-3.5 text-sky-500" />
                            <Paperclip v-else class="w-3.5 h-3.5" />
                            <span class="truncate max-w-[160px]">{{ att.original_name }}</span>
                            <span class="text-slate-400 dark:text-slate-600">{{ att.human_size }}</span>
                            <Download class="w-3 h-3 flex-shrink-0" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Replies (public only, no internal notes) -->
            <div
                v-for="reply in publicReplies"
                :key="reply.id"
                :class="[
                    'rounded-2xl border overflow-hidden',
                    reply.is_staff
                        ? 'bg-sky-50 dark:bg-sky-500/8 border-sky-200 dark:border-sky-500/20'
                        : 'bg-white dark:bg-[#0d1e35] border-slate-200 dark:border-sky-500/12',
                ]"
            >
                <div class="flex items-center gap-3 px-5 py-3.5 border-b"
                    :class="reply.is_staff ? 'border-sky-100 dark:border-sky-500/15' : 'border-slate-100 dark:border-sky-500/8'">
                    <div :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center text-white text-[12px] font-bold flex-shrink-0',
                        reply.is_staff ? 'bg-gradient-to-br from-violet-500 to-purple-600' : 'bg-gradient-to-br from-sky-400 to-blue-500',
                    ]">
                        <Shield v-if="reply.is_staff" class="w-4 h-4" />
                        <span v-else>{{ reply.user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">
                                {{ reply.is_staff ? 'Support Team' : (reply.user?.name ?? 'You') }}
                            </p>
                            <span v-if="reply.is_staff" class="px-1.5 py-0.5 bg-violet-500/10 text-violet-600 dark:text-violet-400 text-[9px] font-bold uppercase tracking-wider rounded">
                                Staff
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ formatDate(reply.created_at) }}</p>
                    </div>
                </div>
                <div class="p-5">
                    <p class="text-[13px] text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ reply.message }}</p>

                    <!-- Reply attachments -->
                    <div v-if="reply.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                        <a v-for="att in reply.attachments" :key="att.id"
                            :href="att.url" target="_blank"
                            class="flex items-center gap-2 px-3 py-1.5 bg-white/60 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg text-[12px] text-slate-600 dark:text-slate-400 hover:border-sky-300 hover:text-sky-600 transition-all">
                            <Image v-if="att.is_image" class="w-3.5 h-3.5 text-sky-500" />
                            <Paperclip v-else class="w-3.5 h-3.5" />
                            <span class="truncate max-w-[160px]">{{ att.original_name }}</span>
                            <span class="text-slate-400">{{ att.human_size }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reply form (allowed even for resolved — replying reopens it) -->
        <div v-if="canReply" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 mb-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[13px] font-bold text-slate-800 dark:text-slate-200">Add Reply</h3>
                <span v-if="isResolved" class="text-[11px] text-amber-600 dark:text-amber-400 flex items-center gap-1">
                    <AlertCircle class="w-3 h-3" />
                    Replying will reopen this ticket
                </span>
            </div>
            <form @submit.prevent="sendReply" class="space-y-3">
                <textarea
                    v-model="replyForm.message"
                    rows="4"
                    placeholder="Type your reply…"
                    :class="['w-full px-3 py-2.5 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all resize-none', replyForm.errors.message ? 'border-rose-400' : 'border-slate-200 dark:border-white/10']"
                ></textarea>
                <p v-if="replyForm.errors.message" class="text-[11px] text-rose-500">{{ replyForm.errors.message }}</p>

                <!-- File attachments -->
                <div>
                    <input ref="fileInput" type="file" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.zip" class="hidden" @change="onFileChange" />
                    <div v-if="selectedFiles.length" class="flex flex-wrap gap-2 mb-2">
                        <div v-for="(file, i) in selectedFiles" :key="i"
                            class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg text-[12px] text-slate-600 dark:text-slate-400">
                            <Paperclip class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" />
                            <span class="truncate max-w-[140px]">{{ file.name }}</span>
                            <span class="text-slate-400 dark:text-slate-600">{{ fileSize(file.size) }}</span>
                            <button type="button" @click="removeFile(i)" class="text-slate-400 hover:text-rose-500 transition-colors">
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                    <button type="button" @click="pickFiles" :disabled="selectedFiles.length >= 5"
                        class="flex items-center gap-1.5 text-[12px] text-slate-400 dark:text-slate-600 hover:text-sky-500 dark:hover:text-sky-400 transition-colors disabled:opacity-40">
                        <Paperclip class="w-3.5 h-3.5" />
                        Attach files (up to 5 · max 5MB each)
                    </button>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="replyForm.processing || !replyForm.message.trim()"
                        class="flex items-center gap-2 px-5 py-2.5 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/30 transition-all">
                        <Loader2 v-if="replyForm.processing" class="w-3.5 h-3.5 animate-spin" />
                        <Send v-else class="w-3.5 h-3.5" />
                        {{ replyForm.processing ? 'Sending…' : (isResolved ? 'Reply & Reopen' : 'Send Reply') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Fully closed notice -->
        <div v-if="isFullyClosed" class="mb-5 flex items-center gap-3 px-5 py-4 bg-slate-50 dark:bg-white/3 border border-slate-200 dark:border-white/8 rounded-2xl">
            <XCircle class="w-5 h-5 text-slate-400 dark:text-slate-600 flex-shrink-0" />
            <div class="flex-1">
                <p class="text-[13px] text-slate-600 dark:text-slate-400">This ticket is <strong>closed</strong>.</p>
                <p class="text-[12px] text-slate-400 dark:text-slate-600 mt-0.5">
                    <button @click="reopenTicket" :disabled="reopenForm.processing" class="text-sky-500 hover:underline font-medium">Reopen it</button>
                    if you need further assistance.
                </p>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div v-if="ticket.events?.length" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
            <h3 class="text-[12px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-4">Activity</h3>
            <div class="space-y-0">
                <div v-for="(event, idx) in ticket.events" :key="event.id" class="flex gap-3">
                    <!-- Timeline line -->
                    <div class="flex flex-col items-center">
                        <div :class="['w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0', eventConfig[event.type]?.bgClass ?? 'bg-slate-500/10']">
                            <component :is="eventConfig[event.type]?.icon ?? MessageSquare" :class="['w-3 h-3', eventConfig[event.type]?.colorClass ?? 'text-slate-500']" />
                        </div>
                        <div v-if="idx < ticket.events.length - 1" class="w-px flex-1 bg-slate-100 dark:bg-white/8 my-1.5"></div>
                    </div>
                    <!-- Event content -->
                    <div class="pb-4 flex-1 min-w-0">
                        <p class="text-[12px] text-slate-700 dark:text-slate-300">{{ event.description }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5">{{ formatRelative(event.created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

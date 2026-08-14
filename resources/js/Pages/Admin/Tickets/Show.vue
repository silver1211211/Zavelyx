<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    CheckCircle,
    CheckCircle2,
    ChevronRight,
    Clock,
    Download,
    Eye,
    Image,
    Lock,
    Mail,
    MailOpen,
    MessageSquare,
    Paperclip,
    Pin,
    PinOff,
    RefreshCw,
    Send,
    Shield,
    Trash2,
    User,
    X,
    XCircle,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    ticket:     Object,
    statuses:   Array,
    priorities: Array,
    categories: Array,
});

const flash = computed(() => usePage().props.flash ?? {});

const replyForm    = useForm({ message: '', is_internal: false, attachments: [] });
const statusForm   = useForm({ status: props.ticket.status });
const priorityForm = useForm({ priority: props.ticket.priority });
const categoryForm = useForm({ category: props.ticket.category ?? 'general' });
const pinForm      = useForm({});
const readForm     = useForm({});
const closeForm    = useForm({ status: 'closed' });

const selectedFiles = ref([]);
const fileInput     = ref(null);
const showTimeline  = ref(true);

function pickFiles() { fileInput.value?.click(); }

function onFilesChange(e) {
    const files = Array.from(e.target.files ?? []);
    selectedFiles.value = [...selectedFiles.value, ...files].slice(0, 5);
    replyForm.attachments = selectedFiles.value;
    e.target.value = '';
}

function removeFile(i) {
    selectedFiles.value.splice(i, 1);
    replyForm.attachments = [...selectedFiles.value];
}

function sendReply() {
    replyForm.post(route('admin.tickets.reply', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => { replyForm.reset(); replyForm.is_internal = false; selectedFiles.value = []; },
    });
}

function changeStatus() {
    statusForm.patch(route('admin.tickets.status', props.ticket.id));
}

function changePriority() {
    priorityForm.patch(route('admin.tickets.priority', props.ticket.id));
}

function changeCategory() {
    categoryForm.patch(route('admin.tickets.category', props.ticket.id));
}

function togglePin() {
    pinForm.patch(route('admin.tickets.pin', props.ticket.id));
}

function markAsRead() {
    readForm.patch(route('admin.tickets.read', props.ticket.id));
}

function closeTicket() {
    if (!confirm('Close this ticket? The user will be notified and can still reopen it by replying.')) return;
    closeForm.patch(route('admin.tickets.status', props.ticket.id));
}

function deleteTicket() {
    if (!confirm('Delete this ticket and all replies permanently? This cannot be undone.')) return;
    useForm({}).delete(route('admin.tickets.destroy', props.ticket.id));
}

// ── Status & display config ──────────────────────────────────────────────────

const statusConfig = {
    new: {
        label: 'Pending',
        color: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
        banner: { class: 'bg-sky-50 dark:bg-sky-500/8 border-sky-200 dark:border-sky-500/20 text-sky-700 dark:text-sky-300', icon: MessageSquare, text: 'New ticket — not yet reviewed. Opening this page has automatically set it to In Review.' },
    },
    in_review: {
        label: 'In Review',
        color: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        banner: null,
    },
    waiting_for_user: {
        label: 'Waiting For User',
        color: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
        banner: { class: 'bg-violet-50 dark:bg-violet-500/8 border-violet-200 dark:border-violet-500/20 text-violet-700 dark:text-violet-300', icon: Clock, text: 'Awaiting user reply — your last response is awaiting their response.' },
    },
    user_replied: {
        label: 'Pending',
        color: 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
        banner: { class: 'bg-orange-50 dark:bg-orange-500/8 border-orange-200 dark:border-orange-500/20 text-orange-700 dark:text-orange-300', icon: AlertCircle, text: 'User has replied and is waiting for your response. Opening this page has set it to In Review.' },
    },
    escalated: {
        label: 'Escalated',
        color: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
        banner: { class: 'bg-rose-50 dark:bg-rose-500/8 border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-300', icon: Zap, text: 'This ticket has been escalated. Handle with high priority.' },
    },
    resolved: {
        label: 'Resolved',
        color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        banner: null,
    },
    closed: {
        label: 'Closed',
        color: 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-500',
        banner: null,
    },
};

const eventConfig = {
    created:          { icon: MessageSquare, colorClass: 'text-sky-500',     bgClass: 'bg-sky-500/10',     label: 'Created'          },
    viewed:           { icon: Eye,           colorClass: 'text-amber-500',   bgClass: 'bg-amber-500/10',   label: 'Viewed'           },
    admin_replied:    { icon: Shield,        colorClass: 'text-violet-500',  bgClass: 'bg-violet-500/10',  label: 'Staff Reply'       },
    user_replied:     { icon: MessageSquare, colorClass: 'text-sky-500',     bgClass: 'bg-sky-500/10',     label: 'User Reply'        },
    status_changed:   { icon: RefreshCw,     colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10',   label: 'Status Change'     },
    priority_changed: { icon: AlertTriangle, colorClass: 'text-amber-500',   bgClass: 'bg-amber-500/10',   label: 'Priority Change'   },
    category_changed: { icon: RefreshCw,     colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10',   label: 'Category Change'   },
    escalated:        { icon: Zap,           colorClass: 'text-rose-500',    bgClass: 'bg-rose-500/10',    label: 'Escalated'         },
    resolved:         { icon: CheckCircle2,  colorClass: 'text-emerald-500', bgClass: 'bg-emerald-500/10', label: 'Resolved'          },
    reopened:         { icon: RefreshCw,     colorClass: 'text-orange-500',  bgClass: 'bg-orange-500/10',  label: 'Reopened'          },
    closed:           { icon: XCircle,       colorClass: 'text-slate-500',   bgClass: 'bg-slate-500/10',   label: 'Closed'            },
    pinned:           { icon: Pin,           colorClass: 'text-amber-500',   bgClass: 'bg-amber-500/10',   label: 'Pinned'            },
    internal_note:    { icon: Lock,          colorClass: 'text-slate-400',   bgClass: 'bg-slate-500/10',   label: 'Internal Note'     },
    assigned:         { icon: User,          colorClass: 'text-sky-500',     bgClass: 'bg-sky-500/10',     label: 'Assigned'          },
};

const currentStatus = computed(() => statusConfig[props.ticket.status] ?? statusConfig['in_review']);
// Show banner from the state BEFORE the auto-transition (the server already changed it)
// We show the in_review banner to indicate admin just opened it
const bannerToShow = computed(() => currentStatus.value.banner);

const priorityConfig = {
    low:      { label: 'Low',      class: 'text-slate-500 dark:text-slate-400'           },
    normal:   { label: 'Normal',   class: 'text-sky-600 dark:text-sky-400'               },
    high:     { label: 'High',     class: 'text-amber-600 dark:text-amber-400'           },
    critical: { label: 'Critical', class: 'text-rose-600 dark:text-rose-400 font-bold'   },
};

// Timeline: all events + public replies interleaved, sorted by created_at
const publicReplies = computed(() => (props.ticket.replies ?? []).filter(r => !r.is_internal));
const internalNotes = computed(() => (props.ticket.replies ?? []).filter(r => r.is_internal));

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatRelative(str) {
    if (!str) return '';
    const d = new Date(str);
    const diff = Math.floor((Date.now() - d) / 1000);
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return formatDate(str);
}

function labelCategory(c) {
    const map = { general:'General', payment:'Payment', sms:'SMS', otp:'OTP', refund:'Refund', account:'Account', api:'API', technical:'Technical', abuse:'Abuse', other:'Other' };
    return map[c] ?? c;
}
</script>

<template>
    <Head :title="`Ticket #${ticket.reference} — Admin`" />
    <AdminLayout>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[13px] font-medium">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />{{ flash.error }}
        </div>

        <!-- Breadcrumb + back -->
        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-5">
            <Link :href="route('admin.tickets.index')" class="hover:text-sky-500 transition-colors flex items-center gap-1">
                <ArrowLeft class="w-3 h-3" /> Tickets
            </Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-mono text-[11px]">#{{ ticket.reference }}</span>
        </div>

        <!-- Unread banner -->
        <div v-if="ticket.admin_unread"
            class="mb-4 flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-red-200 dark:border-red-500/25 bg-red-50 dark:bg-red-500/8 text-red-700 dark:text-red-300 text-[13px]">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2 flex-shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <Mail class="w-4 h-4 flex-shrink-0" />
                <span class="font-semibold">Unread</span>
                <span class="text-red-600/70 dark:text-red-400/70">— this ticket has new activity from the user.</span>
            </div>
            <button @click="markAsRead" :disabled="readForm.processing"
                class="flex items-center gap-1.5 px-3 py-1.5 text-[12px] font-semibold rounded-lg bg-red-100 dark:bg-red-500/15 hover:bg-red-200 dark:hover:bg-red-500/25 border border-red-200 dark:border-red-500/25 text-red-700 dark:text-red-300 transition-colors flex-shrink-0 disabled:opacity-50">
                <MailOpen class="w-3.5 h-3.5" />
                Mark as Read
            </button>
        </div>

        <!-- Contextual banner (shown when ticket needs attention) -->
        <div v-if="bannerToShow" :class="['mb-4 flex items-start gap-3 px-4 py-3.5 rounded-xl border text-[13px]', bannerToShow.class]">
            <component :is="bannerToShow.icon" class="w-4 h-4 flex-shrink-0 mt-0.5" />
            <p>{{ bannerToShow.text }}</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-6">

            <!-- ── Main conversation column ──────────────────────────────── -->
            <div class="space-y-4 min-w-0">

                <!-- Ticket header card -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <Pin v-if="ticket.pinned" class="w-4 h-4 text-amber-500 flex-shrink-0" />
                            <h1 class="text-[17px] font-black text-slate-900 dark:text-white leading-snug truncate">{{ ticket.subject }}</h1>
                        </div>
                        <span :class="['flex-shrink-0 px-2.5 py-1 rounded-lg text-[11px] font-bold', currentStatus.color]">
                            {{ currentStatus.label }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-slate-400 dark:text-slate-400">
                        <span class="font-mono">#{{ ticket.reference }}</span>
                        <span>·</span>
                        <span>{{ formatDate(ticket.created_at) }}</span>
                        <span>·</span>
                        <span :class="['capitalize font-medium', priorityConfig[ticket.priority]?.class]">{{ ticket.priority }} priority</span>
                        <span>·</span>
                        <span>{{ labelCategory(ticket.category) }}</span>
                        <span v-if="ticket.first_response_at">·</span>
                        <span v-if="ticket.first_response_at" class="text-emerald-500">First response {{ formatRelative(ticket.first_response_at) }}</span>
                    </div>
                </div>

                <!-- Original message -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                            {{ ticket.user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ ticket.user?.name ?? 'User' }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400">{{ ticket.user?.email }}</p>
                        </div>
                        <span class="text-[11px] text-slate-400 dark:text-slate-400 flex-shrink-0">{{ formatDate(ticket.created_at) }}</span>
                    </div>
                    <div class="p-5">
                        <p class="text-[13px] text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ ticket.message }}</p>
                        <div v-if="ticket.attachments?.filter(a => !a.ticket_reply_id).length" class="mt-4 pt-4 border-t border-slate-100 dark:border-white/8">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Attachments</p>
                            <div class="flex flex-wrap gap-2">
                                <template v-for="att in ticket.attachments.filter(a => !a.ticket_reply_id)" :key="att.id">
                                    <a v-if="att.is_image" :href="att.url" target="_blank"
                                        class="block w-20 h-20 rounded-lg overflow-hidden border border-slate-200 dark:border-white/10 hover:opacity-80 transition-opacity">
                                        <img :src="att.url" :alt="att.original_name" class="w-full h-full object-cover" />
                                    </a>
                                    <a v-else :href="att.url" download
                                        class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                                        <Download class="w-3.5 h-3.5 text-slate-400" />
                                        <span class="text-[12px] text-slate-700 dark:text-slate-300 max-w-[120px] truncate">{{ att.original_name }}</span>
                                        <span class="text-[11px] text-slate-400">{{ att.human_size }}</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Public replies -->
                <div v-for="reply in publicReplies" :key="reply.id"
                    :class="['rounded-2xl border overflow-hidden', reply.is_staff
                        ? 'bg-sky-50 dark:bg-sky-500/8 border-sky-200 dark:border-sky-500/20'
                        : 'bg-white dark:bg-[#0d1e35] border-slate-200 dark:border-sky-500/12']">
                    <div class="flex items-center gap-3 px-5 py-3.5 border-b"
                        :class="reply.is_staff ? 'border-sky-100 dark:border-sky-500/15' : 'border-slate-100 dark:border-sky-500/8'">
                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0 bg-gradient-to-br',
                            reply.is_staff ? 'from-violet-500 to-purple-600' : 'from-sky-500 to-blue-600']">
                            {{ reply.is_staff ? 'S' : (reply.user?.name?.charAt(0)?.toUpperCase() ?? 'U') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold" :class="reply.is_staff ? 'text-sky-800 dark:text-sky-300' : 'text-slate-800 dark:text-slate-200'">
                                {{ reply.is_staff ? 'Support Team' : (reply.user?.name ?? 'User') }}
                                <span v-if="reply.is_staff" class="ml-2 text-[10px] font-bold px-1.5 py-0.5 bg-sky-500/15 text-sky-600 dark:text-sky-400 rounded-md">STAFF</span>
                            </p>
                        </div>
                        <span class="text-[11px] text-slate-400 dark:text-slate-400 flex-shrink-0">{{ formatDate(reply.created_at) }}</span>
                    </div>
                    <div class="p-5">
                        <p class="text-[13px] leading-relaxed whitespace-pre-wrap"
                            :class="reply.is_staff ? 'text-sky-800 dark:text-sky-200' : 'text-slate-700 dark:text-slate-300'">{{ reply.message }}</p>
                        <div v-if="reply.attachments?.length" class="mt-4 pt-4 border-t"
                            :class="reply.is_staff ? 'border-sky-100 dark:border-sky-500/15' : 'border-slate-100 dark:border-white/8'">
                            <div class="flex flex-wrap gap-2">
                                <template v-for="att in reply.attachments" :key="att.id">
                                    <a v-if="att.is_image" :href="att.url" target="_blank"
                                        class="block w-16 h-16 rounded-lg overflow-hidden border border-slate-200 dark:border-white/10 hover:opacity-80 transition-opacity">
                                        <img :src="att.url" :alt="att.original_name" class="w-full h-full object-cover" />
                                    </a>
                                    <a v-else :href="att.url" download
                                        class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/50 dark:bg-white/5 border border-slate-200 dark:border-white/10 hover:bg-white dark:hover:bg-white/10 transition-colors">
                                        <Download class="w-3.5 h-3.5 text-slate-400" />
                                        <span class="text-[12px] text-slate-700 dark:text-slate-300 max-w-[120px] truncate">{{ att.original_name }}</span>
                                        <span class="text-[11px] text-slate-400">{{ att.human_size }}</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Internal notes (visible to admin only) -->
                <div v-if="internalNotes.length" class="space-y-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 flex items-center gap-2">
                        <Lock class="w-3 h-3" />Internal notes (not visible to user)
                    </p>
                    <div v-for="note in internalNotes" :key="note.id"
                        class="rounded-2xl border border-dashed border-slate-300 dark:border-white/15 bg-slate-50/50 dark:bg-white/2 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-dashed border-slate-200 dark:border-white/10">
                            <Lock class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                            <p class="text-[12px] font-semibold text-slate-500 dark:text-slate-400 flex-1">Support Team <span class="font-normal text-slate-400">· Internal note</span></p>
                            <span class="text-[11px] text-slate-400 dark:text-slate-400">{{ formatDate(note.created_at) }}</span>
                        </div>
                        <div class="p-5">
                            <p class="text-[13px] text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap italic">{{ note.message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Reply form -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[13px] font-bold text-slate-900 dark:text-white">
                            {{ replyForm.is_internal ? 'Add Internal Note' : 'Reply as Support' }}
                        </h3>
                        <!-- Toggle: public reply ↔ internal note -->
                        <button type="button" @click="replyForm.is_internal = !replyForm.is_internal"
                            :class="['flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition-all',
                                replyForm.is_internal
                                    ? 'bg-slate-100 dark:bg-white/8 border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400'
                                    : 'bg-amber-500/10 border-amber-500/30 text-amber-600 dark:text-amber-400']">
                            <Lock class="w-3 h-3" />
                            {{ replyForm.is_internal ? 'Switch to Public Reply' : 'Make Internal Note' }}
                        </button>
                    </div>

                    <!-- Internal note notice -->
                    <div v-if="replyForm.is_internal" class="mb-3 flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-500/8 border border-amber-200 dark:border-amber-500/20 rounded-xl text-[12px] text-amber-700 dark:text-amber-400">
                        <Lock class="w-3.5 h-3.5 flex-shrink-0" />
                        Internal notes are only visible to support staff — not sent to the user.
                    </div>

                    <textarea
                        v-model="replyForm.message"
                        rows="5"
                        :placeholder="replyForm.is_internal ? 'Add a private note for your team…' : 'Type your reply here…'"
                        class="w-full px-4 py-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 resize-none"
                        :class="[replyForm.errors.message ? 'border-rose-400' : '']"
                    />
                    <p v-if="replyForm.errors.message" class="mt-1 text-[11px] text-rose-500">{{ replyForm.errors.message }}</p>

                    <!-- File attachments -->
                    <div class="mt-3">
                        <input ref="fileInput" type="file" multiple accept="image/*,.pdf,.txt,.zip" class="hidden" @change="onFilesChange" />
                        <div v-if="selectedFiles.length" class="flex flex-wrap gap-2 mb-2">
                            <div v-for="(f, i) in selectedFiles" :key="i"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10">
                                <Image v-if="f.type.startsWith('image/')" class="w-3 h-3 text-sky-500" />
                                <Paperclip v-else class="w-3 h-3 text-slate-400" />
                                <span class="text-[11px] text-slate-700 dark:text-slate-300 max-w-[100px] truncate">{{ f.name }}</span>
                                <button type="button" @click="removeFile(i)" class="text-slate-400 hover:text-rose-500 transition-colors ml-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                        <button type="button" v-if="selectedFiles.length < 5" @click="pickFiles"
                            class="flex items-center gap-1.5 text-[11px] text-slate-400 hover:text-sky-500 dark:hover:text-sky-400 transition-colors">
                            <Paperclip class="w-3.5 h-3.5" />
                            Attach files (max 5, 5MB each)
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <p class="text-[11px] text-slate-400">
                            <span v-if="replyForm.is_internal">
                                <Lock class="inline w-3 h-3 mb-0.5 mr-0.5" />Internal — not visible to user.
                            </span>
                            <span v-else>Visible to user immediately. Status → <strong>Waiting For User</strong>.</span>
                        </p>
                        <button @click="sendReply" :disabled="replyForm.processing || !replyForm.message.trim()"
                            :class="['flex items-center gap-2 px-4 py-2 rounded-xl text-white text-[13px] font-bold shadow-lg transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed',
                                replyForm.is_internal
                                    ? 'bg-slate-600 hover:bg-slate-700 shadow-slate-500/20'
                                    : 'bg-gradient-to-r from-sky-500 to-blue-500 hover:from-sky-600 hover:to-blue-600 shadow-sky-500/25 hover:-translate-y-px']">
                            <Lock v-if="replyForm.is_internal" class="w-3.5 h-3.5" />
                            <Send v-else class="w-3.5 h-3.5" />
                            {{ replyForm.processing ? 'Sending…' : (replyForm.is_internal ? 'Save Note' : 'Send Reply') }}
                        </button>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div v-if="ticket.events?.length" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <button @click="showTimeline = !showTimeline" class="flex items-center justify-between w-full mb-0 group">
                        <h3 class="text-[12px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 group-hover:text-slate-600 dark:group-hover:text-slate-400 transition-colors">
                            Activity Timeline ({{ ticket.events.length }})
                        </h3>
                        <ChevronRight :class="['w-3.5 h-3.5 text-slate-400 transition-transform', showTimeline ? 'rotate-90' : '']" />
                    </button>

                    <div v-if="showTimeline" class="mt-4 space-y-0">
                        <div v-for="(event, idx) in ticket.events" :key="event.id" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div :class="['w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0', eventConfig[event.type]?.bgClass ?? 'bg-slate-500/10']">
                                    <component :is="eventConfig[event.type]?.icon ?? MessageSquare" :class="['w-3 h-3', eventConfig[event.type]?.colorClass ?? 'text-slate-500']" />
                                </div>
                                <div v-if="idx < ticket.events.length - 1" class="w-px flex-1 bg-slate-100 dark:bg-white/8 my-1.5"></div>
                            </div>
                            <div class="pb-3.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/8 text-slate-500 dark:text-slate-400">
                                        {{ eventConfig[event.type]?.label ?? event.type }}
                                    </span>
                                    <span v-if="event.actor_name" class="text-[11px] text-slate-500 dark:text-slate-400">by {{ event.actor_name }}</span>
                                </div>
                                <p class="text-[12px] text-slate-600 dark:text-slate-400 mt-0.5">{{ event.description }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-0.5">{{ formatRelative(event.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Sidebar ────────────────────────────────────────────────── -->
            <div class="space-y-4">

                <!-- User info -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-3">Submitted by</p>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-[13px] font-bold flex-shrink-0">
                            {{ ticket.user?.name?.charAt(0)?.toUpperCase() ?? 'U' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ ticket.user?.name }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 truncate">{{ ticket.user?.email }}</p>
                        </div>
                    </div>
                    <Link v-if="ticket.user?.id" :href="route('admin.users.show', ticket.user.id)"
                        class="flex items-center justify-center gap-1.5 w-full py-1.5 text-[12px] font-semibold text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-500/10 transition-colors">
                        <User class="w-3.5 h-3.5" /> View Profile
                    </Link>
                </div>

                <!-- Status -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Status</p>
                    <select v-model="statusForm.status" @change="changeStatus"
                        class="w-full h-9 px-3 text-[13px] font-semibold bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                        <option v-for="s in statuses" :key="s" :value="s">
                            {{ statusConfig[s]?.label ?? s }}
                        </option>
                    </select>
                </div>

                <!-- Priority -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Priority</p>
                    <select v-model="priorityForm.priority" @change="changePriority"
                        class="w-full h-9 px-3 text-[13px] font-semibold bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                        <option v-for="p in priorities" :key="p" :value="p">{{ p.charAt(0).toUpperCase() + p.slice(1) }}</option>
                    </select>
                </div>

                <!-- Category -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Category</p>
                    <select v-model="categoryForm.category" @change="changeCategory"
                        class="w-full h-9 px-3 text-[13px] font-semibold bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                        <option v-for="c in categories" :key="c" :value="c">{{ c.charAt(0).toUpperCase() + c.slice(1) }}</option>
                    </select>
                </div>

                <!-- Metadata -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4 space-y-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-1">Details</p>
                    <div class="flex justify-between text-[12px]">
                        <span class="text-slate-500">Opened</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ formatDate(ticket.created_at) }}</span>
                    </div>
                    <div class="flex justify-between text-[12px]">
                        <span class="text-slate-500">First Reply</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ ticket.first_response_at ? formatDate(ticket.first_response_at) : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-[12px]">
                        <span class="text-slate-500">Last Activity</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ ticket.last_replied_at ? formatDate(ticket.last_replied_at) : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-[12px]">
                        <span class="text-slate-500">Replies</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ ticket.replies?.length ?? 0 }}</span>
                    </div>
                    <div v-if="ticket.resolved_at" class="flex justify-between text-[12px]">
                        <span class="text-slate-500">Resolved</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ formatDate(ticket.resolved_at) }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="space-y-2">
                    <!-- Mark as Read -->
                    <button v-if="ticket.admin_unread" @click="markAsRead" :disabled="readForm.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-red-200 dark:border-red-500/25 text-red-600 dark:text-red-400 text-[12px] font-semibold hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors disabled:opacity-50">
                        <MailOpen class="w-3.5 h-3.5" /> Mark as Read
                    </button>

                    <!-- Pin / Unpin -->
                    <button @click="togglePin" :disabled="pinForm.processing"
                        :class="['w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border text-[12px] font-semibold transition-colors',
                            ticket.pinned
                                ? 'border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10'
                                : 'border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">
                        <component :is="ticket.pinned ? PinOff : Pin" class="w-3.5 h-3.5" />
                        {{ ticket.pinned ? 'Unpin Ticket' : 'Pin Ticket' }}
                    </button>

                    <!-- Close Ticket -->
                    <button v-if="ticket.status !== 'closed'" @click="closeTicket" :disabled="closeForm.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-slate-300 dark:border-white/15 text-slate-600 dark:text-slate-300 text-[12px] font-semibold hover:bg-slate-100 dark:hover:bg-white/8 transition-colors disabled:opacity-50">
                        <XCircle class="w-3.5 h-3.5" /> Close Ticket
                    </button>

                    <!-- Delete -->
                    <button @click="deleteTicket"
                        class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-rose-200 dark:border-rose-500/20 text-rose-500 dark:text-rose-400 text-[12px] font-semibold hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                        <Trash2 class="w-3.5 h-3.5" /> Delete Ticket
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

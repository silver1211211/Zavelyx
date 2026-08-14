<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { setThemeInstant } from '@/utils/theme';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Activity, Award, BarChart3, Bell, Camera, Check, CheckCircle,
    ChevronRight, Clock, Code2, Copy, CreditCard, Eye, EyeOff,
    Globe, Key, Loader2, Lock, LogOut, Monitor, Moon, Palette,
    Pencil, Phone, RefreshCw, Settings2, Shield, ShieldCheck,
    Smartphone, Star, Sun, Trash2, TrendingUp, User, Users, X, Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    user:          { type: Object, default: () => ({}) },
    stats:         { type: Object, default: () => ({}) },
    loginSessions: { type: Array,  default: () => [] },
    hasApiKey:     { type: Boolean, default: false },
    apiKeyCreatedAt: { type: String, default: null },
    preferences:   { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('profile');
const tabs = [
    { key: 'profile',       label: 'Profile',       icon: User },
    { key: 'stats',         label: 'Statistics',    icon: BarChart3 },
    { key: 'security',      label: 'Security',      icon: Shield },
    { key: 'sessions',      label: 'Sessions',      icon: Monitor },
    { key: 'notifications', label: 'Notifications', icon: Bell },
    { key: 'appearance',    label: 'Appearance',    icon: Palette },
    { key: 'api',           label: 'API',           icon: Code2 },
];

// ── Profile form ──────────────────────────────────────────────────────────────
const profileForm = useForm({
    name:     props.user.name     ?? '',
    email:    props.user.email    ?? '',
    phone:    props.user.phone    ?? '',
    country:  props.user.country  ?? '',
    timezone: props.user.timezone ?? '',
});
function updateProfile() { profileForm.patch(route('settings.profile'), { preserveScroll: true }); }

// ── Avatar ────────────────────────────────────────────────────────────────────
const avatarPreview = ref(props.user.avatar ? `/storage/${props.user.avatar}` : null);
const avatarFile    = ref(null);
const avatarInput   = ref(null);
const avatarUploading = ref(false);

function onAvatarChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    avatarFile.value    = file;
    avatarPreview.value = URL.createObjectURL(file);
    uploadAvatar(file);
}

async function uploadAvatar(file) {
    avatarUploading.value = true;
    const fd = new FormData();
    fd.append('avatar', file);
    fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content ?? '');

    try {
        const res = await fetchTimeout(route('settings.avatar'), { method: 'POST', body: fd }, 20000);
        if (!res.ok) throw new Error('Upload failed');
    } catch (err) {
        avatarPreview.value = props.user.avatar ? `/storage/${props.user.avatar}` : null;
    } finally { avatarUploading.value = false; }
}

// ── Password form ─────────────────────────────────────────────────────────────
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const showCurrent  = ref(false);
const showNew      = ref(false);
function updatePassword() {
    passwordForm.patch(route('settings.password'), { preserveScroll: true, onSuccess: () => passwordForm.reset() });
}

// ── Sessions ──────────────────────────────────────────────────────────────────
const revokeForm = useForm({});
function revokeSession(id) {
    revokeForm.delete(route('settings.sessions.revoke', id), { preserveScroll: true });
}
function revokeAll() {
    revokeForm.delete(route('settings.sessions.revoke-all'), { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}
function timeAgo(str) {
    if (!str) return 'Never';
    const diff = Math.floor((Date.now() - new Date(str)) / 1000);
    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

const userInitials = computed(() => {
    const n = props.user.name ?? '';
    return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?';
});

const levelConfig = {
    basic:    { label: 'Basic',    color: 'text-slate-400 bg-slate-500/10 border-slate-500/20', icon: User },
    verified: { label: 'Verified', color: 'text-sky-400 bg-sky-500/10 border-sky-500/20',     icon: CheckCircle },
    premium:  { label: 'Premium',  color: 'text-violet-400 bg-violet-500/10 border-violet-500/20', icon: Star },
    vip:      { label: 'VIP',      color: 'text-amber-400 bg-amber-500/10 border-amber-500/20', icon: Award },
};
const level = computed(() => levelConfig[props.user.account_level ?? 'basic'] ?? levelConfig.basic);

const deviceIcon = type => type === 'mobile' ? Smartphone : type === 'tablet' ? Smartphone : Monitor;

function statPct(part, total) {
    if (!total) return 0;
    return Math.round((part / total) * 100);
}

const countries = ['US','GB','NG','GH','KE','CA','AU','DE','FR','JP','IN','BR'];
const timezones = ['UTC','America/New_York','America/Los_Angeles','Europe/London','Europe/Paris','Africa/Lagos','Asia/Tokyo','Australia/Sydney'];

// ── Appearance / theme ───────────────────────────────────────────────────────
const isDark = ref(document.documentElement.classList.contains('dark'));
function setTheme(dark) {
    isDark.value = dark;
    setThemeInstant(dark ? 'dark' : 'light');
}

// ── Notifications form ────────────────────────────────────────────────────────
const notifForm = useForm({
    email_deposits: props.user.preferences?.email_deposits ?? true,
    email_orders:   props.user.preferences?.email_orders   ?? true,
    email_refunds:  props.user.preferences?.email_refunds  ?? true,
    email_weekly:   props.user.preferences?.email_weekly   ?? false,
});
function saveNotifications() {
    notifForm.transform(data => ({ preferences: data }))
        .patch(route('settings.preferences'), { preserveScroll: true });
}

// ── Copy to clipboard ─────────────────────────────────────────────────────────
const copied = ref('');
function copy(text, key) {
    navigator.clipboard.writeText(text).then(() => {
        copied.value = key;
        setTimeout(() => { copied.value = ''; }, 2000);
    });
}
</script>

<template>
    <Head title="Settings" />
    <AuthenticatedLayout>

        <!-- Page header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.15))">
                    <Settings2 class="w-4.5 h-4.5 text-sky-500" />
                </div>
                Account Settings
            </h1>
            <p class="text-[13px] text-slate-400 dark:text-slate-400 mt-0.5">Manage your profile, security, and preferences.</p>
        </div>

        <!-- Flash -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="flash.success" class="mb-5 flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-500/8 border border-emerald-500/20 rounded-2xl">
                <CheckCircle class="w-4.5 h-4.5 text-emerald-500 flex-shrink-0" />
                <p class="text-[13px] font-medium text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
            </div>
        </Transition>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">

            <!-- ── Left sidebar ─────────────────────────────────────────────── -->
            <div class="xl:col-span-1 space-y-4">

                <!-- Profile card -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 text-center relative">
                    <!-- Avatar with upload -->
                    <div class="relative inline-block mb-4">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden mx-auto ring-4 ring-sky-500/20"
                            style="box-shadow: 0 8px 24px rgba(14,165,233,0.2)">
                            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-white text-[24px] font-black"
                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">
                                {{ userInitials }}
                            </div>
                        </div>
                        <!-- Upload overlay -->
                        <button @click="avatarInput?.click()"
                            class="absolute inset-0 rounded-2xl flex items-center justify-center bg-black/50 opacity-0 hover:opacity-100 transition-opacity">
                            <div v-if="avatarUploading" class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
                            <Camera v-else class="w-5 h-5 text-white" />
                        </button>
                        <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                        <!-- Edit icon badge -->
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-lg flex items-center justify-center bg-sky-500 shadow-lg cursor-pointer" @click="avatarInput?.click()">
                            <Pencil class="w-3 h-3 text-white" />
                        </div>
                    </div>

                    <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">{{ user.name }}</h3>
                    <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5 truncate">{{ user.email }}</p>

                    <!-- Level badge -->
                    <div class="mt-3 flex justify-center">
                        <span :class="['inline-flex items-center gap-1.5 text-[10.5px] font-black uppercase tracking-wide px-3 py-1 rounded-full border', level.color]">
                            <component :is="level.icon" class="w-3 h-3" />
                            {{ level.label }}
                        </span>
                    </div>

                    <!-- Quick stats -->
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div class="bg-slate-50 dark:bg-white/[0.04] rounded-xl p-2.5">
                            <p class="text-[18px] font-black text-sky-500">${{ Number(stats.balance ?? 0).toFixed(2) }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">Balance</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-white/[0.04] rounded-xl p-2.5">
                            <p class="text-[18px] font-black text-slate-800 dark:text-white">{{ stats.total_orders ?? 0 }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">Orders</p>
                        </div>
                    </div>

                    <!-- Meta info -->
                    <div class="mt-3 space-y-1.5 text-left">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 dark:text-slate-600">User ID</span>
                            <button @click="copy(String(user.id), 'uid')" class="flex items-center gap-1 font-mono font-bold text-slate-600 dark:text-slate-400 hover:text-sky-500 transition-colors">
                                #{{ user.id }}
                                <Copy class="w-3 h-3" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 dark:text-slate-600">Joined</span>
                            <span class="font-medium text-slate-600 dark:text-slate-400">{{ formatDate(user.created_at) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 dark:text-slate-600">Last active</span>
                            <span class="font-medium text-slate-600 dark:text-slate-400">{{ timeAgo(user.last_active_at) }}</span>
                        </div>
                        <div v-if="user.referral_code" class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 dark:text-slate-600">Referral code</span>
                            <button @click="copy(user.referral_code, 'ref')" class="flex items-center gap-1 font-mono font-bold text-sky-500 hover:text-sky-400 transition-colors">
                                {{ user.referral_code }}
                                <component :is="copied === 'ref' ? Check : Copy" class="w-3 h-3" />
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 dark:text-slate-600">Email</span>
                            <span :class="user.email_verified ? 'text-emerald-500' : 'text-amber-500'" class="font-semibold flex items-center gap-1">
                                <component :is="user.email_verified ? ShieldCheck : Shield" class="w-3 h-3" />
                                {{ user.email_verified ? 'Verified' : 'Unverified' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tab navigation -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-2">
                    <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                        :class="['w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[12.5px] font-medium transition-all',
                            activeTab === tab.key ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5']">
                        <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" :class="activeTab === tab.key ? 'text-sky-500' : 'text-slate-400 dark:text-slate-400'" />
                        {{ tab.label }}
                        <ChevronRight v-if="activeTab !== tab.key" class="ml-auto w-3.5 h-3.5 text-slate-300 dark:text-slate-700" />
                    </button>
                </div>
            </div>

            <!-- ── Right panels ──────────────────────────────────────────────── -->
            <div class="xl:col-span-3">

                <!-- ══ PROFILE TAB ════════════════════════════════════════════ -->
                <div v-if="activeTab === 'profile'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1">Profile Information</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-6">Update your personal details and contact information.</p>

                        <form @submit.prevent="updateProfile" class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <!-- Name -->
                                <div>
                                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Full Name</label>
                                    <input v-model="profileForm.name" type="text"
                                        :class="['w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', profileForm.errors.name ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                                    <p v-if="profileForm.errors.name" class="mt-1 text-[11px] text-rose-500">{{ profileForm.errors.name }}</p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Email Address</label>
                                    <input v-model="profileForm.email" type="email"
                                        :class="['w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', profileForm.errors.email ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                                    <p v-if="profileForm.errors.email" class="mt-1 text-[11px] text-rose-500">{{ profileForm.errors.email }}</p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Phone Number</label>
                                    <input v-model="profileForm.phone" type="tel" placeholder="+1 (555) 000-0000"
                                        class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                </div>

                                <!-- Country -->
                                <div>
                                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Country</label>
                                    <input v-model="profileForm.country" type="text" placeholder="United States"
                                        class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                </div>

                                <!-- Timezone -->
                                <div class="sm:col-span-2">
                                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Timezone</label>
                                    <select v-model="profileForm.timezone"
                                        class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all">
                                        <option value="">Select timezone…</option>
                                        <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" :disabled="profileForm.processing"
                                    class="flex items-center gap-2 px-6 py-2.5 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/25 transition-all active:scale-95">
                                    <Loader2 v-if="profileForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                    <Check v-else class="w-3.5 h-3.5" />
                                    {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ══ STATS TAB ══════════════════════════════════════════════ -->
                <div v-if="activeTab === 'stats'" class="space-y-5">
                    <!-- Balance overview -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-20 h-20 pointer-events-none" style="background: radial-gradient(circle at 100% 0%, rgba(14,165,233,0.1), transparent 70%)" />
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: rgba(14,165,233,0.1)">
                                <CreditCard class="w-4.5 h-4.5 text-sky-500" />
                            </div>
                            <p class="text-[24px] font-black text-slate-900 dark:text-white">${{ Number(stats.balance ?? 0).toFixed(2) }}</p>
                            <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Current Balance</p>
                        </div>
                        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-20 h-20 pointer-events-none" style="background: radial-gradient(circle at 100% 0%, rgba(16,185,129,0.1), transparent 70%)" />
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: rgba(16,185,129,0.1)">
                                <TrendingUp class="w-4.5 h-4.5 text-emerald-500" />
                            </div>
                            <p class="text-[24px] font-black text-slate-900 dark:text-white">${{ Number(stats.total_deposited ?? 0).toFixed(2) }}</p>
                            <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Total Deposited</p>
                        </div>
                        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-20 h-20 pointer-events-none" style="background: radial-gradient(circle at 100% 0%, rgba(245,158,11,0.1), transparent 70%)" />
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-3" style="background: rgba(245,158,11,0.1)">
                                <Zap class="w-4.5 h-4.5 text-amber-500" />
                            </div>
                            <p class="text-[24px] font-black text-slate-900 dark:text-white">${{ Number(stats.total_spent ?? 0).toFixed(2) }}</p>
                            <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Total Spent</p>
                        </div>
                    </div>

                    <!-- Orders breakdown -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[14px] font-bold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                            <Activity class="w-4 h-4 text-sky-500" />
                            Order Statistics
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-slate-50 dark:bg-white/[0.04] rounded-xl">
                                <p class="text-[30px] font-black text-slate-900 dark:text-white">{{ stats.total_orders ?? 0 }}</p>
                                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 mt-0.5">Total Orders</p>
                            </div>
                            <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-500/[0.06] rounded-xl border border-emerald-500/10">
                                <p class="text-[30px] font-black text-emerald-500">{{ stats.success_orders ?? 0 }}</p>
                                <p class="text-[11px] font-semibold text-emerald-500/70 mt-0.5">Successful</p>
                            </div>
                            <div class="text-center p-4 bg-rose-50 dark:bg-rose-500/[0.06] rounded-xl border border-rose-500/10">
                                <p class="text-[30px] font-black text-rose-500">{{ stats.cancel_orders ?? 0 }}</p>
                                <p class="text-[11px] font-semibold text-rose-500/70 mt-0.5">Cancelled</p>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div v-if="stats.total_orders > 0" class="space-y-3">
                            <div>
                                <div class="flex justify-between text-[11px] mb-1.5">
                                    <span class="font-semibold text-emerald-500">Success rate</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ statPct(stats.success_orders, stats.total_orders) }}%</span>
                                </div>
                                <div class="h-2 bg-slate-100 dark:bg-white/8 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000"
                                        style="background: linear-gradient(90deg, #10b981, #059669)"
                                        :style="{ width: statPct(stats.success_orders, stats.total_orders) + '%' }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referrals -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[14px] font-bold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                            <Users class="w-4 h-4 text-violet-500" />
                            Referral Program
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-violet-50 dark:bg-violet-500/[0.06] rounded-xl border border-violet-500/15">
                                <p class="text-[28px] font-black text-violet-500">{{ stats.referral_count ?? 0 }}</p>
                                <p class="text-[12px] font-semibold text-violet-500/70 mt-0.5">Total Referrals</p>
                            </div>
                            <div class="p-4 bg-sky-50 dark:bg-sky-500/[0.06] rounded-xl border border-sky-500/15">
                                <p class="text-[28px] font-black text-sky-500">${{ Number(stats.referral_bonus ?? 0).toFixed(2) }}</p>
                                <p class="text-[12px] font-semibold text-sky-500/70 mt-0.5">Referral Earnings</p>
                            </div>
                        </div>
                        <div v-if="user.referral_code" class="mt-4 flex items-center gap-3 p-3 bg-slate-50 dark:bg-white/[0.04] rounded-xl border border-slate-200 dark:border-white/8">
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-600 mb-0.5">Your referral code</p>
                                <code class="text-[15px] font-black text-sky-500 tracking-wider">{{ user.referral_code }}</code>
                            </div>
                            <button @click="copy(user.referral_code, 'ref2')"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-semibold transition-all active:scale-95"
                                :class="copied === 'ref2' ? 'bg-emerald-500 text-white' : 'bg-sky-500/10 text-sky-500 hover:bg-sky-500/20'">
                                <component :is="copied === 'ref2' ? Check : Copy" class="w-3.5 h-3.5" />
                                {{ copied === 'ref2' ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ══ SECURITY TAB ════════════════════════════════════════════ -->
                <div v-if="activeTab === 'security'" class="space-y-5">

                    <!-- Change password -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                            <Lock class="w-4 h-4 text-sky-500" />
                            Change Password
                        </h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-6">Use a strong password with at least 8 characters.</p>

                        <form @submit.prevent="updatePassword" class="space-y-5 max-w-lg">
                            <div>
                                <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Current Password</label>
                                <div class="relative">
                                    <input v-model="passwordForm.current_password" :type="showCurrent ? 'text' : 'password'"
                                        :class="['w-full h-10 pl-3 pr-10 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', passwordForm.errors.current_password ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                                    <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                                        <component :is="showCurrent ? EyeOff : Eye" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-[11px] text-rose-500">{{ passwordForm.errors.current_password }}</p>
                            </div>
                            <div>
                                <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">New Password</label>
                                <div class="relative">
                                    <input v-model="passwordForm.password" :type="showNew ? 'text' : 'password'"
                                        :class="['w-full h-10 pl-3 pr-10 text-[13px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all', passwordForm.errors.password ? 'border-rose-400' : 'border-slate-200 dark:border-white/8']" />
                                    <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                                        <component :is="showNew ? EyeOff : Eye" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="mt-1 text-[11px] text-rose-500">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Confirm New Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password"
                                    class="w-full h-10 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <button type="submit" :disabled="passwordForm.processing"
                                class="flex items-center gap-2 px-6 py-2.5 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/25 transition-all active:scale-95">
                                <Loader2 v-if="passwordForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                <Key v-else class="w-3.5 h-3.5" />
                                {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                            </button>
                        </form>
                    </div>

                    <!-- 2FA placeholder -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-violet-500/10">
                                    <Shield class="w-5 h-5 text-violet-500" />
                                </div>
                                <div>
                                    <h3 class="text-[14px] font-bold text-slate-900 dark:text-white">Two-Factor Authentication</h3>
                                    <p class="text-[12px] text-slate-400 dark:text-slate-400">Add an extra layer of security to your account.</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wide px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                Coming Soon
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ══ SESSIONS TAB ════════════════════════════════════════════ -->
                <div v-if="activeTab === 'sessions'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <Monitor class="w-4 h-4 text-sky-500" />
                                    Active Sessions
                                </h2>
                                <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Devices where you're currently signed in.</p>
                            </div>
                            <button v-if="loginSessions.length > 1" @click="revokeAll"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-semibold text-rose-500 border border-rose-500/20 hover:bg-rose-500/8 transition-all active:scale-95">
                                <LogOut class="w-3.5 h-3.5" />
                                Sign out all other
                            </button>
                        </div>

                        <div v-if="loginSessions.length === 0" class="py-10 text-center">
                            <Monitor class="w-10 h-10 text-slate-300 dark:text-slate-700 mx-auto mb-3" />
                            <p class="text-[13px] text-slate-400 dark:text-slate-600">No session history available.</p>
                        </div>

                        <div class="space-y-3">
                            <div v-for="sess in loginSessions" :key="sess.id"
                                class="flex items-center gap-4 p-4 rounded-xl border transition-colors"
                                :class="sess.is_current ? 'bg-sky-50 dark:bg-sky-500/[0.06] border-sky-500/20' : 'bg-slate-50 dark:bg-white/[0.03] border-slate-200 dark:border-white/8'">
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', sess.is_current ? 'bg-sky-500/15' : 'bg-slate-100 dark:bg-white/8']">
                                    <component :is="deviceIcon(sess.device_type)" class="w-5 h-5" :class="sess.is_current ? 'text-sky-500' : 'text-slate-400 dark:text-slate-600'" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ sess.browser ?? 'Unknown Browser' }}</p>
                                        <span v-if="sess.is_current" class="text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-sky-500 text-white leading-none">Current</span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                        <span class="text-[11px] text-slate-400 dark:text-slate-600">{{ sess.os ?? 'Unknown OS' }}</span>
                                        <span v-if="sess.ip_address" class="text-[11px] text-slate-400 dark:text-slate-600 font-mono">{{ sess.ip_address }}</span>
                                        <span v-if="sess.country" class="text-[11px] text-slate-400 dark:text-slate-600">{{ sess.city ? sess.city + ', ' : '' }}{{ sess.country }}</span>
                                    </div>
                                    <p class="text-[10.5px] text-slate-300 dark:text-slate-700 mt-0.5">{{ timeAgo(sess.created_at) }}</p>
                                </div>
                                <button v-if="!sess.is_current" @click="revokeSession(sess.id)"
                                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-all active:scale-90">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ NOTIFICATIONS TAB ════════════════════════════════════ -->
                <div v-if="activeTab === 'notifications'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                            <Bell class="w-4 h-4 text-sky-500" />
                            Notification Preferences
                        </h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-6">Choose what you'd like to be notified about.</p>

                        <div class="space-y-4 max-w-lg">
                            <!-- Email section -->
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Email Notifications</p>

                            <template v-for="item in [
                                { key: 'email_deposits',  label: 'Deposit Alerts',           desc: 'Notify when a deposit is credited to your account.' },
                                { key: 'email_orders',    label: 'Order Updates',             desc: 'Updates on order progress, completions, and failures.' },
                                { key: 'email_refunds',   label: 'Refund Notifications',      desc: 'Alert when a refund is issued to your balance.' },
                                { key: 'email_weekly',    label: 'Weekly Summary',            desc: 'A weekly digest of your account activity.' },
                            ]" :key="item.key">
                                <div class="flex items-center justify-between py-3.5 border-b border-slate-100 dark:border-white/5 last:border-0">
                                    <div>
                                        <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ item.label }}</p>
                                        <p class="text-[11.5px] text-slate-400 dark:text-slate-400 mt-0.5">{{ item.desc }}</p>
                                    </div>
                                    <button type="button" @click="notifForm[item.key] = !notifForm[item.key]"
                                        :class="['relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none', notifForm[item.key] ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                        <span :class="['pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm transform transition-transform duration-200', notifForm[item.key] ? 'translate-x-4' : 'translate-x-0']" />
                                    </button>
                                </div>
                            </template>

                            <div class="pt-2">
                                <button @click="saveNotifications" :disabled="notifForm.processing"
                                    class="flex items-center gap-2 px-6 py-2.5 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/25 transition-all active:scale-95">
                                    <Loader2 v-if="notifForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                    <Check v-else class="w-3.5 h-3.5" />
                                    {{ notifForm.processing ? 'Saving…' : 'Save Preferences' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ APPEARANCE TAB ═════════════════════════════════════════ -->
                <div v-if="activeTab === 'appearance'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                            <Palette class="w-4 h-4 text-sky-500" />
                            Appearance
                        </h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-6">Customize how NexaHub looks for you.</p>

                        <div class="space-y-5 max-w-lg">
                            <!-- Theme mode -->
                            <div>
                                <p class="text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-3">Color Mode</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Light Mode -->
                                    <button type="button" @click="setTheme(false)"
                                        :class="['p-4 rounded-xl border-2 text-left transition-all duration-200 w-full', !isDark ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/[0.06] shadow-md shadow-amber-500/10' : 'border-slate-200 dark:border-white/8 bg-slate-50 dark:bg-white/[0.02] hover:border-slate-300 dark:hover:border-white/15']">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center mb-2 transition-colors"
                                            :class="!isDark ? 'bg-amber-100' : 'bg-white border border-slate-200'">
                                            <Sun class="w-4 h-4 text-amber-500" />
                                        </div>
                                        <p class="text-[13px] font-bold text-slate-900 dark:text-white">Light Mode</p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Clean and bright interface.</p>
                                        <span v-if="!isDark" class="mt-2 inline-flex items-center gap-1 text-[10px] font-black text-amber-500">
                                            <Check class="w-3 h-3" /> Active
                                        </span>
                                    </button>
                                    <!-- Dark Mode -->
                                    <button type="button" @click="setTheme(true)"
                                        :class="['p-4 rounded-xl border-2 text-left transition-all duration-200 w-full', isDark ? 'border-sky-500 bg-sky-500/5 shadow-md shadow-sky-500/10' : 'border-slate-200 dark:border-white/8 bg-slate-50 dark:bg-white/[0.02] hover:border-slate-300 dark:hover:border-white/15']">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center mb-2"
                                            :class="isDark ? 'bg-slate-900' : 'bg-slate-100'">
                                            <Moon class="w-4 h-4 text-sky-400" />
                                        </div>
                                        <p class="text-[13px] font-bold text-slate-900 dark:text-white">Dark Mode</p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Easy on the eyes at night.</p>
                                        <span v-if="isDark" class="mt-2 inline-flex items-center gap-1 text-[10px] font-black text-sky-500">
                                            <Check class="w-3 h-3" /> Active
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Accent color -->
                            <div>
                                <p class="text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-3">Accent Color</p>
                                <div class="flex items-center gap-3">
                                    <button v-for="c in [
                                        { name: 'Sky', bg: 'bg-sky-500', active: true },
                                        { name: 'Violet', bg: 'bg-violet-500', active: false },
                                        { name: 'Emerald', bg: 'bg-emerald-500', active: false },
                                        { name: 'Rose', bg: 'bg-rose-500', active: false },
                                    ]" :key="c.name"
                                        :title="c.name"
                                        :class="['w-8 h-8 rounded-xl transition-all', c.bg, c.active ? 'ring-2 ring-offset-2 ring-sky-500 dark:ring-offset-[#0d1e35] scale-110' : 'opacity-40 cursor-not-allowed']"
                                        :disabled="!c.active" />
                                </div>
                                <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-2">Additional color themes coming soon.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ API TAB ════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'api'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-1">
                            <Code2 class="w-4 h-4 text-sky-500" />
                            API Access
                        </h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-5">Manage your API key and access settings.</p>

                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-white/[0.04] rounded-xl border border-slate-200 dark:border-white/8 mb-5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.1))">
                                <Key class="w-5 h-5 text-sky-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-600 dark:text-slate-400">API Key Status</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span :class="['w-2 h-2 rounded-full', hasApiKey ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600']" />
                                    <span class="text-[13px] font-bold" :class="hasApiKey ? 'text-emerald-500' : 'text-slate-400 dark:text-slate-600'">
                                        {{ hasApiKey ? 'Active' : 'No API key generated' }}
                                    </span>
                                </div>
                                <p v-if="apiKeyCreatedAt" class="text-[10.5px] text-slate-400 dark:text-slate-600 mt-0.5">Created {{ formatDate(apiKeyCreatedAt) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <a :href="route('api-center.index')"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-semibold text-white transition-all active:scale-95"
                                style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 16px rgba(14,165,233,0.3)">
                                <Code2 class="w-3.5 h-3.5" />
                                Open API Center
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

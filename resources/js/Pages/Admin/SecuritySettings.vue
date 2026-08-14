<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle,
    ChevronRight,
    Eye,
    EyeOff,
    Lock,
    Monitor,
    Shield,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    settings:         Object,
    recent_logins:    Array,
    admin_login_logs: Array,
    current_ip:       String,
});

const flash = computed(() => usePage().props.flash ?? {});
const activeTab = ref('platform');
const showNewPassword = ref(false);

const tabs = [
    { key: 'platform',     label: 'Platform',          icon: Monitor },
    { key: 'password',     label: 'Password Policy',    icon: Lock   },
    { key: 'admin',        label: 'Admin Credentials',  icon: User   },
    { key: 'whitelist',    label: 'IP Whitelist',        icon: Shield },
    { key: 'admin_logins', label: 'Admin Logins',        icon: Eye    },
    { key: 'history',      label: 'User Logins',         icon: Monitor },
];

const platformForm = useForm({ ...props.settings.platform });
const passwordForm = useForm({ ...props.settings.password });
const adminForm = useForm({
    username:         props.settings.admin.username,
    current_password: '',
    new_password:     '',
    new_password_confirmation: '',
    session_timeout:  props.settings.admin.session_timeout,
});
const ipWhitelistForm = useForm({ ...props.settings.ip_whitelist });

function savePlatform()      { platformForm.post(route('admin.settings.security.save-platform')); }
function savePassword()      { passwordForm.post(route('admin.settings.security.save-password')); }
function saveAdmin()         { adminForm.post(route('admin.settings.security.save-admin')); }
function saveIpWhitelist()   { ipWhitelistForm.post(route('admin.settings.security.save-ip-whitelist')); }

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function agentLabel(ua) {
    if (!ua) return 'Unknown';
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    return ua.substring(0, 40);
}
</script>

<template>
    <Head title="Security Settings — Admin" />
    <AdminLayout>

        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-6">
            <Link :href="route('admin.settings.index')" class="hover:text-sky-500 transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-medium">Security</span>
        </div>

        <div v-if="flash.success" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[13px] font-medium">
            <AlertTriangle class="w-4 h-4 flex-shrink-0" />{{ flash.error }}
        </div>

        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Security</h1>
        </div>

        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-white/5 rounded-xl mb-6 flex-wrap">
            <button v-for="tab in tabs" :key="tab.key"
                @click="activeTab = tab.key"
                :class="['flex items-center gap-2 px-4 py-2 rounded-lg text-[12px] font-semibold transition-all duration-150',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-[#0d1e35] text-sky-600 dark:text-sky-400 shadow-sm border border-slate-200/60 dark:border-sky-500/20'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']">
                <component :is="tab.icon" class="w-3.5 h-3.5" />
                {{ tab.label }}
            </button>
        </div>

        <!-- Platform -->
        <div v-if="activeTab === 'platform'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Platform Controls</h2>
                <div class="space-y-5">

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">Allow Registration</p>
                            <p class="text-[12px] text-slate-400">When off, new user signups are blocked.</p>
                        </div>
                        <div @click="platformForm.allow_registration = !platformForm.allow_registration"
                            :class="['relative w-10 h-5.5 rounded-full cursor-pointer transition-colors', platformForm.allow_registration ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                            <div :class="['absolute top-0.5 w-4.5 h-4.5 rounded-full bg-white shadow transition-transform', platformForm.allow_registration ? 'translate-x-4.5' : 'translate-x-0.5']" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">Require Email Verification</p>
                            <p class="text-[12px] text-slate-400">Users must verify their email before accessing the platform.</p>
                        </div>
                        <div @click="platformForm.require_email_verify = !platformForm.require_email_verify"
                            :class="['relative w-10 h-5.5 rounded-full cursor-pointer transition-colors', platformForm.require_email_verify ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                            <div :class="['absolute top-0.5 w-4.5 h-4.5 rounded-full bg-white shadow transition-transform', platformForm.require_email_verify ? 'translate-x-4.5' : 'translate-x-0.5']" />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-white/[0.06] grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="label">API Rate Limit <span class="normal-case font-normal">(req/min)</span></label>
                            <input v-model.number="platformForm.api_rate_limit" type="number" min="10" max="1000" class="input" />
                        </div>
                        <div>
                            <label class="label">Max Login Attempts</label>
                            <input v-model.number="platformForm.login_attempts_limit" type="number" min="1" max="20" class="input" />
                        </div>
                        <div>
                            <label class="label">Lockout Duration <span class="normal-case font-normal">(minutes)</span></label>
                            <input v-model.number="platformForm.lockout_duration" type="number" min="1" max="1440" class="input" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="savePlatform" :disabled="platformForm.processing" class="btn-primary">{{ platformForm.processing ? 'Saving…' : 'Save Platform Settings' }}</button>
            </div>
        </div>

        <!-- Password Policy -->
        <div v-if="activeTab === 'password'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">User Password Requirements</h2>
                <div class="space-y-5">
                    <div>
                        <label class="label">Minimum Password Length</label>
                        <input v-model.number="passwordForm.min_length" type="number" min="4" max="32" class="input w-40" />
                    </div>
                    <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-white/[0.06]">
                        <toggle-row v-for="field in [
                            { key: 'require_uppercase', label: 'Require uppercase letter', desc: 'At least one A–Z character' },
                            { key: 'require_numbers',   label: 'Require number',           desc: 'At least one 0–9 digit' },
                            { key: 'require_special',   label: 'Require special character', desc: 'At least one !@#$%^&* etc.' },
                        ]" :key="field.key">
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ field.label }}</p>
                                    <p class="text-[12px] text-slate-400">{{ field.desc }}</p>
                                </div>
                                <div @click="passwordForm[field.key] = !passwordForm[field.key]"
                                    :class="['relative w-9 h-5 rounded-full cursor-pointer transition-colors flex-shrink-0', passwordForm[field.key] ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                    <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', passwordForm[field.key] ? 'translate-x-4' : 'translate-x-0.5']" />
                                </div>
                            </div>
                        </toggle-row>
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="savePassword" :disabled="passwordForm.processing" class="btn-primary">{{ passwordForm.processing ? 'Saving…' : 'Save Password Policy' }}</button>
            </div>
        </div>

        <!-- Admin Credentials -->
        <div v-if="activeTab === 'admin'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Admin Credentials</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Admin Username</label>
                        <input v-model="adminForm.username" type="text" class="input" />
                        <p v-if="adminForm.errors.username" class="field-error">{{ adminForm.errors.username }}</p>
                    </div>
                    <div>
                        <label class="label">Current Password</label>
                        <input v-model="adminForm.current_password" type="password" class="input" placeholder="Required to save any changes" autocomplete="current-password" />
                        <p v-if="adminForm.errors.current_password" class="field-error">{{ adminForm.errors.current_password }}</p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-white/[0.06]">
                        <p class="text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-3">New Password <span class="font-normal">(optional — leave blank to keep current)</span></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="relative">
                                <input v-model="adminForm.new_password" :type="showNewPassword ? 'text' : 'password'" class="input pr-10" placeholder="New password" autocomplete="new-password" />
                                <button type="button" @click="showNewPassword = !showNewPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    <EyeOff v-if="showNewPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                                <p v-if="adminForm.errors.new_password" class="field-error">{{ adminForm.errors.new_password }}</p>
                            </div>
                            <div>
                                <input v-model="adminForm.new_password_confirmation" type="password" class="input" placeholder="Confirm new password" autocomplete="new-password" />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="label">Session Timeout <span class="normal-case font-normal">(minutes of inactivity)</span></label>
                        <input v-model.number="adminForm.session_timeout" type="number" min="15" max="1440" class="input w-40" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="saveAdmin" :disabled="adminForm.processing" class="btn-primary">{{ adminForm.processing ? 'Saving…' : 'Update Credentials' }}</button>
            </div>
        </div>

        <!-- IP Whitelist -->
        <div v-if="activeTab === 'whitelist'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Admin IP Whitelist</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Restrict admin panel access to specific IP addresses only.</p>
                    </div>
                    <div @click="ipWhitelistForm.enabled = !ipWhitelistForm.enabled"
                        :class="['relative w-10 h-5.5 rounded-full cursor-pointer flex-shrink-0 transition-colors', ipWhitelistForm.enabled ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                        <div :class="['absolute top-0.5 w-4.5 h-4.5 rounded-full bg-white shadow transition-transform', ipWhitelistForm.enabled ? 'translate-x-4.5' : 'translate-x-0.5']" />
                    </div>
                </div>

                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 mb-4">
                    <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0" />
                    <p class="text-[12px] text-amber-700 dark:text-amber-400">
                        Your current IP: <strong class="font-mono">{{ current_ip }}</strong> — must be listed if you enable this.
                    </p>
                </div>

                <div>
                    <label class="label">Allowed IPs <span class="normal-case font-normal">(one per line or comma-separated)</span></label>
                    <textarea v-model="ipWhitelistForm.ips" rows="6" class="input resize-none font-mono text-[12px]"
                        placeholder="192.168.1.1&#10;10.0.0.0&#10;2001:db8::1" />
                    <p v-if="ipWhitelistForm.errors.ips" class="field-error">{{ ipWhitelistForm.errors.ips }}</p>
                    <p class="mt-2 text-[11px] text-slate-400">Supports IPv4 and IPv6. Leave blank to allow all IPs (only toggle disables enforcement).</p>
                </div>
            </div>
            <div class="flex justify-end">
                <button @click="saveIpWhitelist" :disabled="ipWhitelistForm.processing" class="btn-primary">
                    {{ ipWhitelistForm.processing ? 'Saving…' : 'Save IP Whitelist' }}
                </button>
            </div>
        </div>

        <!-- Admin Login Logs -->
        <div v-if="activeTab === 'admin_logins'">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8 flex items-center justify-between">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Admin Login History</h2>
                        <p class="text-[12px] text-slate-400 mt-0.5">All admin panel login and logout events.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 bg-slate-100 dark:bg-white/5 px-2.5 py-1 rounded-lg">Last 50 events</span>
                </div>
                <div v-if="!admin_login_logs?.length" class="py-12 text-center text-[13px] text-slate-400">No admin login history yet.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Admin</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">IP Address</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Browser / OS</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Session</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                            <tr v-for="log in admin_login_logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/3">
                                <td class="px-5 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ log.admin_username }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-lg',
                                        log.action === 'login'
                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400'
                                            : 'bg-slate-100 text-slate-600 dark:bg-white/8 dark:text-slate-400']">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-500 dark:text-slate-400">{{ log.ip_address ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-700 dark:text-slate-300">{{ log.browser }}</p>
                                    <p class="text-[11px] text-slate-400">{{ log.os }}</p>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-400">
                                    <span v-if="log.duration_minutes !== null">{{ log.duration_minutes }}m</span>
                                    <span v-else-if="log.action === 'login' && !log.logout_at" class="text-emerald-500 font-semibold text-[11px]">Active</span>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-400">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-lg',
                                        log.status === 'success'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400']">
                                        {{ log.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Login History -->
        <div v-if="activeTab === 'history'">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8 flex items-center justify-between">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">User Login Activity</h2>
                        <p class="text-[12px] text-slate-400 mt-0.5">Last 20 login events across all user accounts.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 bg-slate-100 dark:bg-white/5 px-2.5 py-1 rounded-lg">{{ recent_logins.length }} records</span>
                </div>
                <div v-if="!recent_logins?.length" class="py-12 text-center text-[13px] text-slate-400">No login history yet. Logins are tracked after users sign in.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">IP Address</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Browser / Device</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Session</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                            <tr v-for="log in recent_logins" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/3">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ log.user?.name ?? 'Unknown' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ log.user?.email ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-500 dark:text-slate-400">{{ log.ip_address ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-slate-700 dark:text-slate-300">{{ log.browser ?? agentLabel(log.user_agent) }}</p>
                                    <p class="text-[11px] text-slate-400">{{ log.os ?? '' }}{{ log.device_type ? ' · ' + log.device_type : '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-lg',
                                        (log.action ?? 'login') === 'login'
                                            ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400'
                                            : 'bg-slate-100 text-slate-600 dark:bg-white/8 dark:text-slate-400']">
                                        {{ log.action ?? 'login' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-[12px] text-slate-400">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="log.is_current"
                                        class="text-[11px] font-bold px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 flex items-center gap-1 w-fit">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse inline-block"></span>
                                        Active
                                    </span>
                                    <span v-else class="text-[11px] text-slate-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
.label { @apply block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5; }
.input { @apply w-full px-3 py-2.5 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all; }
.field-error { @apply mt-1 text-[11px] text-rose-500; }
.btn-primary { @apply px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-500 text-white text-[13px] font-bold shadow-sky-500/25 shadow-lg hover:from-sky-600 hover:to-blue-600 hover:-translate-y-px transition-all active:scale-95 disabled:opacity-60 disabled:translate-y-0 disabled:cursor-not-allowed; }
</style>

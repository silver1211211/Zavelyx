<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    CheckCircle,
    ChevronRight,
    Clock,
    Plus,
    Shield,
    Trash2,
    User,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    roles:          Array,
    permissions:    Array,
    users:          Array,
    login_history:  Array,
    activity_logs:  Array,
    default_roles:  Array,
});

const flash = computed(() => usePage().props.flash ?? {});
const activeTab = ref('roles');
const userSearch = ref('');

const tabs = [
    { key: 'roles',    label: 'Roles',           icon: Shield   },
    { key: 'users',    label: 'User Roles',       icon: Users    },
    { key: 'history',  label: 'Login History',    icon: Clock    },
    { key: 'activity', label: 'Admin Activity',   icon: Activity },
];

const filteredUsers = computed(() => {
    const q = userSearch.value.toLowerCase();
    if (!q) return props.users;
    return props.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

const roleColorMap = {
    'super-admin': 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
    'admin':       'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
    'support':     'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
    'finance':     'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    'moderator':   'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'developer':   'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-400',
};

function getRoleColor(name) {
    return roleColorMap[name] ?? 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400';
}

const newRoleForm = useForm({ name: '' });
const assignForm  = useForm({ role: '' });

function createRole() {
    newRoleForm.post(route('admin.access-control.roles.create'), {
        onSuccess: () => newRoleForm.reset(),
    });
}

function seedRoles() {
    router.post(route('admin.access-control.roles.seed'), {}, { preserveScroll: true });
}

function assignRole(userId, role) {
    if (!role) return;
    router.post(route('admin.access-control.users.assign-role', userId), { role }, { preserveScroll: true });
}

function removeRole(userId, role) {
    if (!confirm(`Remove role '${role}' from this user?`)) return;
    router.delete(route('admin.access-control.users.remove-role', userId), { data: { role }, preserveScroll: true });
}

function deleteRole(roleId) {
    if (!confirm('Delete this role? Users with this role will lose it.')) return;
    router.delete(route('admin.access-control.roles.destroy', roleId), { preserveScroll: true });
}

const selectedRole = ref({});

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function actionColor(action) {
    if (action?.includes('delete'))   return 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400';
    if (action?.includes('reply'))    return 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400';
    if (action?.includes('status'))   return 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400';
    if (action?.includes('login'))    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400';
    return 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-400';
}
</script>

<template>
    <Head title="Access Control — Admin" />
    <AdminLayout>

        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-6">
            <Link :href="route('admin.settings.index')" class="hover:text-sky-500 transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-medium">Access Control</span>
        </div>

        <div v-if="flash.success" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 text-[13px] font-medium">
            <AlertTriangle class="w-4 h-4 flex-shrink-0" />{{ flash.error }}
        </div>

        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Access Control</h1>
        </div>

        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-white/5 rounded-xl mb-6 w-fit">
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

        <!-- Roles tab -->
        <div v-if="activeTab === 'roles'" class="space-y-5">

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Roles</h2>
                    <button @click="seedRoles"
                        class="text-[11px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                        Seed Default Roles
                    </button>
                </div>

                <div v-if="roles.length === 0" class="py-8 text-center">
                    <p class="text-[13px] text-slate-400">No roles yet. Click "Seed Default Roles" to create them.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
                    <div v-for="role in roles" :key="role.id"
                        class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200 dark:border-sky-500/12 bg-slate-50 dark:bg-white/3">
                        <div class="flex items-center gap-2.5">
                            <span :class="['px-2 py-0.5 rounded-lg text-[11px] font-bold', getRoleColor(role.name)]">{{ role.name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-slate-400">{{ role.users_count }} users</span>
                            <button v-if="!['super-admin', 'admin'].includes(role.name)"
                                @click="deleteRole(role.id)"
                                class="p-1 text-slate-300 dark:text-slate-700 hover:text-rose-500 dark:hover:text-rose-400 transition-colors">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Create custom role -->
                <div class="pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                    <p class="text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-2">Create Custom Role</p>
                    <div class="flex gap-3">
                        <input v-model="newRoleForm.name" type="text" placeholder="e.g. marketing" maxlength="50"
                            class="flex-1 h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                        <button @click="createRole" :disabled="newRoleForm.processing || !newRoleForm.name.trim()"
                            class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-50 text-white rounded-xl transition-colors">
                            <Plus class="w-3.5 h-3.5" /> Create
                        </button>
                    </div>
                    <p v-if="newRoleForm.errors.name" class="mt-1 text-[11px] text-rose-500">{{ newRoleForm.errors.name }}</p>
                </div>
            </div>
        </div>

        <!-- User Roles tab -->
        <div v-if="activeTab === 'users'" class="space-y-4">
            <div class="flex gap-3">
                <div class="relative flex-1">
                    <input v-model="userSearch" type="text" placeholder="Search users…"
                        class="w-full h-9 pl-4 pr-4 text-[13px] bg-white dark:bg-[#0d1e35] border border-slate-200 dark:border-sky-500/12 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Current Roles</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Assign Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-white/3 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ user.name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-if="user.roles.length === 0" class="text-[11px] text-slate-400">No roles</span>
                                        <span v-for="role in user.roles" :key="role"
                                            class="group inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold cursor-pointer hover:opacity-80 transition-opacity"
                                            :class="getRoleColor(role)"
                                            @click="removeRole(user.id, role)"
                                            :title="`Click to remove '${role}'`">
                                            {{ role }}
                                            <span class="opacity-0 group-hover:opacity-100 text-[9px]">×</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <select v-model="selectedRole[user.id]"
                                            class="h-8 px-2 text-[12px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-lg text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-sky-500/30">
                                            <option value="">Pick role…</option>
                                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                        </select>
                                        <button @click="assignRole(user.id, selectedRole[user.id])"
                                            :disabled="!selectedRole[user.id]"
                                            class="h-8 px-3 text-[11px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg transition-colors">
                                            Assign
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Login History tab -->
        <div v-if="activeTab === 'history'">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Login History</h2>
                </div>
                <div v-if="login_history.length === 0" class="py-12 text-center text-[13px] text-slate-400">No login history.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">IP</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                            <tr v-for="log in login_history" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/3">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ log.user?.name ?? '—' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ log.user?.email }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-500 dark:text-slate-400">{{ log.ip_address }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-400">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-lg', log.status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400']">
                                        {{ log.status ?? 'success' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin Activity tab -->
        <div v-if="activeTab === 'activity'">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Admin Activity Log</h2>
                    <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Last 100 admin actions across the panel.</p>
                </div>
                <div v-if="!activity_logs?.length" class="py-12 text-center text-[13px] text-slate-400">No activity recorded yet.</div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                                <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Admin</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Description</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">IP</th>
                                <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                            <tr v-for="log in activity_logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/3">
                                <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ log.admin }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-0.5 rounded-lg text-[10px] font-bold', actionColor(log.action)]">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ log.description }}</td>
                                <td class="px-4 py-3 font-mono text-[12px] text-slate-400 whitespace-nowrap">{{ log.ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-[12px] text-slate-400 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

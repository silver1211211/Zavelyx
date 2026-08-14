<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Ban, ChevronLeft, ChevronRight, CircleCheck,
    Search, ShieldAlert, User, Users, Wallet,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    users:  Object,
    search: { type: String, default: '' },
});

const flash = computed(() => usePage().props.flash ?? {});

// ── Search ────────────────────────────────────────────────────────────────────
const searchQuery = ref(props.search);
let searchTimer = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.users.index'), { search: val }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
});

// ── Helpers ───────────────────────────────────────────────────────────────────
const initials = (name) => (name ?? '').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?';
const statusLabel = (wallet) => wallet?.is_active === false ? 'Frozen' : 'Active';
const statusClass = (wallet) => wallet?.is_active === false
    ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
    : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400';
const fmt = (n) => Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="User Management" />
    <AdminLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">User Management</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ users.total.toLocaleString() }} registered users
                    </p>
                </div>
            </div>

            <!-- Flash -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="flash.success" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium">
                    <CircleCheck class="w-4 h-4 flex-shrink-0" />
                    {{ flash.success }}
                </div>
            </Transition>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Total Users</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ users.total.toLocaleString() }}</p>
                </div>
                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Showing</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ users.data.length }}</p>
                </div>
                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Page</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ users.current_page }}<span class="text-slate-400 font-normal text-base">/{{ users.last_page }}</span></p>
                </div>
                <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-600">Per Page</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ users.per_page }}</p>
                </div>
            </div>

            <!-- Table card -->
            <div class="bg-white dark:bg-[#0d1829] rounded-2xl border border-slate-200 dark:border-white/[0.06] overflow-hidden">

                <!-- Search -->
                <div class="p-4 border-b border-slate-100 dark:border-white/[0.05]">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search by name or email…"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm
                                bg-slate-50 dark:bg-white/[0.04]
                                border border-slate-200 dark:border-white/[0.07]
                                text-slate-800 dark:text-slate-200
                                placeholder-slate-400 dark:placeholder-slate-600
                                focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400 transition-all" />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05] bg-slate-50/50 dark:bg-white/[0.01]">
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">User</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Balance</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Orders</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Joined</th>
                                <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <Users class="w-10 h-10 mx-auto mb-3 text-slate-300 dark:text-slate-700" />
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">No users found</p>
                                </td>
                            </tr>
                            <tr v-for="user in users.data" :key="user.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors group">
                                <!-- User info -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-sky-500 to-blue-600
                                            flex items-center justify-center text-white text-[11px] font-black flex-shrink-0 ring-2 ring-white dark:ring-[#0d1829]">
                                            {{ initials(user.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[160px] leading-tight">{{ user.name }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400 truncate max-w-[160px] leading-tight mt-px">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Balance -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <Wallet class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" />
                                        <span class="font-bold text-slate-800 dark:text-slate-200 font-mono text-[13px]">
                                            {{ user.wallet?.currency ?? 'NGN' }} {{ fmt(user.wallet?.balance) }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Orders -->
                                <td class="px-4 py-3.5">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ user.orders_count }}</span>
                                </td>
                                <!-- Status -->
                                <td class="px-4 py-3.5">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold', statusClass(user.wallet)]">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                            :class="user.wallet?.is_active === false ? 'bg-rose-500' : 'bg-emerald-500 animate-pulse'" />
                                        {{ statusLabel(user.wallet) }}
                                    </span>
                                </td>
                                <!-- Joined -->
                                <td class="px-4 py-3.5 text-[12px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ fmtDate(user.created_at) }}
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3.5">
                                    <Link :href="route('admin.users.show', user.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold
                                            bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400
                                            hover:bg-sky-100 dark:hover:bg-sky-500/20
                                            border border-sky-200 dark:border-sky-500/20
                                            transition-all active:scale-95">
                                        <User class="w-3 h-3" />
                                        Manage
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-white/[0.05]">
                    <p class="text-[12px] text-slate-500 dark:text-slate-400">
                        Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link v-if="users.prev_page_url" :href="users.prev_page_url"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-500 dark:text-slate-400 transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span class="px-3 py-1 text-[12px] font-semibold text-slate-700 dark:text-slate-300">
                            {{ users.current_page }} / {{ users.last_page }}
                        </span>
                        <Link v-if="users.next_page_url" :href="users.next_page_url"
                            class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/[0.06] text-slate-500 dark:text-slate-400 transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { fetchTimeout } from '@/utils/fetchTimeout';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Check, DollarSign, Globe, Loader2, Pencil, Plus,
    RefreshCw, Star, Trash2, X, Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    currencies:        { type: Array,  default: () => [] },
    currency_settings: { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── Live-rate settings form ───────────────────────────────────────────────────
const settingsForm = useForm({
    live_rates_enabled:        props.currency_settings.live_rates_enabled ?? false,
    exchange_api_url:          props.currency_settings.exchange_api_url   ?? 'https://open.er-api.com/v6/latest/USD',
    exchange_refresh_interval: props.currency_settings.exchange_refresh_interval ?? 30,
});

function saveSettings() {
    settingsForm.post(route('admin.website-settings.currency-settings.save'), { preserveScroll: true });
}

// ── Manual rate refresh ───────────────────────────────────────────────────────
const refreshing     = ref(false);
const refreshMessage = ref('');
const lastSyncedAt   = ref(props.currency_settings.last_synced_at ?? null);

async function refreshRates() {
    refreshing.value     = true;
    refreshMessage.value = '';
    try {
        const res = await fetchTimeout(route('admin.website-settings.currencies.refresh-rates'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            },
        }, 20000);
        const data = await res.json();
        if (data.ok) {
            refreshMessage.value = data.message ?? 'Rates refreshed successfully.';
            lastSyncedAt.value   = data.last_synced_at ?? null;
            router.reload({ only: ['currencies'] });
        } else {
            refreshMessage.value = 'Error: ' + (data.error ?? 'Unknown error');
        }
    } catch (e) {
        refreshMessage.value = 'Request failed: ' + e.message;
    } finally {
        refreshing.value = false;
    }
}

function formatSyncTime(iso) {
    if (!iso) return 'Never';
    try { return new Date(iso).toLocaleString(); } catch { return iso; }
}

// ── Add / Edit currency modal ─────────────────────────────────────────────────
const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    code:          '',
    name:          '',
    symbol:        '',
    exchange_rate: '',
    sort_order:    99,
    is_active:     true,
});

function openAdd() {
    editingId.value = null;
    form.reset();
    form.sort_order  = 99;
    form.is_active   = true;
    showForm.value   = true;
}

function openEdit(c) {
    editingId.value       = c.id;
    form.code             = c.code;
    form.name             = c.name;
    form.symbol           = c.symbol;
    form.exchange_rate    = c.exchange_rate;
    form.sort_order       = c.sort_order;
    form.is_active        = c.is_active;
    form.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value  = false;
    editingId.value = null;
    form.reset();
}

function submitForm() {
    if (editingId.value) {
        form.put(route('admin.website-settings.currencies.update', editingId.value), {
            preserveScroll: true,
            onSuccess:      closeForm,
        });
    } else {
        form.post(route('admin.website-settings.currencies.store'), {
            preserveScroll: true,
            onSuccess:      closeForm,
        });
    }
}

// ── Toggle / Default / Delete ─────────────────────────────────────────────────
function toggleCurrency(c) {
    router.patch(route('admin.website-settings.currencies.toggle', c.id), {}, { preserveScroll: true });
}

function setDefault(c) {
    if (c.is_default) return;
    router.patch(route('admin.website-settings.currencies.default', c.id), {}, { preserveScroll: true });
}

function deleteCurrency(c) {
    if (c.is_default) { alert('Cannot delete the default currency.'); return; }
    if (!confirm(`Delete ${c.code} — ${c.name}?`)) return;
    router.delete(route('admin.website-settings.currencies.destroy', c.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Currency Settings — Admin" />
    <AdminLayout>

        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Currency Settings</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                Manage supported currencies, exchange rates, and live rate automation.
            </p>
        </div>

        <!-- Flash -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="flash.success"
                class="mb-5 flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-500/25 rounded-2xl">
                <Check class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                <p class="text-[13px] font-medium text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
            </div>
        </Transition>

        <div class="space-y-6">

            <!-- ── Live Rate Settings ──────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                        style="background:linear-gradient(135deg,rgba(14,165,233,0.15),rgba(99,102,241,0.12))">
                        <Zap class="w-5 h-5 text-sky-500" />
                    </div>
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Live Exchange Rate Automation</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">
                            Auto-fetch rates every N minutes from an open forex API.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-5">

                    <!-- Enable toggle -->
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.08]">
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">Enable Live Rate Fetching</p>
                            <p class="text-[11.5px] text-slate-400 dark:text-slate-400 mt-0.5">
                                Automatically update rates on the configured schedule.
                            </p>
                        </div>
                        <!-- Tailwind UI standard toggle: border-2 border-transparent makes thumb math exact -->
                        <button type="button"
                            role="switch"
                            :aria-checked="settingsForm.live_rates_enabled"
                            @click="settingsForm.live_rates_enabled = !settingsForm.live_rates_enabled"
                            :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full',
                                'border-2 border-transparent transition-colors duration-200 ease-in-out',
                                'focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:ring-offset-2 dark:focus:ring-offset-[#0d1e35]',
                                settingsForm.live_rates_enabled
                                    ? 'bg-sky-500'
                                    : 'bg-slate-300 dark:bg-slate-600',
                            ]">
                            <span
                                aria-hidden="true"
                                :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white',
                                    'shadow-sm ring-0 transition duration-200 ease-in-out',
                                    settingsForm.live_rates_enabled ? 'translate-x-5' : 'translate-x-0',
                                ]"
                            />
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- API URL -->
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">
                                API URL
                            </label>
                            <div class="relative">
                                <Globe class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="settingsForm.exchange_api_url" type="url"
                                    placeholder="https://open.er-api.com/v6/latest/USD"
                                    class="w-full h-11 pl-9 pr-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[12.5px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-400">
                                Must return <code class="font-mono bg-slate-100 dark:bg-white/[0.08] px-1 rounded">{"rates":{"USD":1,"EUR":...}}</code>
                            </p>
                        </div>

                        <!-- Interval -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">
                                Refresh Every (min)
                            </label>
                            <input v-model.number="settingsForm.exchange_refresh_interval" type="number"
                                min="5" max="1440" step="5"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-400">Min 5 min, max 1440 (24h)</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-wrap">
                        <button type="submit" :disabled="settingsForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold text-white
                                transition-all active:scale-95 disabled:opacity-50"
                            style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                            <Loader2 v-if="settingsForm.processing" class="w-4 h-4 animate-spin" />
                            <Check v-else class="w-4 h-4" />
                            Save Settings
                        </button>

                        <!-- Manual refresh -->
                        <button type="button" @click="refreshRates" :disabled="refreshing"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[13px] font-bold
                                bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400
                                hover:bg-emerald-200 dark:hover:bg-emerald-500/30 transition-all active:scale-95 disabled:opacity-50">
                            <Loader2 v-if="refreshing" class="w-4 h-4 animate-spin" />
                            <RefreshCw v-else class="w-4 h-4" />
                            Refresh Now
                        </button>

                        <span class="text-[11.5px] text-slate-400 dark:text-slate-400">
                            Last synced: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ formatSyncTime(lastSyncedAt) }}</span>
                        </span>
                    </div>

                    <!-- Refresh result message -->
                    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="refreshMessage"
                            :class="['p-3 rounded-xl text-[12px] font-medium border',
                                refreshMessage.startsWith('Error') || refreshMessage.startsWith('Request')
                                    ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-500/25 text-rose-700 dark:text-rose-400'
                                    : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-500/25 text-emerald-700 dark:text-emerald-400']">
                            {{ refreshMessage }}
                        </div>
                    </Transition>
                </form>
            </div>

            <!-- ── Currency List ───────────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/[0.07]">
                    <div class="flex items-center gap-2">
                        <DollarSign class="w-5 h-5 text-sky-500" />
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Supported Currencies</h2>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                            bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400">
                            {{ currencies.length }}
                        </span>
                    </div>
                    <button @click="openAdd"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-[12.5px] font-bold text-white
                            transition-all active:scale-95"
                        style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                        <Plus class="w-4 h-4" />
                        Add Currency
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-[12.5px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.05]">
                                <th class="text-left px-6 py-3 text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Currency</th>
                                <th class="text-left px-4 py-3 text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Symbol</th>
                                <th class="text-right px-4 py-3 text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Rate (per $1 USD)</th>
                                <th class="text-center px-4 py-3 text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Status</th>
                                <th class="text-center px-4 py-3 text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Default</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
                            <tr v-for="c in currencies" :key="c.id"
                                class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[11px] font-black text-white flex-shrink-0"
                                            style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                                            {{ c.code.slice(0,2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ c.code }}</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-400">{{ c.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-700 dark:text-slate-300">{{ c.symbol }}</td>
                                <td class="px-4 py-3.5 text-right font-semibold text-slate-700 dark:text-slate-300">
                                    {{ Number(c.exchange_rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <button @click="toggleCurrency(c)"
                                        :disabled="c.is_default"
                                        :class="['px-2.5 py-1 rounded-full text-[10.5px] font-bold transition-all',
                                            c.is_active
                                                ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30'
                                                : 'bg-slate-100 dark:bg-white/[0.06] text-slate-500 hover:bg-slate-200 dark:hover:bg-white/[0.1]',
                                            c.is_default ? 'cursor-not-allowed opacity-75' : 'cursor-pointer']">
                                        {{ c.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <button @click="setDefault(c)"
                                        :class="['w-7 h-7 flex items-center justify-center rounded-full mx-auto transition-all',
                                            c.is_default
                                                ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-500 cursor-default'
                                                : 'bg-slate-100 dark:bg-white/[0.06] text-slate-400 hover:bg-amber-100 dark:hover:bg-amber-500/20 hover:text-amber-500']">
                                        <Star class="w-3.5 h-3.5" :fill="c.is_default ? 'currentColor' : 'none'" />
                                    </button>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <button @click="openEdit(c)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg
                                                bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400
                                                hover:bg-sky-100 dark:hover:bg-sky-500/20 hover:text-sky-500 transition-all">
                                            <Pencil class="w-3 h-3" />
                                        </button>
                                        <button @click="deleteCurrency(c)"
                                            :disabled="c.is_default"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg
                                                bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400
                                                hover:bg-rose-100 dark:hover:bg-rose-500/20 hover:text-rose-500
                                                transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                            <Trash2 class="w-3 h-3" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="currencies.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-[13px] text-slate-400 dark:text-slate-600">
                                    No currencies configured yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info note -->
            <div class="rounded-2xl bg-sky-50 dark:bg-sky-500/[0.07] border border-sky-500/20 p-4">
                <p class="text-[12px] font-semibold text-sky-700 dark:text-sky-400 mb-1">How Exchange Rates Work</p>
                <p class="text-[11.5px] text-sky-600/80 dark:text-sky-500/70 leading-relaxed">
                    All balances are stored internally in USD. Exchange rates only affect the display layer — when a user
                    selects a currency, amounts are multiplied by the rate. No actual conversion or transfer happens.
                    USD must always have a rate of 1.0 and cannot be auto-updated.
                </p>
            </div>

        </div>

        <!-- ── Add / Edit Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showForm"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                style="background:rgba(2,10,22,0.8);backdrop-filter:blur(16px)"
                @click.self="closeForm">

                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" appear>
                <div class="w-full max-w-md rounded-3xl bg-white dark:bg-[#0a1628]
                    border border-white/50 dark:border-white/[0.08]
                    shadow-[0_32px_80px_rgba(0,0,0,0.6)]">

                    <div class="px-6 py-5 border-b border-slate-100 dark:border-white/[0.06] flex items-center justify-between">
                        <h2 class="text-[15px] font-black text-slate-900 dark:text-white">
                            {{ editingId ? 'Edit Currency' : 'Add Currency' }}
                        </h2>
                        <button @click="closeForm"
                            class="w-7 h-7 flex items-center justify-center rounded-full
                                bg-slate-100 dark:bg-white/[0.08] text-slate-500
                                hover:bg-slate-200 dark:hover:bg-white/[0.15] transition-colors">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Code *</label>
                                <input v-model="form.code" type="text" placeholder="USD" maxlength="10" required
                                    :disabled="!!editingId"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px] uppercase
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all
                                        disabled:opacity-60 disabled:cursor-not-allowed" />
                                <p v-if="form.errors.code" class="mt-1 text-[11px] text-rose-500">{{ form.errors.code }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Symbol *</label>
                                <input v-model="form.symbol" type="text" placeholder="$" maxlength="10" required
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                <p v-if="form.errors.symbol" class="mt-1 text-[11px] text-rose-500">{{ form.errors.symbol }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Name *</label>
                            <input v-model="form.name" type="text" placeholder="US Dollar" maxlength="100" required
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Rate per $1 USD *</label>
                                <input v-model="form.exchange_rate" type="number" step="any" min="0.000001" required
                                    placeholder="1.0"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                                <p v-if="form.errors.exchange_rate" class="mt-1 text-[11px] text-rose-500">{{ form.errors.exchange_rate }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Sort Order</label>
                                <input v-model.number="form.sort_order" type="number" min="0" max="999"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                        </div>

                        <!-- Active toggle -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-[13px] font-semibold text-slate-700 dark:text-slate-300">Active</span>
                            <button type="button"
                                role="switch"
                                :aria-checked="form.is_active"
                                @click="form.is_active = !form.is_active"
                                :class="[
                                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full',
                                    'border-2 border-transparent transition-colors duration-200 ease-in-out',
                                    'focus:outline-none focus:ring-2 focus:ring-sky-500/40',
                                    form.is_active ? 'bg-sky-500' : 'bg-slate-300 dark:bg-slate-600',
                                ]">
                                <span
                                    aria-hidden="true"
                                    :class="[
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white',
                                        'shadow-sm ring-0 transition duration-200 ease-in-out',
                                        form.is_active ? 'translate-x-4' : 'translate-x-0',
                                    ]"
                                />
                            </button>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="button" @click="closeForm"
                                class="flex-1 h-11 rounded-xl text-[13px] font-semibold
                                    text-slate-600 dark:text-slate-400
                                    bg-slate-100 dark:bg-white/[0.06]
                                    hover:bg-slate-200 dark:hover:bg-white/[0.1]
                                    transition-all border border-slate-200 dark:border-white/[0.07]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 h-11 rounded-xl text-[13px] font-black text-white transition-all disabled:opacity-60"
                                style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin inline mr-1" />
                                {{ editingId ? 'Save Changes' : 'Add Currency' }}
                            </button>
                        </div>

                    </form>
                </div>
                </Transition>
            </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>

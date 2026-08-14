<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertCircle, Check, ChevronDown, ChevronRight, Download, Key,
    Loader2, Pencil, Plus, RefreshCw, ToggleLeft, ToggleRight, Trash2, X, Zap,
    SlidersHorizontal,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    providers: { type: Array, default: () => [] },
});

// ── UI State ─────────────────────────────────────────────────────────────────
const showAddModal = ref(false);
const showEditModal = ref(false);
const editTarget = ref(null);
const recalculating = ref(null);
const expandedProvider = ref(null);
const providerServices = ref({});
const loadingServices = ref({});
const testingProvider = ref(null);
const testResults = ref({});
const importingProvider = ref(null);

// ── Forms ─────────────────────────────────────────────────────────────────────
const addForm = useForm({
    name: '',
    base_url: '',
    api_key: '',
    markup_type: 'percentage',
    markup_value: 0,
    is_active: false,
});

const editForm = useForm({
    name: '',
    base_url: '',
    api_key: '',
    markup_type: 'percentage',
    markup_value: 0,
    is_active: false,
});

// ── Modals ────────────────────────────────────────────────────────────────────
function openAdd() {
    addForm.reset();
    showAddModal.value = true;
}

function openEdit(provider) {
    editTarget.value = provider;
    editForm.name = provider.name;
    editForm.base_url = provider.base_url;
    editForm.api_key = '';
    editForm.markup_type = provider.markup_type;
    editForm.markup_value = provider.markup_value;
    editForm.is_active = provider.is_active;
    showEditModal.value = true;
}

function submitAdd() {
    addForm.post(route('admin.api-settings.providers.store'), {
        onSuccess: () => { showAddModal.value = false; addForm.reset(); },
    });
}

function submitEdit() {
    editForm.put(route('admin.api-settings.providers.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

function deleteProvider(provider) {
    if (!confirm(`Delete "${provider.name}"? This will permanently delete the provider AND all its imported services.`)) return;
    router.delete(route('admin.api-settings.providers.destroy', provider.id), { preserveScroll: true });
}

function recalculateMarkup(provider) {
    if (!confirm(`Recalculate all ${provider.services_count} service prices for "${provider.name}" using the current markup?`)) return;
    recalculating.value = provider.id;
    router.post(route('admin.api-settings.providers.recalculate', provider.id), {}, {
        preserveScroll: true,
        onFinish: () => { recalculating.value = null; },
    });
}

function toggleProvider(provider) {
    router.patch(route('admin.api-settings.providers.toggle', provider.id), {}, { preserveScroll: true });
}

// ── Test Connection ───────────────────────────────────────────────────────────
async function testConnection(provider) {
    testingProvider.value = provider.id;
    testResults.value[provider.id] = null;

    try {
        const resp = await axios.post(route('admin.api-settings.providers.test', provider.id), {}, { timeout: 20000 });
        testResults.value[provider.id] = resp.data;
    } catch (e) {
        testResults.value[provider.id] = { success: false, message: e.code === 'ECONNABORTED' ? 'Request timed out.' : (e.response?.data?.message ?? 'Request failed.') };
    } finally {
        testingProvider.value = null;
    }
}

// ── Import Services ───────────────────────────────────────────────────────────
function importServices(provider) {
    if (!confirm(`Import services from "${provider.name}"? Existing services will be updated.`)) return;
    importingProvider.value = provider.id;
    router.post(route('admin.api-settings.providers.import', provider.id), {}, {
        preserveScroll: true,
        onFinish: () => { importingProvider.value = null; },
    });
}

// ── Expand / load services ─────────────────────────────────────────────────
async function toggleExpand(provider) {
    if (expandedProvider.value === provider.id) {
        expandedProvider.value = null;
        return;
    }

    expandedProvider.value = provider.id;

    if (providerServices.value[provider.id]) return;

    loadingServices.value[provider.id] = true;
    try {
        const resp = await axios.get(route('admin.api-settings.providers.services', provider.id), { timeout: 20000 });
        providerServices.value[provider.id] = resp.data;
    } catch {
        providerServices.value[provider.id] = [];
    } finally {
        loadingServices.value[provider.id] = false;
    }
}

function toggleService(service, providerId) {
    axios.patch(route('admin.api-settings.services.toggle', service.id), {}, { timeout: 15000 }).then(resp => {
        service.is_active = resp.data.is_active;
    });
}

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="API Settings — Admin" />
    <AdminLayout>
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Integration</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">API Settings</h1>
            </div>
            <button @click="openAdd" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-colors">
                <Plus class="w-3.5 h-3.5" />
                Add Provider
            </button>
        </div>

        <!-- Flash message -->
        <div v-if="$page.props.flash?.success" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl text-[13px] text-emerald-700 dark:text-emerald-400">
            <Check class="w-4 h-4 flex-shrink-0" />
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.errors?.import" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl text-[13px] text-red-700 dark:text-red-400">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            {{ $page.props.errors.import }}
        </div>

        <!-- Providers list -->
        <div v-if="providers.length === 0" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 py-16 flex flex-col items-center gap-3 text-center">
            <Key class="w-10 h-10 text-slate-300 dark:text-slate-700" />
            <p class="text-[13px] text-slate-400 dark:text-slate-600">No SMM providers configured yet.</p>
            <button @click="openAdd" class="flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-colors">
                <Plus class="w-3.5 h-3.5" />
                Add your first provider
            </button>
        </div>

        <div v-else class="space-y-3">
            <div v-for="provider in providers" :key="provider.id" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                <!-- Provider header row -->
                <div class="px-5 py-4 flex flex-wrap items-center gap-4">
                    <!-- Name + status -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500/20 to-blue-600/20 dark:from-sky-500/10 dark:to-blue-600/10 flex items-center justify-center flex-shrink-0">
                            <Key class="w-4 h-4 text-sky-500 dark:text-sky-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-slate-800 dark:text-white truncate">{{ provider.name }}</p>
                                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold', provider.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400']">
                                    <span :class="['w-1 h-1 rounded-full', provider.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                    {{ provider.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-600 truncate">{{ provider.base_url }}</p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-5 text-[12px]">
                        <div class="text-center">
                            <p class="font-bold text-slate-800 dark:text-white">{{ provider.services_count }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600">Services</p>
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-slate-800 dark:text-white">{{ provider.markup_value }}{{ provider.markup_type === 'percentage' ? '%' : '$' }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600">Markup</p>
                        </div>
                        <div v-if="provider.balance" class="text-center">
                            <p class="font-bold text-emerald-600 dark:text-emerald-400">{{ provider.balance }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-600">Balance</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Test connection -->
                        <button @click="testConnection(provider)" :disabled="testingProvider === provider.id"
                            class="flex items-center gap-1.5 h-8 px-3 text-[11px] font-semibold border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 rounded-xl transition-colors disabled:opacity-60">
                            <Loader2 v-if="testingProvider === provider.id" class="w-3 h-3 animate-spin" />
                            <Zap v-else class="w-3 h-3" />
                            Test
                        </button>

                        <!-- Import -->
                        <button @click="importServices(provider)" :disabled="importingProvider === provider.id"
                            class="flex items-center gap-1.5 h-8 px-3 text-[11px] font-semibold border border-sky-200 dark:border-sky-500/30 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-xl transition-colors disabled:opacity-60">
                            <Loader2 v-if="importingProvider === provider.id" class="w-3 h-3 animate-spin" />
                            <Download v-else class="w-3 h-3" />
                            Import
                        </button>

                        <!-- Recalculate markup -->
                        <button
                            v-if="provider.services_count > 0"
                            @click="recalculateMarkup(provider)"
                            :disabled="recalculating === provider.id"
                            class="flex items-center gap-1.5 h-8 px-3 text-[11px] font-semibold border border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-xl transition-colors disabled:opacity-60"
                            title="Recalculate all service prices with current markup"
                        >
                            <Loader2 v-if="recalculating === provider.id" class="w-3 h-3 animate-spin" />
                            <SlidersHorizontal v-else class="w-3 h-3" />
                            Reprice
                        </button>

                        <button @click="openEdit(provider)" class="p-2 text-slate-400 hover:text-sky-500 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button @click="toggleProvider(provider)" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg transition-colors">
                            <ToggleRight v-if="provider.is_active" class="w-4 h-4 text-emerald-500" />
                            <ToggleLeft v-else class="w-4 h-4" />
                        </button>
                        <button @click="deleteProvider(provider)" class="p-2 text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                        <button @click="toggleExpand(provider)" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg transition-colors">
                            <ChevronDown v-if="expandedProvider === provider.id" class="w-4 h-4" />
                            <ChevronRight v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Test result inline -->
                <div v-if="testResults[provider.id]" :class="['mx-5 mb-4 px-3 py-2.5 rounded-xl flex items-center gap-2 text-[12px] font-medium', testResults[provider.id].success ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400']">
                    <Check v-if="testResults[provider.id].success" class="w-3.5 h-3.5 flex-shrink-0" />
                    <AlertCircle v-else class="w-3.5 h-3.5 flex-shrink-0" />
                    {{ testResults[provider.id].message }}
                    <span v-if="testResults[provider.id].balance" class="ml-1 font-bold">Balance: {{ testResults[provider.id].balance }}</span>
                </div>

                <!-- Last synced -->
                <div v-if="provider.last_synced_at" class="px-5 pb-3 text-[11px] text-slate-400 dark:text-slate-600">
                    Last synced: {{ formatDate(provider.last_synced_at) }}
                </div>

                <!-- Expanded services sub-table -->
                <div v-if="expandedProvider === provider.id" class="border-t border-slate-100 dark:border-sky-500/8">
                    <div v-if="loadingServices[provider.id]" class="py-8 flex items-center justify-center gap-2 text-[13px] text-slate-400 dark:text-slate-600">
                        <Loader2 class="w-4 h-4 animate-spin" />
                        Loading services...
                    </div>
                    <div v-else-if="!providerServices[provider.id]?.length" class="py-8 text-center text-[13px] text-slate-400 dark:text-slate-600">
                        No services imported yet. Click "Import" to fetch from the provider.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-white/2">
                                    <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Service</th>
                                    <th class="text-left px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Category</th>
                                    <th class="text-left px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Cost</th>
                                    <th class="text-left px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Sell Price</th>
                                    <th class="text-left px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Min / Max</th>
                                    <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                                <tr v-for="svc in providerServices[provider.id]" :key="svc.id" class="hover:bg-slate-50 dark:hover:bg-white/2 transition-colors">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-700 dark:text-slate-300">{{ svc.name }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-600">#{{ svc.provider_service_code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ svc.category?.name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">${{ Number(svc.cost_price).toFixed(4) }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200">${{ Number(svc.selling_price).toFixed(4) }}</td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ svc.min_amount }} / {{ svc.max_amount }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <button @click="toggleService(svc, provider.id)" :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold transition-colors', svc.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20' : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10']">
                                            <span :class="['w-1 h-1 rounded-full', svc.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                            {{ svc.is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="px-5 py-3 border-t border-slate-100 dark:border-sky-500/8 text-[11px] text-slate-400 dark:text-slate-600">
                            {{ providerServices[provider.id].length }} services loaded
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Provider Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddModal = false" />
                <div class="relative w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8 sticky top-0 bg-white dark:bg-[#0d1e35] z-10">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Add SMM Provider</h3>
                        <button @click="showAddModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitAdd" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Provider Name</label>
                            <input v-model="addForm.name" type="text" placeholder="JingleSMM"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="addForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">API URL</label>
                            <input v-model="addForm.base_url" type="url" placeholder="https://jinglesmm.com/api/v2"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 font-mono" />
                            <p v-if="addForm.errors.base_url" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.base_url }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">API Key</label>
                            <input v-model="addForm.api_key" type="password" placeholder="Your API key (stored encrypted)"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 font-mono" />
                            <p class="mt-1 text-[10px] text-slate-400 dark:text-slate-600">Stored encrypted at rest. Never exposed to users.</p>
                            <p v-if="addForm.errors.api_key" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.api_key }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Markup Type</label>
                            <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-white/8">
                                <button type="button" @click="addForm.markup_type = 'percentage'" :class="['flex-1 h-9 text-[12px] font-semibold transition-colors', addForm.markup_type === 'percentage' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">
                                    % Percentage
                                </button>
                                <button type="button" @click="addForm.markup_type = 'fixed'" :class="['flex-1 h-9 text-[12px] font-semibold transition-colors', addForm.markup_type === 'fixed' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">
                                    $ Fixed
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                Markup Value
                                <span class="normal-case text-slate-400 ml-1">{{ addForm.markup_type === 'percentage' ? '(e.g. 50 = 50% added)' : '(flat USD per service unit)' }}</span>
                            </label>
                            <input v-model="addForm.markup_value" type="number" step="0.01" min="0" placeholder="0"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="addForm.errors.markup_value" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.markup_value }}</p>
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="addForm.is_active = !addForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', addForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', addForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active immediately</span>
                        </label>
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-sky-500/8">
                            <button type="button" @click="showAddModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="addForm.processing" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                <Plus class="w-3.5 h-3.5" />
                                Add Provider
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Provider Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false" />
                <div class="relative w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8 sticky top-0 bg-white dark:bg-[#0d1e35] z-10">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Edit {{ editTarget?.name }}</h3>
                        <button @click="showEditModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Provider Name</label>
                            <input v-model="editForm.name" type="text"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="editForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">API URL</label>
                            <input v-model="editForm.base_url" type="url"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 font-mono" />
                            <p v-if="editForm.errors.base_url" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.base_url }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">API Key <span class="normal-case text-slate-400">(leave blank to keep current)</span></label>
                            <input v-model="editForm.api_key" type="password" placeholder="Enter new key to replace..."
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 font-mono" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Markup Type</label>
                            <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-white/8">
                                <button type="button" @click="editForm.markup_type = 'percentage'" :class="['flex-1 h-9 text-[12px] font-semibold transition-colors', editForm.markup_type === 'percentage' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">
                                    % Percentage
                                </button>
                                <button type="button" @click="editForm.markup_type = 'fixed'" :class="['flex-1 h-9 text-[12px] font-semibold transition-colors', editForm.markup_type === 'fixed' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">
                                    $ Fixed
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Markup Value</label>
                            <input v-model="editForm.markup_value" type="number" step="0.01" min="0"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="editForm.errors.markup_value" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.markup_value }}</p>
                            <p class="mt-1 text-[10px] text-amber-600 dark:text-amber-400/80">
                                Changing markup will automatically recalculate all service selling prices on save.
                            </p>
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="editForm.is_active = !editForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', editForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', editForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active</span>
                        </label>
                        <div class="rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.05] px-3 py-2.5 text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Note:</span>
                            Disabling a provider will automatically hide all its services from users. Re-enabling restores them.
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-sky-500/8">
                            <button type="button" @click="showEditModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="editForm.processing" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                <Check class="w-3.5 h-3.5" />
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

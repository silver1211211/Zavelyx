<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, Grid3x3, Loader2, Pencil, Plus, Search, ShoppingCart, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    services:   { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
});

// ── UI state ──────────────────────────────────────────────────────────────
const searchQuery   = ref('');
const filterStatus  = ref('all');
const showAddModal  = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showClearModal  = ref(false);
const editTarget   = ref(null);
const deleteTarget = ref(null);

// ── Derived lists ─────────────────────────────────────────────────────────
const allServices = computed(() =>
    Array.isArray(props.services) ? props.services : (props.services?.data ?? [])
);

const filteredServices = computed(() => {
    let list = allServices.value;

    if (filterStatus.value === 'active')   list = list.filter(s => s.is_active);
    if (filterStatus.value === 'inactive') list = list.filter(s => !s.is_active);
    if (filterStatus.value === 'manual')   list = list.filter(s => !s.provider_id);
    if (filterStatus.value === 'imported') list = list.filter(s => !!s.provider_id);

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(s =>
            s.name?.toLowerCase().includes(q) ||
            s.category?.name?.toLowerCase().includes(q) ||
            s.provider?.name?.toLowerCase().includes(q)
        );
    }

    return list;
});

const counts = computed(() => ({
    all:      allServices.value.length,
    active:   allServices.value.filter(s => s.is_active).length,
    inactive: allServices.value.filter(s => !s.is_active).length,
    manual:   allServices.value.filter(s => !s.provider_id).length,
    imported: allServices.value.filter(s => !!s.provider_id).length,
}));

// ── Add form ──────────────────────────────────────────────────────────────
const addForm = useForm({
    name:          '',
    category_id:   '',
    type:          'smm',
    selling_price: '',
    cost_price:    '',
    min_amount:    100,
    max_amount:    100000,
    description:   '',
    is_active:     true,
});

function openAdd() {
    addForm.reset();
    addForm.type      = 'smm';
    addForm.is_active = true;
    addForm.min_amount = 100;
    addForm.max_amount = 100000;
    showAddModal.value = true;
}

function submitAdd() {
    addForm.post(route('admin.services.store'), {
        onSuccess: () => { showAddModal.value = false; addForm.reset(); },
    });
}

// ── Edit form ─────────────────────────────────────────────────────────────
const editForm = useForm({
    name:          '',
    category_id:   '',
    type:          'smm',
    selling_price: '',
    cost_price:    '',
    min_amount:    '',
    max_amount:    '',
    description:   '',
    is_active:     true,
});

function openEdit(service) {
    editTarget.value       = service;
    editForm.name          = service.name;
    editForm.category_id   = service.category_id ?? '';
    editForm.type          = service.type;
    editForm.selling_price = service.selling_price;
    editForm.cost_price    = service.cost_price;
    editForm.min_amount    = service.min_amount;
    editForm.max_amount    = service.max_amount;
    editForm.description   = service.metadata?.description ?? '';
    editForm.is_active     = service.is_active;
    showEditModal.value    = true;
}

function submitEdit() {
    editForm.put(route('admin.services.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

// ── Delete ────────────────────────────────────────────────────────────────
function confirmDelete(service) {
    deleteTarget.value    = service;
    showDeleteModal.value = true;
}

function submitDelete() {
    router.delete(route('admin.services.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; deleteTarget.value = null; },
    });
}

// ── Toggle status ─────────────────────────────────────────────────────────
function toggleService(service) {
    router.patch(route('admin.services.toggle', service.id), {}, { preserveScroll: true });
}

// ── Clear all ─────────────────────────────────────────────────────────────
function submitClear() {
    router.delete(route('admin.services.clear'), {
        preserveScroll: true,
        onSuccess: () => { showClearModal.value = false; },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function fmt(val) {
    return Number(val ?? 0).toFixed(4);
}
</script>

<template>
    <Head title="Services — Admin" />
    <AdminLayout>

        <!-- Page header -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Management</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Services</h1>
                <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">{{ counts.all }} total · {{ counts.active }} active</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="counts.all > 0"
                    @click="showClearModal = true"
                    class="h-9 px-4 text-[12px] font-semibold text-red-500 dark:text-red-400 border border-red-200 dark:border-red-500/25 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors"
                >
                    Clear All
                </button>
                <button
                    @click="openAdd"
                    class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-colors shadow-sm shadow-sky-500/30"
                >
                    <Plus class="w-3.5 h-3.5" />
                    Add Service
                </button>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="mb-5 flex items-center gap-2.5 px-4 py-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl text-[13px] text-emerald-700 dark:text-emerald-400">
            <Check class="w-4 h-4 flex-shrink-0" />
            {{ $page.props.flash.success }}
        </div>

        <!-- Filter tabs + search -->
        <div class="mb-4 flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
            <!-- Filter tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto">
                <button
                    v-for="tab in [
                        { key: 'all',      label: 'All',      count: counts.all },
                        { key: 'active',   label: 'Active',   count: counts.active },
                        { key: 'inactive', label: 'Inactive', count: counts.inactive },
                        { key: 'manual',   label: 'Manual',   count: counts.manual },
                        { key: 'imported', label: 'Imported', count: counts.imported },
                    ]"
                    :key="tab.key"
                    @click="filterStatus = tab.key"
                    :class="['flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12px] font-semibold transition-all',
                        filterStatus === tab.key
                            ? 'bg-sky-500 text-white shadow-sm'
                            : 'bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/10']"
                >
                    {{ tab.label }}
                    <span :class="['text-[10px] px-1.5 py-0.5 rounded-full font-bold', filterStatus === tab.key ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-400']">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Search -->
            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search by name, category, provider..."
                    class="w-full h-9 pl-9 pr-4 text-[13px] bg-white dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all"
                />
            </div>
        </div>

        <!-- Services table -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">

            <!-- Empty state -->
            <div v-if="filteredServices.length === 0" class="py-16 flex flex-col items-center gap-3 text-center px-6">
                <ShoppingCart class="w-10 h-10 text-slate-300 dark:text-slate-700" />
                <template v-if="counts.all === 0">
                    <p class="text-[14px] font-semibold text-slate-600 dark:text-slate-400">No services yet</p>
                    <p class="text-[12px] text-slate-400 dark:text-slate-600 max-w-xs">Add a manual service or import from a provider in API Settings.</p>
                    <button @click="openAdd" class="mt-1 flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-colors">
                        <Plus class="w-3 h-3" /> Add your first service
                    </button>
                </template>
                <template v-else>
                    <p class="text-[14px] font-semibold text-slate-600 dark:text-slate-400">No services match your filter</p>
                    <p class="text-[12px] text-slate-400 dark:text-slate-600">Try a different search term or filter tab.</p>
                </template>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                            <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Service</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Provider</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Cost</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Sell Price</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Min / Max</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                            <th class="px-5 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                        <tr v-for="service in filteredServices" :key="service.id" class="hover:bg-slate-50 dark:hover:bg-white/3 transition-colors">

                            <!-- Name + category -->
                            <td class="px-5 py-3.5 max-w-[280px]">
                                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ service.name }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ service.category?.name ?? '—' }}</p>
                            </td>

                            <!-- Provider badge -->
                            <td class="px-4 py-3.5">
                                <span v-if="service.provider" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                    {{ service.provider.name }}
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400">
                                    Manual
                                </span>
                            </td>

                            <!-- Cost -->
                            <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400 tabular-nums">${{ fmt(service.cost_price) }}</td>

                            <!-- Sell price -->
                            <td class="px-4 py-3.5 font-bold text-slate-800 dark:text-slate-200 tabular-nums">${{ fmt(service.selling_price) }}</td>

                            <!-- Min / Max -->
                            <td class="px-4 py-3.5 text-[12px] text-slate-500 dark:text-slate-400 tabular-nums whitespace-nowrap">
                                {{ Number(service.min_amount ?? 0).toLocaleString() }} / {{ Number(service.max_amount ?? 0).toLocaleString() }}
                            </td>

                            <!-- Status toggle -->
                            <td class="px-4 py-3.5">
                                <button
                                    @click="toggleService(service)"
                                    :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold transition-colors cursor-pointer',
                                        service.is_active
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20'
                                            : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10']"
                                >
                                    <span :class="['w-1.5 h-1.5 rounded-full', service.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                    {{ service.is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        @click="openEdit(service)"
                                        class="p-1.5 text-slate-400 hover:text-sky-500 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors"
                                        title="Edit service"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="confirmDelete(service)"
                                        class="p-1.5 text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors"
                                        title="Delete service"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Table footer -->
                <div class="px-5 py-3 border-t border-slate-100 dark:border-sky-500/8 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-600">
                    <span>Showing {{ filteredServices.length }} of {{ counts.all }} services</span>
                    <span v-if="searchQuery || filterStatus !== 'all'" class="italic">Filtered view</span>
                </div>
            </div>
        </div>

        <!-- ─── ADD SERVICE MODAL ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddModal = false" />
                <div class="relative w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl max-h-[90vh] overflow-y-auto">

                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8 sticky top-0 bg-white dark:bg-[#0d1e35] z-10">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Add Manual Service</h3>
                        <button @click="showAddModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAdd" class="p-6 space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Service Name *</label>
                            <input v-model="addForm.name" type="text" placeholder="e.g. Instagram Followers – High Quality"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="addForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.name }}</p>
                        </div>

                        <!-- Category + Type row -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Category</label>
                                <select v-model="addForm.category_id"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                                    <option value="">— None —</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Type *</label>
                                <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-white/8 h-9">
                                    <button type="button" @click="addForm.type = 'smm'" :class="['flex-1 text-[12px] font-semibold transition-colors', addForm.type === 'smm' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">SMM</button>
                                    <button type="button" @click="addForm.type = 'vtu'" :class="['flex-1 text-[12px] font-semibold transition-colors', addForm.type === 'vtu' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">VTU</button>
                                </div>
                            </div>
                        </div>

                        <!-- Prices row -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Selling Price (USD) *</label>
                                <input v-model="addForm.selling_price" type="number" step="0.0001" min="0" placeholder="0.0000"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                                <p v-if="addForm.errors.selling_price" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.selling_price }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Cost Price (USD)</label>
                                <input v-model="addForm.cost_price" type="number" step="0.0001" min="0" placeholder="Optional"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                        </div>

                        <!-- Min / Max row -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Min Order</label>
                                <input v-model="addForm.min_amount" type="number" min="1" placeholder="100"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Max Order</label>
                                <input v-model="addForm.max_amount" type="number" min="1" placeholder="100000"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                            <textarea v-model="addForm.description" rows="2" placeholder="Optional notes about this service..."
                                class="w-full px-3 py-2 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 resize-none" />
                        </div>

                        <!-- Active toggle -->
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="addForm.is_active = !addForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', addForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', addForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active (visible to users)</span>
                        </label>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-sky-500/8">
                            <button type="button" @click="showAddModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">Cancel</button>
                            <button type="submit" :disabled="addForm.processing" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                <Loader2 v-if="addForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                <Plus v-else class="w-3.5 h-3.5" />
                                Add Service
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── EDIT SERVICE MODAL ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false" />
                <div class="relative w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl max-h-[90vh] overflow-y-auto">

                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8 sticky top-0 bg-white dark:bg-[#0d1e35] z-10">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Edit Service</h3>
                        <button @click="showEditModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Service Name *</label>
                            <input v-model="editForm.name" type="text"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="editForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Category</label>
                                <select v-model="editForm.category_id"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30">
                                    <option value="">— None —</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Type</label>
                                <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-white/8 h-9">
                                    <button type="button" @click="editForm.type = 'smm'" :class="['flex-1 text-[12px] font-semibold transition-colors', editForm.type === 'smm' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">SMM</button>
                                    <button type="button" @click="editForm.type = 'vtu'" :class="['flex-1 text-[12px] font-semibold transition-colors', editForm.type === 'vtu' ? 'bg-sky-500 text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5']">VTU</button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Selling Price (USD) *</label>
                                <input v-model="editForm.selling_price" type="number" step="0.0001" min="0"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                                <p v-if="editForm.errors.selling_price" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.selling_price }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Cost Price (USD)</label>
                                <input v-model="editForm.cost_price" type="number" step="0.0001" min="0"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Min Order</label>
                                <input v-model="editForm.min_amount" type="number" min="1"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Max Order</label>
                                <input v-model="editForm.max_amount" type="number" min="1"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Description</label>
                            <textarea v-model="editForm.description" rows="2"
                                class="w-full px-3 py-2 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500/30 resize-none" />
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="editForm.is_active = !editForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', editForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', editForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active</span>
                        </label>

                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-sky-500/8">
                            <button type="button" @click="showEditModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">Cancel</button>
                            <button type="submit" :disabled="editForm.processing" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                <Loader2 v-if="editForm.processing" class="w-3.5 h-3.5 animate-spin" />
                                <Check v-else class="w-3.5 h-3.5" />
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── DELETE CONFIRM MODAL ──────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false" />
                <div class="relative w-full max-w-sm bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
                            <Trash2 class="w-5 h-5 text-red-500" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1">Delete service?</h3>
                            <p class="text-[12px] text-slate-500 dark:text-slate-400">
                                "<span class="font-semibold text-slate-700 dark:text-slate-300">{{ deleteTarget?.name }}</span>" will be permanently removed.
                                Existing orders linked to this service will not be affected.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-5">
                        <button @click="showDeleteModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">Cancel</button>
                        <button @click="submitDelete" class="h-9 px-4 text-[12px] font-semibold bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors">Delete</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── CLEAR ALL CONFIRM MODAL ───────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showClearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showClearModal = false" />
                <div class="relative w-full max-w-sm bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center flex-shrink-0">
                            <AlertCircle class="w-5 h-5 text-red-500" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1">Delete ALL services?</h3>
                            <p class="text-[12px] text-slate-500 dark:text-slate-400">
                                This will permanently delete all <span class="font-bold text-red-500">{{ counts.all }}</span> services.
                                This cannot be undone. Existing orders will not be affected.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-5">
                        <button @click="showClearModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">Cancel</button>
                        <button @click="submitClear" class="h-9 px-4 text-[12px] font-semibold bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors">Delete All {{ counts.all }} Services</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

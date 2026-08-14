<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Globe, Pencil, Plus, Star, Trash2, ToggleLeft, ToggleRight, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    currencies: { type: Array, default: () => [] },
});

// ── Modal state ─────────────────────────────────────────────────────────────
const showAddModal = ref(false);
const showEditModal = ref(false);
const editTarget = ref(null);

const addForm = useForm({
    code: '',
    name: '',
    symbol: '',
    exchange_rate: '',
    is_active: true,
    sort_order: 99,
});

const editForm = useForm({
    code: '',
    name: '',
    symbol: '',
    exchange_rate: '',
    is_active: true,
    sort_order: 0,
});

function openAdd() {
    addForm.reset();
    showAddModal.value = true;
}

function openEdit(currency) {
    editTarget.value = currency;
    editForm.code = currency.code;
    editForm.name = currency.name;
    editForm.symbol = currency.symbol;
    editForm.exchange_rate = currency.exchange_rate;
    editForm.is_active = currency.is_active;
    editForm.sort_order = currency.sort_order;
    showEditModal.value = true;
}

function submitAdd() {
    addForm.post(route('admin.website-settings.currencies.store'), {
        onSuccess: () => { showAddModal.value = false; addForm.reset(); },
    });
}

function submitEdit() {
    editForm.put(route('admin.website-settings.currencies.update', editTarget.value.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
}

function deleteCurrency(currency) {
    if (!confirm(`Delete ${currency.name} (${currency.code})?`)) return;
    router.delete(route('admin.website-settings.currencies.destroy', currency.id), { preserveScroll: true });
}

function toggleCurrency(currency) {
    router.patch(route('admin.website-settings.currencies.toggle', currency.id), {}, { preserveScroll: true });
}

function setDefault(currency) {
    router.patch(route('admin.website-settings.currencies.default', currency.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Website Settings — Admin" />
    <AdminLayout>
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Configuration</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Website Settings</h1>
            </div>
        </div>

        <!-- Currency Management Section -->
        <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <Globe class="w-4 h-4 text-sky-500" />
                    <h2 class="text-[14px] font-bold text-slate-800 dark:text-white">Currency Management</h2>
                    <span class="ml-2 text-[11px] font-semibold text-slate-400 dark:text-slate-600">{{ currencies.length }} currencies</span>
                </div>
                <button @click="openAdd" class="flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 text-white rounded-xl transition-colors">
                    <Plus class="w-3.5 h-3.5" />
                    Add Currency
                </button>
            </div>

            <div v-if="currencies.length === 0" class="py-14 flex flex-col items-center gap-3 text-center">
                <Globe class="w-10 h-10 text-slate-300 dark:text-slate-700" />
                <p class="text-[13px] text-slate-400 dark:text-slate-600">No currencies configured.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-sky-500/8 bg-slate-50 dark:bg-white/2">
                            <th class="text-left px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Currency</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Symbol</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Exchange Rate (per USD)</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Status</th>
                            <th class="text-left px-4 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Default</th>
                            <th class="text-right px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-sky-500/8">
                        <tr v-for="c in currencies" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-white/3 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500/20 to-blue-600/20 dark:from-sky-500/10 dark:to-blue-600/10 flex items-center justify-center text-[11px] font-black text-sky-600 dark:text-sky-400">
                                        {{ c.code }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ c.name }}</p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ c.code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-mono font-bold text-slate-700 dark:text-slate-300">{{ c.symbol }}</td>
                            <td class="px-4 py-4">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">1 USD = {{ Number(c.exchange_rate).toLocaleString() }} {{ c.code }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <button @click="toggleCurrency(c)" :disabled="c.is_default" class="flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed transition-opacity">
                                    <ToggleRight v-if="c.is_active" class="w-4 h-4 text-emerald-500" />
                                    <ToggleLeft v-else class="w-4 h-4 text-slate-400 dark:text-slate-600" />
                                    <span :class="['text-[11px] font-semibold', c.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-600']">
                                        {{ c.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </button>
                            </td>
                            <td class="px-4 py-4">
                                <button v-if="!c.is_default" @click="setDefault(c)" class="text-[11px] font-semibold text-slate-400 dark:text-slate-600 hover:text-sky-500 dark:hover:text-sky-400 transition-colors">
                                    Set default
                                </button>
                                <span v-else class="flex items-center gap-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                                    <Star class="w-3 h-3 fill-current" />
                                    Default
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit(c)" class="p-1.5 text-slate-400 hover:text-sky-500 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button @click="deleteCurrency(c)" :disabled="c.is_default" class="p-1.5 text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Exchange rate info -->
            <div class="px-5 py-3 bg-sky-50 dark:bg-sky-500/5 border-t border-sky-100 dark:border-sky-500/10">
                <p class="text-[11px] text-sky-600 dark:text-sky-400">
                    Exchange rates are used to convert USD prices for display. All balances and order amounts are stored in USD internally.
                    Active currencies appear in the user-facing currency switcher.
                </p>
            </div>
        </div>

        <!-- Add Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddModal = false" />
                <div class="relative w-full max-w-md bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Add Currency</h3>
                        <button @click="showAddModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitAdd" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                <input v-model="addForm.code" type="text" placeholder="USD" maxlength="10"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 uppercase" />
                                <p v-if="addForm.errors.code" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.code }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Symbol</label>
                                <input v-model="addForm.symbol" type="text" placeholder="$" maxlength="10"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                                <p v-if="addForm.errors.symbol" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.symbol }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                            <input v-model="addForm.name" type="text" placeholder="US Dollar"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="addForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Exchange Rate <span class="normal-case text-slate-400">(1 USD = ?)</span></label>
                            <input v-model="addForm.exchange_rate" type="number" step="0.000001" placeholder="1600"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="addForm.errors.exchange_rate" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.exchange_rate }}</p>
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="addForm.is_active = !addForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', addForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', addForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active (visible to users)</span>
                        </label>
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-sky-500/8">
                            <button type="button" @click="showAddModal = false" class="h-9 px-4 text-[12px] font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="addForm.processing" class="flex items-center gap-1.5 h-9 px-4 text-[12px] font-semibold bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                <Plus class="w-3.5 h-3.5" />
                                Add Currency
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false" />
                <div class="relative w-full max-w-md bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/15 shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-sky-500/8">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Edit {{ editTarget?.code }}</h3>
                        <button @click="showEditModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Code</label>
                                <input v-model="editForm.code" type="text" maxlength="10"
                                    :disabled="editTarget?.is_default"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 uppercase disabled:opacity-60" />
                                <p v-if="editForm.errors.code" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.code }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Symbol</label>
                                <input v-model="editForm.symbol" type="text" maxlength="10"
                                    class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                                <p v-if="editForm.errors.symbol" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.symbol }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Name</label>
                            <input v-model="editForm.name" type="text"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="editForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Exchange Rate <span class="normal-case text-slate-400">(1 USD = ?)</span></label>
                            <input v-model="editForm.exchange_rate" type="number" step="0.000001"
                                class="w-full h-9 px-3 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            <p v-if="editForm.errors.exchange_rate" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.exchange_rate }}</p>
                        </div>
                        <label v-if="!editTarget?.is_default" class="flex items-center gap-2.5 cursor-pointer">
                            <div @click="editForm.is_active = !editForm.is_active" :class="['relative w-9 h-5 rounded-full transition-colors', editForm.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', editForm.is_active ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300">Active</span>
                        </label>
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

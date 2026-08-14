<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle, Check, CheckCircle2, Loader2,
    Pencil, Phone, Plus, RefreshCw, Trash2, X, Zap,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { fetchTimeout } from '@/utils/fetchTimeout';

const props = defineProps({
    providers: { type: Array, default: () => [] },
});

const DRIVER_LABELS = {
    fivesim: '5SIM',
    smspva: 'SMSPVA',
    pvapins: 'PVAPins',
};

// ── Add/edit form ────────────────────────────────────────────────────────────
const showForm = ref(false);
const editing  = ref(null);

const form = useForm({
    name:           '',
    driver:         'fivesim',
    api_key:        '',
    base_url:       '',
    markup_percent: 20,
    priority:       1,
});

function openAdd() {
    editing.value = null;
    form.reset();
    form.driver = 'fivesim';
    showForm.value = true;
}

function openEdit(p) {
    editing.value = p;
    form.name           = p.name;
    form.driver         = p.driver;
    form.api_key        = '';
    form.base_url       = p.base_url ?? '';
    form.markup_percent = p.markup_percent;
    form.priority       = p.priority;
    showForm.value      = true;
}

function closeForm() { showForm.value = false; editing.value = null; form.reset(); }

function submitForm() {
    if (editing.value) {
        form.put(route('admin.number-providers.update', editing.value.id), {
            onSuccess: closeForm,
        });
    } else {
        form.post(route('admin.number-providers.store'), {
            onSuccess: closeForm,
        });
    }
}

function deleteProvider(p) {
    if (!confirm(`Delete provider "${p.name}"?`)) return;
    router.delete(route('admin.number-providers.destroy', p.id));
}

// ── Toggle ───────────────────────────────────────────────────────────────────
const togglingId = ref(null);
async function toggleProvider(p) {
    togglingId.value = p.id;
    try {
        await fetchTimeout(route('admin.number-providers.toggle', p.id), {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            },
        }, 15000);
        router.reload({ only: ['providers'] });
    } finally {
        togglingId.value = null;
    }
}

// ── Test connection ───────────────────────────────────────────────────────────
const testingId  = ref(null);
const testResult = ref({});

props.providers.forEach(p => {
    if (p.last_test_result) {
        testResult.value[p.id] = p.last_test_result;
    }
});

async function testConnection(p) {
    testingId.value = p.id;
    delete testResult.value[p.id];

    try {
        const res  = await fetchTimeout(route('admin.number-providers.test', p.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            },
        }, 20000);
        const data = await res.json();
        testResult.value = { ...testResult.value, [p.id]: data };
    } catch {
        testResult.value = { ...testResult.value, [p.id]: { ok: false, error: 'Request failed — check network.' } };
    } finally {
        testingId.value = null;
    }
}

// ── Sync ─────────────────────────────────────────────────────────────────────
const syncingId     = ref(null);
const syncResult    = ref({});

async function syncProvider(p) {
    syncingId.value = p.id;
    delete syncResult.value[p.id];

    try {
        const res  = await fetchTimeout(route('admin.number-providers.sync', p.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            },
        }, 600000);
        const data = await res.json();
        syncResult.value = { ...syncResult.value, [p.id]: data };
        if (data.ok) router.reload({ only: ['providers'] });
    } catch (e) {
        syncResult.value = {
            ...syncResult.value,
            [p.id]: {
                ok: false,
                error: e.name === 'AbortError'
                    ? 'Sync timed out before the provider finished. Please retry, or check the last synced time after a minute.'
                    : (e.message || 'Sync request failed.'),
            },
        };
    } finally {
        syncingId.value = null;
    }
}
</script>

<template>
    <Head title="Number Providers — Admin" />
    <AdminLayout>

        <div class="mb-6 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <Phone class="w-5 h-5 text-sky-500" />
                    Number Providers
                </h1>
                <p class="text-[12.5px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Manage SMS/OTP number providers and API credentials
                </p>
            </div>
            <button @click="openAdd"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95"
                style="background:linear-gradient(135deg,#0ea5e9,#6366f1)">
                <Plus class="w-4 h-4" />
                Add Provider
            </button>
        </div>

        <!-- Providers list -->
        <div class="space-y-3">
            <div v-if="providers.length === 0"
                class="rounded-2xl border border-dashed border-slate-200 dark:border-white/10 bg-white dark:bg-[#09111f] p-12 text-center">
                <Phone class="w-8 h-8 text-slate-300 dark:text-slate-700 mx-auto mb-3" :stroke-width="1.5" />
                <p class="text-[13px] text-slate-400 dark:text-slate-600">No providers configured yet. Add your first one.</p>
            </div>

            <div v-for="p in providers" :key="p.id"
                class="rounded-2xl border border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#09111f] p-5">

                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <p class="text-[14px] font-black text-slate-800 dark:text-white">{{ p.name }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full
                                bg-slate-100 dark:bg-white/[0.07] text-slate-500 dark:text-slate-400">
                                {{ DRIVER_LABELS[p.driver] ?? p.driver }}
                            </span>
                            <span v-if="!p.is_active"
                                class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full
                                    bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400">
                                Inactive
                            </span>
                        </div>
                        <p class="text-[11.5px] text-slate-400 dark:text-slate-400">
                            Markup: {{ p.markup_percent }}% · Priority: {{ p.priority }} ·
                            <span :class="p.has_api_key ? 'text-emerald-500' : 'text-rose-500'">
                                {{ p.has_api_key ? 'API key set' : 'No API key' }}
                            </span>
                        </p>

                        <!-- Sync result inline -->
                        <p v-if="syncResult[p.id]" class="mt-1 text-[11px]"
                            :class="syncResult[p.id].ok ? 'text-emerald-500' : 'text-rose-500'">
                            <template v-if="syncResult[p.id].ok">
                                Synced {{ syncResult[p.id].products }} products
                            </template>
                            <template v-else>
                                Sync failed: {{ syncResult[p.id].error }}
                            </template>
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0 flex-wrap justify-end">
                        <!-- Test result chip -->
                        <span v-if="testResult[p.id]"
                            :title="testResult[p.id].ok ? undefined : (testResult[p.id].error ?? 'Connection failed')"
                            :class="['text-[11px] font-bold px-2.5 py-1 rounded-full max-w-[200px] truncate',
                                testResult[p.id].ok
                                    ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                                    : 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 cursor-help']">
                            <template v-if="testResult[p.id].ok">
                                <CheckCircle2 class="w-3 h-3 inline -mt-0.5 mr-0.5" />
                                Balance: ${{ testResult[p.id].balance?.toFixed(2) }}
                            </template>
                            <template v-else>
                                <AlertCircle class="w-3 h-3 inline -mt-0.5 mr-0.5" />
                                {{ testResult[p.id].error ?? 'Failed' }}
                            </template>
                        </span>

                        <!-- Toggle active -->
                        <button @click="toggleProvider(p)" :disabled="togglingId === p.id"
                            class="px-3 py-1.5 rounded-xl text-[11.5px] font-bold transition-all"
                            :class="p.is_active
                                ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30'
                                : 'bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/[0.1]'">
                            <Loader2 v-if="togglingId === p.id" class="w-3 h-3 inline animate-spin" />
                            {{ p.is_active ? 'Active' : 'Inactive' }}
                        </button>

                        <!-- Sync -->
                        <button @click="syncProvider(p)" :disabled="syncingId === p.id"
                            :title="p.last_synced_at ? 'Last synced: ' + new Date(p.last_synced_at).toLocaleString() : 'Never synced'"
                            class="px-3 py-1.5 rounded-xl text-[11.5px] font-bold transition-all
                                bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400
                                hover:bg-violet-200 dark:hover:bg-violet-500/30 disabled:opacity-50">
                            <Loader2 v-if="syncingId === p.id" class="w-3 h-3 inline animate-spin" />
                            <RefreshCw v-else class="w-3 h-3 inline -mt-0.5 mr-0.5" />
                            <template v-if="syncingId !== p.id">Sync</template>
                        </button>

                        <!-- Test -->
                        <button @click="testConnection(p)" :disabled="testingId === p.id"
                            class="px-3 py-1.5 rounded-xl text-[11.5px] font-bold transition-all
                                bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-400
                                hover:bg-sky-200 dark:hover:bg-sky-500/30 disabled:opacity-50">
                            <Loader2 v-if="testingId === p.id" class="w-3 h-3 inline animate-spin" />
                            <Zap v-else class="w-3 h-3 inline -mt-0.5 mr-0.5" />
                            <template v-if="testingId !== p.id">Test</template>
                        </button>

                        <!-- Edit -->
                        <button @click="openEdit(p)"
                            class="w-8 h-8 flex items-center justify-center rounded-xl
                                bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400
                                hover:bg-slate-200 dark:hover:bg-white/[0.1] transition-all">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>

                        <!-- Delete -->
                        <button @click="deleteProvider(p)"
                            class="w-8 h-8 flex items-center justify-center rounded-xl
                                bg-rose-50 dark:bg-rose-500/[0.08] text-rose-500
                                hover:bg-rose-100 dark:hover:bg-rose-500/[0.15] transition-all">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 dark:text-slate-600">
                    Last synced: {{ p.last_synced_at ? new Date(p.last_synced_at).toLocaleString() : 'Never' }}
                    · Last tested: {{ p.last_tested_at ? new Date(p.last_tested_at).toLocaleString() : 'Never' }}
                    · Added {{ new Date(p.created_at).toLocaleDateString() }}
                </p>
            </div>
        </div>

        <!-- ── Add/Edit modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
            <div v-if="showForm"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                style="background:rgba(2,10,22,0.8);backdrop-filter:blur(16px)"
                @click.self="closeForm">

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    appear
                >
                <div class="w-full max-w-md rounded-3xl bg-white dark:bg-[#09111f]
                    border border-white/50 dark:border-white/[0.08]
                    shadow-[0_32px_80px_rgba(0,0,0,0.6)]">

                    <div class="px-6 py-5 border-b border-slate-100 dark:border-white/[0.06] flex items-center justify-between">
                        <h2 class="text-[15px] font-black text-slate-900 dark:text-white">
                            {{ editing ? 'Edit Provider' : 'Add Provider' }}
                        </h2>
                        <button @click="closeForm"
                            class="w-7 h-7 flex items-center justify-center rounded-full
                                bg-slate-100 dark:bg-white/[0.08] text-slate-500
                                hover:bg-slate-200 dark:hover:bg-white/[0.15] transition-colors">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. SMS Provider Main" required
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Driver — locked when editing to avoid data mismatch -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Driver</label>
                            <select v-model="form.driver" :disabled="!!editing"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all
                                    disabled:opacity-60 disabled:cursor-not-allowed">
                                <option value="fivesim">5SIM API</option>
                                <option value="smspva">SMSPVA API</option>
                                <option value="pvapins">PVAPins API</option>
                            </select>
                            <p v-if="editing" class="mt-1 text-[11px] text-slate-400 dark:text-slate-400">Driver cannot be changed after creation.</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">
                                API Key {{ editing ? '(leave blank to keep current)' : '' }}
                            </label>
                            <input v-model="form.api_key" type="password" autocomplete="new-password"
                                :placeholder="form.driver === 'pvapins' ? 'PVAPins API key…' : 'Bearer API key…'"
                                :required="!editing"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            <p v-if="form.errors.api_key" class="mt-1 text-[11px] text-rose-500">{{ form.errors.api_key }}</p>
                        </div>

                        <!-- Base URL — PVAPins only -->
                        <div v-if="form.driver === 'pvapins'">
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">
                                Base URL <span class="normal-case font-normal">(optional — leave blank for default)</span>
                            </label>
                            <input v-model="form.base_url" type="url"
                                placeholder="https://api.pvapins.com/user/api/"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            <p v-if="form.errors.base_url" class="mt-1 text-[11px] text-rose-500">{{ form.errors.base_url }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Markup %</label>
                                <input v-model="form.markup_percent" type="number" min="0" max="500" step="0.01" required
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-1.5">Priority</label>
                                <input v-model="form.priority" type="number" min="1" max="99" required
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-white/[0.1]
                                        bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-white text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all" />
                            </div>
                        </div>

                        <div v-if="form.driver === 'pvapins'"
                            class="rounded-xl border border-violet-200 dark:border-violet-500/20
                                bg-violet-50 dark:bg-violet-500/[0.06] p-3 text-[11.5px] text-violet-700 dark:text-violet-400">
                            PVAPins supports temporary OTP numbers only. Rental / long-duration numbers are not enabled.
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
                                {{ editing ? 'Save Changes' : 'Add Provider' }}
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

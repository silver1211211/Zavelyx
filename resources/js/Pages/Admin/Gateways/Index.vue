<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    gateways:         Array,
    supportedDrivers: Object,
    callbackUrls:     Object,
})

// ── Modals ────────────────────────────────────────────────────────────────────
const showAddModal  = ref(false)
const showEditModal = ref(false)
const editTarget    = ref(null)

// ── Flash messages ────────────────────────────────────────────────────────────
const flash = ref(null)

function setFlash(msg, type = 'success') {
    flash.value = { msg, type }
    setTimeout(() => { flash.value = null }, 4000)
}

// ── Add form ──────────────────────────────────────────────────────────────────
const addForm = useForm({
    driver:      '',
    name:        '',
    api_key:     '',
    secret_key:  '',
    fee_percent: '0',
    min_amount:  '5',
    max_amount:  '10000',
    sandbox:     false,
})

const selectedDriverMeta = computed(() =>
    addForm.driver ? (props.supportedDrivers[addForm.driver] ?? null) : null
)

function onDriverChange() {
    if (selectedDriverMeta.value) {
        addForm.name = selectedDriverMeta.value.name
    }
}

function submitAdd() {
    addForm.post(route('admin.gateways.store'), {
        onSuccess: () => {
            showAddModal.value = false
            addForm.reset()
        },
    })
}

// ── Edit form ─────────────────────────────────────────────────────────────────
const editForm = useForm({
    name:        '',
    api_key:     '',
    secret_key:  '',
    fee_percent: '0',
    min_amount:  '5',
    max_amount:  '10000',
    sandbox:     false,
})

function openEdit(gw) {
    editTarget.value = gw
    editForm.name        = gw.name
    editForm.api_key     = ''
    editForm.secret_key  = ''
    editForm.fee_percent = String(gw.fee_percent)
    editForm.min_amount  = String(gw.min_amount)
    editForm.max_amount  = String(gw.max_amount)
    editForm.sandbox     = gw.extra_config?.sandbox ?? false
    showEditModal.value  = true
}

function submitEdit() {
    editForm.put(route('admin.gateways.update', editTarget.value.id), {
        onSuccess: () => {
            showEditModal.value = false
            editTarget.value    = null
        },
    })
}

// ── Actions ───────────────────────────────────────────────────────────────────
function toggleGateway(gw) {
    router.patch(route('admin.gateways.toggle', gw.id), {}, { preserveScroll: true })
}

function setDefault(gw) {
    router.patch(route('admin.gateways.default', gw.id), {}, { preserveScroll: true })
}

function testGateway(gw) {
    router.post(route('admin.gateways.test', gw.id), {}, { preserveScroll: true })
}

function deleteGateway(gw) {
    if (!confirm(`Remove "${gw.name}"? This cannot be undone.`)) return
    router.delete(route('admin.gateways.destroy', gw.id), { preserveScroll: true })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const driverMeta = (driver) => props.supportedDrivers[driver] ?? null

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).catch(() => {})
}

const callbackUrl = (driver, type) => props.callbackUrls?.[driver]?.[type] ?? null

// Drivers available to add (not yet installed)
const availableDrivers = computed(() =>
    Object.entries(props.supportedDrivers).filter(([key]) =>
        !props.gateways.some(g => g.driver === key)
    )
)
</script>

<template>
    <Head title="Payment Gateways" />

    <AdminLayout>
        <!-- Flash -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="flash"
                :class="[
                    'fixed top-4 right-4 z-[100] flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-[13px] font-medium max-w-sm',
                    flash.type === 'success'
                        ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-500/30'
                        : 'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-500/30',
                ]">
                <span>{{ flash.msg }}</span>
            </div>
        </Transition>

        <div class="space-y-6 max-w-5xl mx-auto">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white">Payment Gateways</h1>
                    <p class="mt-0.5 text-[12.5px] text-slate-500">Configure and manage payment processors</p>
                </div>
                <button
                    v-if="availableDrivers.length > 0"
                    @click="showAddModal = true"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 active:scale-95 text-white text-[13px] font-semibold transition-all shadow-lg shadow-sky-500/25">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Gateway
                </button>
            </div>

            <!-- Installed gateways -->
            <div v-if="gateways.length > 0" class="space-y-3">
                <div
                    v-for="gw in gateways" :key="gw.id"
                    class="bg-white dark:bg-[#0c1829] rounded-2xl border border-slate-200/80 dark:border-white/[0.05] overflow-hidden shadow-sm dark:shadow-none">

                    <!-- Gateway header row -->
                    <div class="px-5 py-4 flex items-center gap-4">

                        <!-- Driver logo / icon -->
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 flex items-center justify-center shrink-0 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                            {{ gw.driver.slice(0, 2) }}
                        </div>

                        <!-- Name & driver -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-[14px] font-semibold text-slate-900 dark:text-white">{{ gw.name }}</p>
                                <span v-if="gw.is_default"
                                    class="px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wider rounded-md bg-sky-100 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 ring-1 ring-sky-200 dark:ring-sky-500/25">
                                    Default
                                </span>
                                <span v-if="driverMeta(gw.driver)?.live === false"
                                    class="px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wider rounded-md bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-500/20">
                                    Coming Soon
                                </span>
                            </div>
                            <p class="text-[11.5px] text-slate-400 mt-0.5">{{ driverMeta(gw.driver)?.desc ?? gw.driver }}</p>
                        </div>

                        <!-- Status toggle -->
                        <div class="flex items-center gap-3 shrink-0">
                            <!-- Active pill -->
                            <button
                                @click="toggleGateway(gw)"
                                :class="[
                                    'relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none',
                                    gw.is_active ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10',
                                ]">
                                <span :class="[
                                    'inline-block h-3.5 w-3.5 rounded-full bg-white shadow-sm transition-transform',
                                    gw.is_active ? 'translate-x-[18px]' : 'translate-x-[3px]',
                                ]"></span>
                            </button>
                            <span :class="[
                                'text-[11px] font-semibold w-16',
                                gw.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400',
                            ]">{{ gw.is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>

                    <!-- Stats row -->
                    <div class="px-5 pb-3 grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 dark:bg-white/[0.02] rounded-xl px-3 py-2">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Fee</p>
                            <p class="text-[13px] font-bold text-slate-800 dark:text-white mt-0.5">{{ gw.fee_percent }}%</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-white/[0.02] rounded-xl px-3 py-2">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Min</p>
                            <p class="text-[13px] font-bold text-slate-800 dark:text-white mt-0.5">${{ gw.min_amount.toFixed(2) }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-white/[0.02] rounded-xl px-3 py-2">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Max</p>
                            <p class="text-[13px] font-bold text-slate-800 dark:text-white mt-0.5">${{ Number(gw.max_amount).toLocaleString() }}</p>
                        </div>
                    </div>

                    <!-- Credential status + IPN URL -->
                    <div class="px-5 pb-3 space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span :class="[
                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-medium ring-1',
                                gw.has_api_key
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-emerald-200 dark:ring-emerald-500/20'
                                    : 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400 ring-red-200 dark:ring-red-500/20',
                            ]">
                                <span :class="['w-1.5 h-1.5 rounded-full', gw.has_api_key ? 'bg-emerald-400' : 'bg-red-400']"></span>
                                API Key {{ gw.has_api_key ? 'Set' : 'Missing' }}
                            </span>
                            <!-- Secret Key badge — only shown for drivers that require a separate secret -->
                            <span v-if="gw.requires_secret !== false" :class="[
                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-medium ring-1',
                                gw.has_secret_key
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-emerald-200 dark:ring-emerald-500/20'
                                    : 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400 ring-red-200 dark:ring-red-500/20',
                            ]">
                                <span :class="['w-1.5 h-1.5 rounded-full', gw.has_secret_key ? 'bg-emerald-400' : 'bg-red-400']"></span>
                                Secret Key {{ gw.has_secret_key ? 'Set' : 'Missing' }}
                            </span>
                            <span v-if="gw.extra_config?.sandbox"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-medium ring-1 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 ring-amber-200 dark:ring-amber-500/20">
                                Sandbox Mode
                            </span>
                        </div>

                        <!-- Callback URLs (copy) — shown only if driver has entries -->
                        <template v-if="callbackUrls?.[gw.driver]">
                            <div v-for="[type, label] in [['ipn','IPN Callback'],['success','Success URL'],['cancel','Cancel URL']]"
                                :key="type"
                                class="flex items-center gap-2">
                                <p class="text-[9.5px] text-slate-400 font-bold uppercase tracking-wide shrink-0 w-[74px]">{{ label }}</p>
                                <div class="flex items-center gap-1.5 flex-1 min-w-0 bg-slate-50 dark:bg-white/[0.03] rounded-lg px-2.5 py-1.5 ring-1 ring-slate-200/80 dark:ring-white/5">
                                    <p class="text-[10.5px] font-mono text-slate-600 dark:text-slate-400 truncate flex-1">{{ callbackUrl(gw.driver, type) }}</p>
                                    <button @click="copyToClipboard(callbackUrl(gw.driver, type))"
                                        class="shrink-0 text-slate-400 hover:text-sky-500 transition-colors p-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Action buttons -->
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-white/[0.04] flex items-center gap-2 flex-wrap">
                        <button
                            @click="openEdit(gw)"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.05] hover:bg-slate-200 dark:hover:bg-white/[0.08] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                            Edit
                        </button>

                        <button
                            v-if="!gw.is_default && gw.is_active"
                            @click="setDefault(gw)"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/[0.08] hover:bg-sky-100 dark:hover:bg-sky-500/[0.14] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                            Set Default
                        </button>

                        <button
                            @click="testGateway(gw)"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/[0.08] hover:bg-emerald-100 dark:hover:bg-emerald-500/[0.14] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                            </svg>
                            Test Connection
                        </button>

                        <button
                            v-if="!gw.is_default"
                            @click="deleteGateway(gw)"
                            class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/[0.08] hover:bg-red-100 dark:hover:bg-red-500/[0.14] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else
                class="bg-white dark:bg-[#0c1829] rounded-2xl border border-slate-200/80 dark:border-white/[0.05] p-12 text-center shadow-sm dark:shadow-none">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-sky-50 dark:bg-sky-500/10 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-800 dark:text-white mb-1">No gateways configured</p>
                <p class="text-[12.5px] text-slate-500 mb-5">Add a payment gateway to start accepting deposits.</p>
                <button
                    @click="showAddModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold transition-colors">
                    Add First Gateway
                </button>
            </div>

            <!-- Coming soon drivers -->
            <div v-if="Object.values(supportedDrivers).some(d => !d.live)" class="bg-white dark:bg-[#0c1829] rounded-2xl border border-slate-200/80 dark:border-white/[0.05] shadow-sm dark:shadow-none overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-white/[0.04]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-400 dark:text-slate-600">Coming Soon</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-px bg-slate-100 dark:bg-white/[0.03]">
                    <div v-for="[key, driver] in Object.entries(supportedDrivers).filter(([,d]) => !d.live)" :key="key"
                        class="bg-white dark:bg-[#0c1829] px-4 py-3.5 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-white/5 flex items-center justify-center text-[10px] font-bold text-slate-400 uppercase shrink-0">
                            {{ key.slice(0, 2) }}
                        </div>
                        <div>
                            <p class="text-[12.5px] font-medium text-slate-700 dark:text-slate-300">{{ driver.name }}</p>
                            <p class="text-[10.5px] text-slate-400 mt-0.5 line-clamp-1">{{ driver.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Add Gateway Modal ────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showAddModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showAddModal = false">

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100">
                        <form v-if="showAddModal"
                            @submit.prevent="submitAdd"
                            class="w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-3xl shadow-2xl overflow-hidden">

                            <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-white/[0.06] flex items-center justify-between">
                                <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Add Payment Gateway</h3>
                                <button type="button" @click="showAddModal = false"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                                <!-- Driver picker -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Gateway Driver</label>
                                    <select v-model="addForm.driver" @change="onDriverChange" required
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400">
                                        <option value="" disabled>Select a driver…</option>
                                        <option v-for="[key, d] in availableDrivers" :key="key" :value="key" :disabled="!d.live">
                                            {{ d.name }}{{ !d.live ? ' (Coming Soon)' : '' }}
                                        </option>
                                    </select>
                                    <p v-if="selectedDriverMeta" class="mt-1.5 text-[11px] text-slate-500">{{ selectedDriverMeta.desc }}</p>
                                    <p v-if="addForm.errors.driver" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.driver }}</p>
                                </div>

                                <!-- Display name -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Display Name</label>
                                    <input v-model="addForm.name" type="text" required placeholder="e.g. NOWPayments"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                    <p v-if="addForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.name }}</p>
                                </div>

                                <!-- API Key -->
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">API Key</label>
                                    <input v-model="addForm.api_key" type="password" placeholder="Paste your API key"
                                        autocomplete="new-password"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                    <p v-if="addForm.errors.api_key" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.api_key }}</p>
                                </div>

                                <!-- Secret Key (hidden for drivers that don't require it) -->
                                <div v-if="selectedDriverMeta?.requires_secret !== false">
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Secret Key</label>
                                    <input v-model="addForm.secret_key" type="password" placeholder="Paste your secret key"
                                        autocomplete="new-password"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                    <p v-if="addForm.errors.secret_key" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.secret_key }}</p>
                                </div>

                                <!-- Fee / Min / Max -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Fee %</label>
                                        <input v-model="addForm.fee_percent" type="number" step="0.01" min="0" max="100"
                                            class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                        <p v-if="addForm.errors.fee_percent" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.fee_percent }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Min ($)</label>
                                        <input v-model="addForm.min_amount" type="number" step="0.01" min="0.01"
                                            class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                        <p v-if="addForm.errors.min_amount" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.min_amount }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Max ($)</label>
                                        <input v-model="addForm.max_amount" type="number" step="1" min="1"
                                            class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                        <p v-if="addForm.errors.max_amount" class="mt-1 text-[11px] text-red-500">{{ addForm.errors.max_amount }}</p>
                                    </div>
                                </div>

                                <!-- Sandbox -->
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" v-model="addForm.sandbox" class="sr-only" />
                                        <div :class="[
                                            'w-9 h-5 rounded-full transition-colors',
                                            addForm.sandbox ? 'bg-amber-400' : 'bg-slate-200 dark:bg-white/10',
                                        ]"></div>
                                        <div :class="[
                                            'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform',
                                            addForm.sandbox ? 'translate-x-4' : '',
                                        ]"></div>
                                    </div>
                                    <div>
                                        <p class="text-[12.5px] font-medium text-slate-800 dark:text-slate-200">Sandbox / Test Mode</p>
                                        <p class="text-[11px] text-slate-500">Use test credentials — no real payments</p>
                                    </div>
                                </label>

                            </div>

                            <div class="px-6 py-4 border-t border-slate-100 dark:border-white/[0.06] flex items-center justify-end gap-3">
                                <button type="button" @click="showAddModal = false"
                                    class="px-4 py-2 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="addForm.processing"
                                    class="flex items-center gap-2 px-5 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white text-[13px] font-semibold transition-colors">
                                    <svg v-if="addForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Add Gateway
                                </button>
                            </div>
                        </form>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Edit Gateway Modal ───────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showEditModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showEditModal = false">

                    <form @submit.prevent="submitEdit"
                        class="w-full max-w-lg bg-white dark:bg-[#0d1e35] rounded-3xl shadow-2xl overflow-hidden">

                        <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-white/[0.06] flex items-center justify-between">
                            <div>
                                <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">Edit Gateway</h3>
                                <p class="text-[11.5px] text-slate-500 mt-0.5">{{ editTarget?.driver }}</p>
                            </div>
                            <button type="button" @click="showEditModal = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.06] transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                            <!-- Display name -->
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Display Name</label>
                                <input v-model="editForm.name" type="text" required
                                    class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                <p v-if="editForm.errors.name" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.name }}</p>
                            </div>

                            <!-- API Key -->
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">
                                    API Key
                                    <span class="normal-case font-normal text-slate-400">(leave blank to keep current)</span>
                                </label>
                                <input v-model="editForm.api_key" type="password" placeholder="New API key…"
                                    autocomplete="new-password"
                                    class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                <p v-if="editForm.errors.api_key" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.api_key }}</p>
                            </div>

                            <!-- Secret Key (hidden for drivers that don't require it) -->
                            <div v-if="driverMeta(editTarget?.driver)?.requires_secret !== false">
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">
                                    Secret Key
                                    <span class="normal-case font-normal text-slate-400">(leave blank to keep current)</span>
                                </label>
                                <input v-model="editForm.secret_key" type="password" placeholder="New secret key…"
                                    autocomplete="new-password"
                                    class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                <p v-if="editForm.errors.secret_key" class="mt-1 text-[11px] text-red-500">{{ editForm.errors.secret_key }}</p>
                            </div>

                            <!-- Fee / Min / Max -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Fee %</label>
                                    <input v-model="editForm.fee_percent" type="number" step="0.01" min="0" max="100"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Min ($)</label>
                                    <input v-model="editForm.min_amount" type="number" step="0.01" min="0.01"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1.5">Max ($)</label>
                                    <input v-model="editForm.max_amount" type="number" step="1" min="1"
                                        class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 text-[13px] text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400" />
                                </div>
                            </div>

                            <!-- Sandbox -->
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="editForm.sandbox" class="sr-only" />
                                    <div :class="[
                                        'w-9 h-5 rounded-full transition-colors',
                                        editForm.sandbox ? 'bg-amber-400' : 'bg-slate-200 dark:bg-white/10',
                                    ]"></div>
                                    <div :class="[
                                        'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform',
                                        editForm.sandbox ? 'translate-x-4' : '',
                                    ]"></div>
                                </div>
                                <div>
                                    <p class="text-[12.5px] font-medium text-slate-800 dark:text-slate-200">Sandbox / Test Mode</p>
                                    <p class="text-[11px] text-slate-500">Use test credentials — no real payments</p>
                                </div>
                            </label>

                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/[0.06] flex items-center justify-end gap-3">
                            <button type="button" @click="showEditModal = false"
                                class="px-4 py-2 rounded-xl text-[13px] font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="editForm.processing"
                                class="flex items-center gap-2 px-5 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white text-[13px] font-semibold transition-colors">
                                <svg v-if="editForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>

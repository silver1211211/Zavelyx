<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    ChevronRight,
    CreditCard,
    Eye,
    EyeOff,
    Info,
    Key,
    Lock,
    RefreshCw,
    Save,
    Shield,
    ToggleLeft,
    ToggleRight,
    Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    settings:    { type: Object, default: () => ({}) },
    callbackUrl: { type: String, default: '' },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// ── All supported coins (full list for admin UI) ───────────────────────────────
const ALL_COINS = [
    { code: 'btc',   label: 'Bitcoin',    emoji: '₿',  color: '#f7931a' },
    { code: 'eth',   label: 'Ethereum',   emoji: 'Ξ',  color: '#627eea' },
    { code: 'usdt',  label: 'USDT',       emoji: '₮',  color: '#26a17b' },
    { code: 'usdc',  label: 'USDC',       emoji: '◎',  color: '#2775ca' },
    { code: 'bnb',   label: 'BNB',        emoji: '🔶', color: '#f0b90b' },
    { code: 'ltc',   label: 'Litecoin',   emoji: 'Ł',  color: '#bfbbbb' },
    { code: 'trx',   label: 'TRON',       emoji: '⟐',  color: '#ef0027' },
    { code: 'sol',   label: 'Solana',     emoji: '◎',  color: '#9945ff' },
    { code: 'doge',  label: 'Dogecoin',   emoji: '🐕', color: '#c2a633' },
    { code: 'xrp',   label: 'XRP',        emoji: '✕',  color: '#0ecdd8' },
    { code: 'ada',   label: 'Cardano',    emoji: '₳',  color: '#0033ad' },
    { code: 'dot',   label: 'Polkadot',   emoji: '●',  color: '#e6007a' },
    { code: 'matic', label: 'Polygon',    emoji: '⬡',  color: '#8247e5' },
    { code: 'avax',  label: 'Avalanche',  emoji: '▲',  color: '#e84142' },
    { code: 'shib',  label: 'SHIB',       emoji: '🐕', color: '#ffa409' },
];

const form = useForm({
    api_key:         '',
    ipn_secret:      '',
    enabled:         props.settings.enabled        ?? true,
    sandbox:         props.settings.sandbox        ?? false,
    maintenance:     props.settings.maintenance    ?? false,
    min_deposit:     props.settings.min_deposit    ?? 5,
    max_deposit:     props.settings.max_deposit    ?? 10000,
    fee_percent:     props.settings.fee_percent    ?? 0,
    supported_coins: props.settings.supported_coins ?? ['btc', 'eth', 'usdt', 'usdc', 'bnb', 'ltc', 'trx', 'sol', 'doge', 'xrp'],
});

const showApiKey    = ref(false);
const showIpnSecret = ref(false);

function toggleCoin(code) {
    const idx = form.supported_coins.indexOf(code);
    if (idx === -1) {
        form.supported_coins = [...form.supported_coins, code];
    } else {
        if (form.supported_coins.length === 1) return; // keep at least one
        form.supported_coins = form.supported_coins.filter(c => c !== code);
    }
}

function selectAllCoins() {
    form.supported_coins = ALL_COINS.map(c => c.code);
}

function clearCoins() {
    form.supported_coins = ['usdt'];
}

function save() {
    form.post(route('admin.settings.payments.save'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Payment Settings — Admin" />
    <AdminLayout>

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Settings</p>
                <h1 class="text-xl font-black text-slate-900 dark:text-white">Payment Gateway</h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">Configure NOWPayments API, deposit limits, and supported currencies</p>
            </div>
            <button @click="save" :disabled="form.processing"
                class="sm:ml-auto flex items-center gap-2 h-10 px-5 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.35)">
                <RefreshCw v-if="form.processing" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                Save Settings
            </button>
        </div>

        <!-- Flash messages -->
        <div v-if="flash.success"
            class="mb-5 flex items-center gap-3 p-4 rounded-2xl
                bg-emerald-50 dark:bg-emerald-500/[0.08] border border-emerald-200 dark:border-emerald-500/20">
            <CheckCircle2 class="w-5 h-5 text-emerald-500 flex-shrink-0" />
            <p class="text-[13px] font-semibold text-emerald-700 dark:text-emerald-400">{{ flash.success }}</p>
        </div>
        <div v-if="flash.error"
            class="mb-5 flex items-center gap-3 p-4 rounded-2xl
                bg-rose-50 dark:bg-rose-500/[0.08] border border-rose-200 dark:border-rose-500/20">
            <AlertCircle class="w-5 h-5 text-rose-500 flex-shrink-0" />
            <p class="text-[13px] font-semibold text-rose-700 dark:text-rose-400">{{ flash.error }}</p>
        </div>

        <div class="space-y-5">

            <!-- ── Gateway toggles ──────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15 flex items-center justify-center flex-shrink-0">
                        <Shield class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white">Gateway Status</p>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400">Control payment gateway availability</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Enabled toggle -->
                    <button type="button" @click="form.enabled = !form.enabled"
                        :class="[
                            'flex items-center gap-3 p-4 rounded-xl border text-left transition-all',
                            form.enabled
                                ? 'bg-emerald-50 dark:bg-emerald-500/[0.08] border-emerald-200 dark:border-emerald-500/20'
                                : 'bg-slate-50 dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.07]',
                        ]">
                        <ToggleRight v-if="form.enabled" class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                        <ToggleLeft  v-else              class="w-5 h-5 text-slate-400 dark:text-slate-600 flex-shrink-0" />
                        <div>
                            <p :class="['text-[13px] font-bold', form.enabled ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300']">
                                Payments {{ form.enabled ? 'Enabled' : 'Disabled' }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Toggle NOWPayments gateway</p>
                        </div>
                    </button>

                    <!-- Sandbox toggle -->
                    <button type="button" @click="form.sandbox = !form.sandbox"
                        :class="[
                            'flex items-center gap-3 p-4 rounded-xl border text-left transition-all',
                            form.sandbox
                                ? 'bg-amber-50 dark:bg-amber-500/[0.08] border-amber-200 dark:border-amber-500/20'
                                : 'bg-slate-50 dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.07]',
                        ]">
                        <Wrench class="w-5 h-5 flex-shrink-0" :class="form.sandbox ? 'text-amber-500' : 'text-slate-400 dark:text-slate-600'" />
                        <div>
                            <p :class="['text-[13px] font-bold', form.sandbox ? 'text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300']">
                                {{ form.sandbox ? 'Sandbox Mode' : 'Production Mode' }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Use sandbox API endpoint</p>
                        </div>
                    </button>

                    <!-- Maintenance toggle -->
                    <button type="button" @click="form.maintenance = !form.maintenance"
                        :class="[
                            'flex items-center gap-3 p-4 rounded-xl border text-left transition-all',
                            form.maintenance
                                ? 'bg-rose-50 dark:bg-rose-500/[0.08] border-rose-200 dark:border-rose-500/20'
                                : 'bg-slate-50 dark:bg-white/[0.04] border-slate-200 dark:border-white/[0.07]',
                        ]">
                        <AlertCircle class="w-5 h-5 flex-shrink-0" :class="form.maintenance ? 'text-rose-500' : 'text-slate-400 dark:text-slate-600'" />
                        <div>
                            <p :class="['text-[13px] font-bold', form.maintenance ? 'text-rose-700 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300']">
                                {{ form.maintenance ? 'Maintenance Mode' : 'Deposits Open' }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">Pause deposits for users</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ── API Keys ─────────────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-violet-500/10 dark:bg-violet-500/15 flex items-center justify-center flex-shrink-0">
                        <Key class="w-4.5 h-4.5 text-violet-600 dark:text-violet-400" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white">API Credentials</p>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400">Stored encrypted in the database — leave blank to keep current</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- API Key -->
                    <div>
                        <label class="block text-[11.5px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            NOWPayments API Key
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.api_key"
                                :type="showApiKey ? 'text' : 'password'"
                                placeholder="Leave blank to keep current"
                                autocomplete="new-password"
                                class="w-full h-10 pl-3.5 pr-10 text-[13px] font-mono rounded-xl border transition-all
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-slate-200
                                    placeholder:text-slate-300 dark:placeholder:text-slate-700 placeholder:font-sans
                                    focus:outline-none focus:ring-2 focus:ring-violet-500/25 focus:border-violet-400 dark:focus:border-violet-500/40
                                    border-slate-200 dark:border-white/[0.07]"
                            />
                            <button type="button" @click="showApiKey = !showApiKey"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-400 transition-colors">
                                <EyeOff v-if="showApiKey" class="w-4 h-4" />
                                <Eye    v-else            class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="settings.has_api_key" class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-500 flex items-center gap-1">
                            <Lock class="w-3 h-3" /> API key is set
                        </p>
                        <p v-else class="mt-1 text-[11px] text-amber-500 flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> No API key configured
                        </p>
                    </div>

                    <!-- IPN Secret -->
                    <div>
                        <label class="block text-[11.5px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            IPN Secret Key
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.ipn_secret"
                                :type="showIpnSecret ? 'text' : 'password'"
                                placeholder="Leave blank to keep current"
                                autocomplete="new-password"
                                class="w-full h-10 pl-3.5 pr-10 text-[13px] font-mono rounded-xl border transition-all
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-slate-200
                                    placeholder:text-slate-300 dark:placeholder:text-slate-700 placeholder:font-sans
                                    focus:outline-none focus:ring-2 focus:ring-violet-500/25 focus:border-violet-400 dark:focus:border-violet-500/40
                                    border-slate-200 dark:border-white/[0.07]"
                            />
                            <button type="button" @click="showIpnSecret = !showIpnSecret"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-400 transition-colors">
                                <EyeOff v-if="showIpnSecret" class="w-4 h-4" />
                                <Eye    v-else               class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="settings.has_ipn_secret" class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-500 flex items-center gap-1">
                            <Lock class="w-3 h-3" /> IPN secret is set
                        </p>
                        <p v-else class="mt-1 text-[11px] text-amber-500 flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> No IPN secret configured
                        </p>
                    </div>
                </div>

                <!-- IPN callback URL -->
                <div class="mt-4 p-3.5 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]">
                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-600 mb-1.5">IPN Callback URL (set this in NOWPayments)</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-[11px] font-mono text-sky-600 dark:text-sky-400 truncate">{{ callbackUrl }}</code>
                    </div>
                </div>
            </div>

            <!-- ── Deposit limits ───────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-sky-500/10 dark:bg-sky-500/15 flex items-center justify-center flex-shrink-0">
                        <CreditCard class="w-4.5 h-4.5 text-sky-600 dark:text-sky-400" />
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-slate-900 dark:text-white">Deposit Limits & Fees</p>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400">Applied to all users during checkout</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Min deposit -->
                    <div>
                        <label class="block text-[11.5px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Minimum Deposit (USD)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-slate-400 dark:text-slate-600 pointer-events-none">$</span>
                            <input
                                v-model.number="form.min_deposit"
                                type="number" step="0.01" min="0.01"
                                class="w-full h-10 pl-8 pr-3.5 text-[13px] font-mono rounded-xl border transition-all
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-slate-200
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/25 focus:border-sky-400 dark:focus:border-sky-500/40
                                    border-slate-200 dark:border-white/[0.07]"
                            />
                        </div>
                        <p v-if="form.errors.min_deposit" class="mt-1 text-[11px] text-rose-500">{{ form.errors.min_deposit }}</p>
                    </div>

                    <!-- Max deposit -->
                    <div>
                        <label class="block text-[11.5px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Maximum Deposit (USD)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-slate-400 dark:text-slate-600 pointer-events-none">$</span>
                            <input
                                v-model.number="form.max_deposit"
                                type="number" step="1" min="1"
                                class="w-full h-10 pl-8 pr-3.5 text-[13px] font-mono rounded-xl border transition-all
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-slate-200
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/25 focus:border-sky-400 dark:focus:border-sky-500/40
                                    border-slate-200 dark:border-white/[0.07]"
                            />
                        </div>
                        <p v-if="form.errors.max_deposit" class="mt-1 text-[11px] text-rose-500">{{ form.errors.max_deposit }}</p>
                    </div>

                    <!-- Fee percent -->
                    <div>
                        <label class="block text-[11.5px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Deposit Fee (%)
                        </label>
                        <div class="relative">
                            <input
                                v-model.number="form.fee_percent"
                                type="number" step="0.01" min="0" max="100"
                                class="w-full h-10 pl-3.5 pr-8 text-[13px] font-mono rounded-xl border transition-all
                                    bg-slate-50 dark:bg-white/[0.04] text-slate-800 dark:text-slate-200
                                    focus:outline-none focus:ring-2 focus:ring-sky-500/25 focus:border-sky-400 dark:focus:border-sky-500/40
                                    border-slate-200 dark:border-white/[0.07]"
                            />
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-slate-400 dark:text-slate-600 pointer-events-none">%</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-600">Set 0 for no fee</p>
                        <p v-if="form.errors.fee_percent" class="mt-1 text-[11px] text-rose-500">{{ form.errors.fee_percent }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Supported coins ─────────────────────────────────────────── -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 dark:bg-amber-500/15 flex items-center justify-center flex-shrink-0">
                            <CreditCard class="w-4.5 h-4.5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-[14px] font-bold text-slate-900 dark:text-white">Supported Cryptocurrencies</p>
                            <p class="text-[12px] text-slate-400 dark:text-slate-400">
                                {{ form.supported_coins.length }} of {{ ALL_COINS.length }} enabled
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="selectAllCoins"
                            class="h-8 px-3 text-[11.5px] font-semibold rounded-lg
                                bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400
                                border border-sky-200 dark:border-sky-500/20
                                hover:bg-sky-100 dark:hover:bg-sky-500/20 transition-colors">
                            All
                        </button>
                        <button type="button" @click="clearCoins"
                            class="h-8 px-3 text-[11.5px] font-semibold rounded-lg
                                bg-slate-50 dark:bg-white/[0.04] text-slate-500 dark:text-slate-400
                                border border-slate-200 dark:border-white/[0.07]
                                hover:bg-slate-100 dark:hover:bg-white/[0.07] transition-colors">
                            Reset
                        </button>
                    </div>
                </div>

                <p v-if="form.errors.supported_coins" class="mb-3 text-[12px] text-rose-500 flex items-center gap-1">
                    <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.supported_coins }}
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                    <button
                        v-for="coin in ALL_COINS"
                        :key="coin.code"
                        type="button"
                        @click="toggleCoin(coin.code)"
                        :class="[
                            'relative flex items-center gap-2.5 p-3 rounded-xl border transition-all text-left',
                            form.supported_coins.includes(coin.code)
                                ? 'border-transparent text-white shadow-md'
                                : 'bg-slate-50 dark:bg-white/[0.03] border-slate-200 dark:border-white/[0.07] text-slate-500 dark:text-slate-400 opacity-50',
                        ]"
                        :style="form.supported_coins.includes(coin.code)
                            ? `background: linear-gradient(135deg, ${coin.color}dd, ${coin.color}99); box-shadow: 0 4px 16px ${coin.color}30`
                            : ''"
                    >
                        <span class="text-[17px] leading-none flex-shrink-0">{{ coin.emoji }}</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase leading-tight">{{ coin.code }}</p>
                            <p class="text-[10px] truncate leading-tight opacity-80">{{ coin.label }}</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ── Save footer ──────────────────────────────────────────────── -->
            <div class="flex items-center justify-between p-4 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12">
                <p class="text-[12px] text-slate-400 dark:text-slate-600 flex items-center gap-1.5">
                    <Info class="w-3.5 h-3.5 flex-shrink-0" />
                    Changes take effect immediately. API keys are encrypted at rest.
                </p>
                <button @click="save" :disabled="form.processing"
                    class="flex items-center gap-2 h-10 px-6 rounded-xl text-[13px] font-bold text-white transition-all active:scale-95 disabled:opacity-60"
                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 20px rgba(14,165,233,0.35)">
                    <RefreshCw v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4" />
                    Save Settings
                </button>
            </div>

        </div>
    </AdminLayout>
</template>

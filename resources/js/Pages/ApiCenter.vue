<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle, BarChart3, Book, Check, ChevronDown, ChevronRight,
    Code2, Copy, Eye, EyeOff, Globe, Key, Layers, Loader2,
    Lock, MessageSquare, Phone, RefreshCw, Shield, Terminal,
    TrendingUp, Webhook, Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    apiKey:         { type: String,  default: null },
    tokenCreatedAt: { type: String,  default: null },
    requestCount:   { type: Number,  default: 0 },
    lastUsed:       { type: String,  default: null },
});

const page     = usePage();
const newKey   = computed(() => page.props.flash?.newApiKey ?? null);

const showKey      = ref(false);
const regenerating = ref(false);
const copied       = ref('');
const activePage   = ref('quickstart');
const activeLang   = ref('curl');
const expandedEp   = ref(null);
const expandedFlow = ref(null);

// ── Accent color (reads CSS variable set by theme) ────────────────────────────
const accent = computed(() => {
    try {
        return getComputedStyle(document.documentElement)
            .getPropertyValue('--color-primary').trim() || '#0ea5e9';
    } catch { return '#0ea5e9'; }
});

// ── Sidebar navigation ────────────────────────────────────────────────────────
const NAV = [
    {
        group: 'GETTING STARTED',
        items: [
            { key: 'quickstart',    label: 'Quick Start',      icon: Zap },
            { key: 'auth',         label: 'Authentication',    icon: Lock },
            { key: 'key',          label: 'API Key',           icon: Key },
        ],
    },
    {
        group: 'WORKFLOWS',
        items: [
            { key: 'smm',          label: 'SMM Orders',        icon: TrendingUp },
            { key: 'sms',          label: 'SMS / OTP Numbers', icon: Phone },
            { key: 'balance',      label: 'Balance & Wallet',  icon: Layers },
            { key: 'webhooks',     label: 'Webhooks',          icon: Webhook },
        ],
    },
    {
        group: 'REFERENCE',
        items: [
            { key: 'endpoints',    label: 'All Endpoints',     icon: Globe },
            { key: 'errors',       label: 'Error Codes',       icon: AlertTriangle },
        ],
    },
];

// ── Language tabs ─────────────────────────────────────────────────────────────
const LANGS = [
    { key: 'curl',   label: 'cURL' },
    { key: 'js',     label: 'JavaScript' },
    { key: 'php',    label: 'PHP' },
    { key: 'python', label: 'Python' },
];

// ── Code examples per endpoint × language ────────────────────────────────────
const baseUrl = computed(() => { try { return window.location.origin; } catch { return ''; } });
const B = computed(() => baseUrl.value);

const codeExamples = computed(() => ({
    balance: {
        curl:   `curl -X GET "${B.value}/api/v1/balance" \\\n  -H "Authorization: Bearer YOUR_API_KEY" \\\n  -H "Accept: application/json"`,
        js:     `const res = await fetch("${B.value}/api/v1/balance", {\n  headers: {\n    "Authorization": "Bearer YOUR_API_KEY",\n    "Accept": "application/json"\n  }\n});\nconst data = await res.json();\nconsole.log(data.balance); // "100.00"`,
        php:    `<?php\n$ch = curl_init("${B.value}/api/v1/balance");\ncurl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\ncurl_setopt($ch, CURLOPT_HTTPHEADER, [\n    "Authorization: Bearer YOUR_API_KEY",\n    "Accept: application/json"\n]);\n$response = json_decode(curl_exec($ch));\necho $response->balance; // "100.00"`,
        python: `import requests\n\nheaders = {\n    "Authorization": "Bearer YOUR_API_KEY",\n    "Accept": "application/json"\n}\n\nres = requests.get("${B.value}/api/v1/balance", headers=headers)\nprint(res.json()["balance"])  # "100.00"`,
    },
    services: {
        curl:   `curl -X GET "${B.value}/api/v1/services" \\\n  -H "Authorization: Bearer YOUR_API_KEY"`,
        js:     `const res = await fetch("${B.value}/api/v1/services", {\n  headers: { "Authorization": "Bearer YOUR_API_KEY" }\n});\nconst { services } = await res.json();\n// services[0] → { id, name, category, min, max, price }`,
        php:    `<?php\n$ch = curl_init("${B.value}/api/v1/services");\ncurl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\ncurl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer YOUR_API_KEY"]);\n$data = json_decode(curl_exec($ch));\nforeach ($data->services as $svc) {\n    echo $svc->name . ' — $' . $svc->price . PHP_EOL;\n}`,
        python: `import requests\n\nres = requests.get(\n    "${B.value}/api/v1/services",\n    headers={"Authorization": "Bearer YOUR_API_KEY"}\n)\nfor svc in res.json()["services"]:\n    print(svc["name"], svc["price"])`,
    },
    placeOrder: {
        curl:   `curl -X POST "${B.value}/api/v1/orders" \\\n  -H "Authorization: Bearer YOUR_API_KEY" \\\n  -H "Content-Type: application/json" \\\n  -d '{"service": 1, "link": "https://instagram.com/yourpage", "quantity": 1000}'`,
        js:     `const res = await fetch("${B.value}/api/v1/orders", {\n  method: "POST",\n  headers: {\n    "Authorization": "Bearer YOUR_API_KEY",\n    "Content-Type": "application/json"\n  },\n  body: JSON.stringify({\n    service: 1,\n    link: "https://instagram.com/yourpage",\n    quantity: 1000\n  })\n});\nconst { order } = await res.json();\nconsole.log(order); // 12345`,
        php:    `<?php\n$ch = curl_init("${B.value}/api/v1/orders");\ncurl_setopt_array($ch, [\n    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_POST => true,\n    CURLOPT_POSTFIELDS => json_encode([\n        "service"  => 1,\n        "link"     => "https://instagram.com/yourpage",\n        "quantity" => 1000\n    ]),\n    CURLOPT_HTTPHEADER => [\n        "Authorization: Bearer YOUR_API_KEY",\n        "Content-Type: application/json"\n    ]\n]);\n$res = json_decode(curl_exec($ch));\necho $res->order; // 12345`,
        python: `import requests\n\nres = requests.post(\n    "${B.value}/api/v1/orders",\n    headers={"Authorization": "Bearer YOUR_API_KEY"},\n    json={"service": 1, "link": "https://instagram.com/yourpage", "quantity": 1000}\n)\nprint(res.json()["order"])  # 12345`,
    },
    orderStatus: {
        curl:   `curl -X GET "${B.value}/api/v1/orders/12345" \\\n  -H "Authorization: Bearer YOUR_API_KEY"`,
        js:     `const res = await fetch("${B.value}/api/v1/orders/12345", {\n  headers: { "Authorization": "Bearer YOUR_API_KEY" }\n});\nconst data = await res.json();\n// { order: 12345, status: "in_progress", remains: 200 }`,
        php:    `<?php\n$ch = curl_init("${B.value}/api/v1/orders/12345");\ncurl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\ncurl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer YOUR_API_KEY"]);\n$res = json_decode(curl_exec($ch));\necho $res->status; // "in_progress"`,
        python: `import requests\n\nres = requests.get(\n    "${B.value}/api/v1/orders/12345",\n    headers={"Authorization": "Bearer YOUR_API_KEY"}\n)\nprint(res.json()["status"])  # "in_progress"`,
    },
}));

function codeFor(key) { return codeExamples.value[key]?.[activeLang.value] ?? ''; }

// ── All endpoints ─────────────────────────────────────────────────────────────
const endpoints = [
    { method: 'GET',  path: '/api/v1/balance',       label: 'Account Balance',  desc: 'Returns current wallet balance.', params: [], response: `{\n  "balance": "100.00",\n  "currency": "USD"\n}` },
    { method: 'GET',  path: '/api/v1/services',      label: 'List Services',    desc: 'All active SMM services with pricing.', params: [], response: `{\n  "services": [\n    { "id": 1, "name": "Instagram Followers", "category": "Instagram", "min": 100, "max": 100000, "price": "0.90" }\n  ]\n}` },
    { method: 'POST', path: '/api/v1/orders',        label: 'Place Order',      desc: 'Create a new SMM order.', params: [
        { name: 'service',  type: 'integer', desc: 'Service ID from /services', required: true },
        { name: 'link',     type: 'string',  desc: 'Target URL (profile, post)', required: true },
        { name: 'quantity', type: 'integer', desc: 'Number of units', required: true },
    ], response: `{\n  "order": 12345,\n  "status": "pending",\n  "charge": "0.90"\n}` },
    { method: 'GET',  path: '/api/v1/orders/{id}',   label: 'Order Status',     desc: 'Status and progress of an order.', params: [
        { name: 'id', type: 'integer', desc: 'Order ID', required: true },
    ], response: `{\n  "order": 12345,\n  "status": "in_progress",\n  "start_count": 500,\n  "remains": 200\n}` },
    { method: 'GET',  path: '/api/v1/transactions',  label: 'Transactions',     desc: 'Paginated wallet transaction history.', params: [
        { name: 'page', type: 'integer', desc: 'Page number', required: false },
    ], response: `{\n  "data": [ { "type": "deposit", "amount": "10.00", "balance_after": "110.00" } ],\n  "total": 42\n}` },
    { method: 'GET',  path: '/api/v1/wallet',        label: 'Wallet Info',      desc: 'Current wallet details.', params: [], response: `{\n  "balance": "100.00",\n  "currency": "USD",\n  "updated_at": "2025-01-01T00:00:00Z"\n}` },
];

const methodColor = m => ({
    GET:    'bg-emerald-500/10 text-emerald-500 border border-emerald-500/25',
    POST:   'bg-sky-500/10 text-sky-500 border border-sky-500/25',
    DELETE: 'bg-rose-500/10 text-rose-500 border border-rose-500/25',
    PATCH:  'bg-amber-500/10 text-amber-500 border border-amber-500/25',
}[m] ?? 'bg-slate-500/10 text-slate-500');

// ── Error codes ───────────────────────────────────────────────────────────────
const errors = [
    { code: 401, label: 'Unauthorized',       desc: 'Missing or invalid API key.' },
    { code: 403, label: 'Forbidden',           desc: 'Your account lacks permission for this action.' },
    { code: 404, label: 'Not Found',           desc: 'The resource does not exist.' },
    { code: 422, label: 'Validation Error',    desc: 'Required parameters missing or invalid.' },
    { code: 429, label: 'Too Many Requests',   desc: 'Rate limit exceeded. Back off and retry.' },
    { code: 500, label: 'Server Error',        desc: 'Something went wrong on our side.' },
];

// ── Helpers ───────────────────────────────────────────────────────────────────
function regenerate() {
    if (regenerating.value) return;
    if (!confirm('Regenerate your API key? The old key will stop working immediately.')) return;
    regenerating.value = true;
    router.post(route('api-center.regenerate'), {}, { onFinish: () => { regenerating.value = false; showKey.value = true; } });
}
function copy(text, key) {
    navigator.clipboard.writeText(text).then(() => { copied.value = key; setTimeout(() => { copied.value = ''; }, 2000); });
}
function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}
function timeAgo(str) {
    if (!str) return 'Never';
    const d = Math.floor((Date.now() - new Date(str)) / 1000);
    if (d < 60)    return 'Just now';
    if (d < 3600)  return `${Math.floor(d/60)} min ago`;
    if (d < 86400) return `${Math.floor(d/3600)}h ago`;
    return `${Math.floor(d/86400)}d ago`;
}
const displayedKey = computed(() => page.props.flash?.newApiKey ?? props.apiKey ?? null);
</script>

<template>
    <Head title="API Center" />
    <AuthenticatedLayout>

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(14,165,233,0.15), rgba(99,102,241,0.15))">
                        <Terminal class="w-4 h-4 text-sky-500" />
                    </div>
                    API Center
                </h1>
                <p class="text-[13px] text-slate-400 dark:text-slate-400 mt-0.5">Integrate NexaHub into your app. REST API · Bearer token auth · JSON responses.</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-[#0d1e35] rounded-xl border border-slate-200 dark:border-sky-500/12 shrink-0">
                <Globe class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" />
                <code class="text-[11.5px] font-mono font-semibold text-slate-600 dark:text-slate-300">{{ baseUrl }}/api/v1</code>
                <button @click="copy(baseUrl + '/api/v1', 'base')" class="text-slate-400 hover:text-sky-500 transition-colors ml-1">
                    <component :is="copied === 'base' ? Check : Copy" class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <!-- New key flash banner -->
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="page.props.flash?.newApiKey" class="mb-5 p-4 rounded-2xl border border-emerald-500/30" style="background: rgba(16,185,129,0.06)">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-500/15">
                        <Key class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400 mb-1">New API Key Generated — copy it now</p>
                        <p class="text-[11.5px] text-emerald-600/70 dark:text-emerald-500/70 mb-3">This key will only be shown once. Store it securely.</p>
                        <div class="flex items-center gap-2 p-3 bg-black/5 dark:bg-black/20 rounded-xl border border-emerald-500/20">
                            <code class="flex-1 text-[12px] font-mono text-slate-700 dark:text-slate-200 break-all">{{ page.props.flash.newApiKey }}</code>
                            <button @click="copy(page.props.flash.newApiKey, 'new')"
                                class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all active:scale-95"
                                :class="copied === 'new' ? 'bg-emerald-500 text-white' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25'">
                                <component :is="copied === 'new' ? Check : Copy" class="w-3.5 h-3.5" />
                                {{ copied === 'new' ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

            <!-- ── Sidebar ──────────────────────────────────────────────────── -->
            <div class="lg:col-span-1 space-y-3">

                <!-- Navigation -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-2">
                    <template v-for="group in NAV" :key="group.group">
                        <p class="px-3 pt-3 pb-1 text-[9px] font-black tracking-[0.16em] text-slate-400 dark:text-slate-600">{{ group.group }}</p>
                        <button v-for="item in group.items" :key="item.key"
                            @click="activePage = item.key"
                            :class="['w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-[12.5px] font-medium transition-all mb-0.5',
                                activePage === item.key
                                    ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5']">
                            <component :is="item.icon" class="w-3.5 h-3.5 flex-shrink-0"
                                :class="activePage === item.key ? 'text-sky-500' : 'text-slate-400 dark:text-slate-400'" />
                            {{ item.label }}
                            <ChevronRight v-if="activePage !== item.key" class="ml-auto w-3 h-3 text-slate-300 dark:text-slate-700" />
                        </button>
                    </template>
                </div>

                <!-- Usage stats -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4 space-y-2.5">
                    <p class="text-[9px] font-black tracking-[0.16em] text-slate-400 dark:text-slate-600">USAGE STATS</p>
                    <div v-for="row in [
                        { label: 'Total requests', value: requestCount.toLocaleString(), color: '' },
                        { label: 'Last used',      value: timeAgo(lastUsed),           color: '' },
                        { label: 'Rate limit',     value: '500 / hr',                  color: 'text-emerald-500' },
                        { label: 'Status',         value: apiKey ? 'Active' : 'Inactive', color: apiKey ? 'text-emerald-500' : 'text-slate-400' },
                    ]" :key="row.label" class="flex justify-between text-[11.5px]">
                        <span class="text-slate-400 dark:text-slate-600">{{ row.label }}</span>
                        <span :class="['font-bold text-slate-700 dark:text-slate-300', row.color]">{{ row.value }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Main content ─────────────────────────────────────────────── -->
            <div class="lg:col-span-3 space-y-5">

                <!-- Language tab bar (shown on pages with code) -->
                <div v-if="['quickstart','smm','sms','balance','webhooks'].includes(activePage)"
                    class="flex items-center gap-1 p-1 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 w-fit">
                    <button v-for="lang in LANGS" :key="lang.key" @click="activeLang = lang.key"
                        :class="['px-4 py-1.5 rounded-xl text-[12px] font-semibold transition-all',
                            activeLang === lang.key ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']">
                        {{ lang.label }}
                    </button>
                </div>

                <!-- ════ QUICK START ════ -->
                <div v-if="activePage === 'quickstart'" class="space-y-5">
                    <!-- Steps -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6 space-y-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Zap class="w-4 h-4 text-sky-500" /> Quick Start
                        </h3>

                        <div v-for="(step, i) in [
                            { title: 'Generate your API key', desc: 'Go to the API Key section in the sidebar and click Generate.' },
                            { title: 'Add the Authorization header', code: `Authorization: Bearer YOUR_API_KEY`, codeKey: 'header' },
                            { title: 'Fetch your balance', codeKey: 'balance' },
                            { title: 'Browse services and place an order', codeKey: 'placeOrder' },
                        ]" :key="i" class="flex gap-4">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-7 h-7 rounded-xl text-white text-[11px] font-black flex items-center justify-center flex-shrink-0"
                                    style="background: linear-gradient(135deg, #0ea5e9, #6366f1)">{{ i + 1 }}</div>
                                <div v-if="i < 3" class="w-px flex-1 mt-2 bg-slate-100 dark:bg-white/[0.06] min-h-[24px]" />
                            </div>
                            <div class="flex-1 pb-2">
                                <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 mb-1.5">{{ step.title }}</p>
                                <p v-if="step.desc" class="text-[12px] text-slate-400 dark:text-slate-400">{{ step.desc }}</p>
                                <div v-if="step.code || step.codeKey" class="relative mt-2">
                                    <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                        <pre class="text-[12px] font-mono text-emerald-300 whitespace-pre-wrap">{{ step.code ?? codeFor(step.codeKey) }}</pre>
                                    </div>
                                    <button @click="copy(step.code ?? codeFor(step.codeKey), 'step-' + i)"
                                        class="absolute right-2 top-2 w-7 h-7 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white/60 hover:text-white transition-colors">
                                        <component :is="copied === 'step-' + i ? Check : Copy" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats cards -->
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="c in [
                            { label: 'API Calls',    value: requestCount.toLocaleString(), icon: Zap,       color: 'text-sky-500',    bg: 'rgba(14,165,233,0.08)' },
                            { label: 'Rate Limit',   value: '500/hr',                     icon: Shield,     color: 'text-emerald-500', bg: 'rgba(16,185,129,0.08)' },
                            { label: 'Endpoints',    value: String(endpoints.length),     icon: Code2,      color: 'text-violet-500',  bg: 'rgba(99,102,241,0.08)' },
                        ]" :key="c.label"
                            class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-16 h-16 pointer-events-none"
                                :style="`background: radial-gradient(circle at 100% 0%, ${c.bg}, transparent 70%)`" />
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center mb-2" :style="`background: ${c.bg}`">
                                <component :is="c.icon" class="w-4 h-4" :class="c.color" />
                            </div>
                            <p class="text-[22px] font-black text-slate-900 dark:text-white">{{ c.value }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400">{{ c.label }}</p>
                        </div>
                    </div>
                </div>

                <!-- ════ AUTH ════ -->
                <div v-if="activePage === 'auth'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <Lock class="w-4 h-4 text-sky-500" /> Authentication
                        </h3>
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-400 mb-5 leading-relaxed">
                            Every request must include an <code class="font-mono text-sky-500">Authorization</code> header with your Bearer token. Requests without a valid key return <code class="font-mono text-rose-400">401 Unauthorized</code>.
                        </p>
                        <div class="relative">
                            <div class="p-4 bg-[#0d1117] rounded-xl overflow-x-auto">
                                <pre class="text-[12px] font-mono"><span class="text-slate-400">GET /api/v1/balance HTTP/1.1
Host: </span><span class="text-white">{{ baseUrl.replace('https://','').replace('http://','') }}</span>
<span class="text-sky-300">Authorization: Bearer YOUR_API_KEY</span>
<span class="text-slate-400">Accept: application/json</span></pre>
                            </div>
                            <button @click="copy('Authorization: Bearer YOUR_API_KEY', 'authheader')"
                                class="absolute right-3 top-3 w-7 h-7 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white/60 hover:text-white transition-colors">
                                <component :is="copied === 'authheader' ? Check : Copy" class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <div class="mt-5 grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-2">✓ Success 200</p>
                                <div class="p-3 bg-[#0d1117] rounded-xl">
                                    <pre class="text-[11.5px] font-mono text-emerald-300">{ "balance": "100.00" }</pre>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-rose-500 mb-2">✗ Unauthorized 401</p>
                                <div class="p-3 bg-[#0d1117] rounded-xl">
                                    <pre class="text-[11.5px] font-mono text-rose-300">{ "message": "Unauthenticated." }</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ API KEY ════ -->
                <div v-if="activePage === 'key'" class="space-y-5">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <Key class="w-4 h-4 text-sky-500" /> Your API Key
                        </h3>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-6">Keep your key secret. Never expose it in client-side code or public repositories.</p>

                        <div v-if="displayedKey" class="space-y-4">
                            <div>
                                <label class="block text-[10.5px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">API Key</label>
                                <div class="flex items-center gap-2 p-4 bg-slate-50 dark:bg-black/20 rounded-xl border border-slate-200 dark:border-white/8">
                                    <Lock class="w-4 h-4 text-slate-400 dark:text-slate-600 flex-shrink-0" />
                                    <code class="flex-1 text-[13px] font-mono text-slate-700 dark:text-slate-200 break-all min-w-0 select-all">
                                        {{ showKey ? displayedKey : displayedKey.slice(0, 10) + '●'.repeat(24) }}
                                    </code>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <button @click="showKey = !showKey"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/8 transition-all">
                                            <component :is="showKey ? EyeOff : Eye" class="w-4 h-4" />
                                        </button>
                                        <button @click="copy(displayedKey, 'key')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-all"
                                            :class="copied === 'key' ? 'bg-emerald-500 text-white' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/8'">
                                            <component :is="copied === 'key' ? Check : Copy" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <p v-if="tokenCreatedAt" class="mt-1.5 text-[11px] text-slate-400 dark:text-slate-600">Generated {{ formatDate(tokenCreatedAt) }}</p>
                            </div>
                            <div class="p-4 bg-amber-50 dark:bg-amber-500/[0.06] rounded-xl border border-amber-500/20">
                                <div class="flex items-start gap-2">
                                    <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                    <ul class="text-[11.5px] text-amber-600/90 dark:text-amber-500/80 space-y-0.5 list-disc list-inside">
                                        <li>Never commit your key to git / public repos</li>
                                        <li>Store it in <code class="font-mono">.env</code> or a secrets manager</li>
                                        <li>Rotate immediately if you suspect it's leaked</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(99,102,241,0.08))">
                                <Key class="w-7 h-7 text-sky-500/50" />
                            </div>
                            <p class="text-[14px] font-semibold text-slate-700 dark:text-slate-300 mb-1">No API Key Yet</p>
                            <p class="text-[12px] text-slate-400 dark:text-slate-600 mb-4">Generate a key to start integrating.</p>
                        </div>

                        <div class="flex items-center gap-3 mt-5 pt-5 border-t border-slate-100 dark:border-white/[0.06]">
                            <button @click="regenerate" :disabled="regenerating"
                                class="flex items-center gap-2 px-5 py-2.5 text-[13px] font-semibold rounded-xl text-white shadow-lg transition-all active:scale-95 disabled:opacity-60"
                                :style="apiKey ? 'background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 4px 16px rgba(99,102,241,0.3)' : 'background: linear-gradient(135deg, #0ea5e9, #6366f1); box-shadow: 0 4px 16px rgba(14,165,233,0.3)'">
                                <Loader2 v-if="regenerating" class="w-3.5 h-3.5 animate-spin" />
                                <RefreshCw v-else class="w-3.5 h-3.5" />
                                {{ apiKey ? 'Regenerate Key' : 'Generate API Key' }}
                            </button>
                            <p class="text-[11px] text-slate-400 dark:text-slate-600">{{ apiKey ? 'Invalidates the current key immediately.' : 'Creates a new key for your account.' }}</p>
                        </div>
                    </div>
                </div>

                <!-- ════ SMM WORKFLOW ════ -->
                <div v-if="activePage === 'smm'" class="space-y-4">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <TrendingUp class="w-4 h-4 text-sky-500" /> SMM Orders Workflow
                        </h3>
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-400 mb-6">3-step flow: fetch services → place order → poll status.</p>

                        <div class="space-y-5">
                            <div v-for="(step, i) in [
                                { title: 'Step 1 — Fetch available services', codeKey: 'services', badge: 'GET', badgeClass: 'bg-emerald-500/10 text-emerald-500', desc: 'Get all service IDs, categories, min/max quantities, and price per 1000.' },
                                { title: 'Step 2 — Place an order', codeKey: 'placeOrder', badge: 'POST', badgeClass: 'bg-sky-500/10 text-sky-500', desc: 'Use the service ID from step 1. Returns an order ID for polling.' },
                                { title: 'Step 3 — Poll order status', codeKey: 'orderStatus', badge: 'GET', badgeClass: 'bg-emerald-500/10 text-emerald-500', desc: 'Poll every 30–60s. Status: pending → in_progress → completed / partial / cancelled.' },
                            ]" :key="i" class="rounded-xl border border-slate-100 dark:border-white/[0.06] overflow-hidden">
                                <button class="w-full flex items-center gap-3 px-4 py-3 bg-slate-50 dark:bg-white/[0.02] hover:bg-slate-100 dark:hover:bg-white/[0.04] text-left transition-colors"
                                    @click="expandedFlow = expandedFlow === i ? null : i">
                                    <span :class="['text-[10px] font-black px-2 py-0.5 rounded-md', step.badgeClass]">{{ step.badge }}</span>
                                    <span class="text-[13px] font-semibold text-slate-800 dark:text-slate-200 flex-1">{{ step.title }}</span>
                                    <ChevronDown class="w-4 h-4 text-slate-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': expandedFlow === i }" />
                                </button>
                                <div v-if="expandedFlow === i" class="p-4 space-y-3">
                                    <p class="text-[12px] text-slate-400 dark:text-slate-400">{{ step.desc }}</p>
                                    <div class="relative">
                                        <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                            <pre class="text-[12px] font-mono text-emerald-300 whitespace-pre-wrap">{{ codeFor(step.codeKey) }}</pre>
                                        </div>
                                        <button @click="copy(codeFor(step.codeKey), 'flow-' + i)"
                                            class="absolute right-2 top-2 w-7 h-7 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white/60 hover:text-white transition-colors">
                                            <component :is="copied === 'flow-' + i ? Check : Copy" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order status flowchart -->
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-4">Order Status Flow</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <template v-for="(s, i) in [
                                { label: 'pending',     color: 'bg-amber-500/10 text-amber-500 border-amber-500/20' },
                                { label: 'in_progress', color: 'bg-sky-500/10 text-sky-500 border-sky-500/20' },
                                { label: 'completed',   color: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
                                { label: 'partial',     color: 'bg-violet-500/10 text-violet-500 border-violet-500/20' },
                                { label: 'cancelled',   color: 'bg-rose-500/10 text-rose-500 border-rose-500/20' },
                            ]" :key="s.label">
                                <span :class="['text-[11px] font-mono font-bold px-2.5 py-1 rounded-lg border', s.color]">{{ s.label }}</span>
                                <ChevronRight v-if="i < 2" class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700 flex-shrink-0" />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ════ SMS WORKFLOW ════ -->
                <div v-if="activePage === 'sms'" class="space-y-4">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <Phone class="w-4 h-4 text-sky-500" /> SMS / OTP Numbers
                        </h3>
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-400 mb-5">Rent virtual numbers for SMS verification. Purchased via the platform dashboard — number IDs can then be queried via API for received messages.</p>
                        <div class="space-y-3">
                            <div v-for="row in [
                                { label: 'Purchase number', desc: 'Done via dashboard (Buy Number page). Returns a number ID.' },
                                { label: 'Wait for SMS',    desc: 'OTP or verification SMS arrives on the rented number. Auto-detected.' },
                                { label: 'Query result',    desc: 'Use GET /api/v1/orders/{id} to read the SMS message received.' },
                            ]" :key="row.label" class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05]">
                                <div class="w-2 h-2 rounded-full bg-sky-500 mt-1.5 flex-shrink-0" />
                                <div>
                                    <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ row.label }}</p>
                                    <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">{{ row.desc }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 relative">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Check order for received SMS</p>
                            <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                <pre class="text-[12px] font-mono text-emerald-300 whitespace-pre-wrap">{{ codeFor('orderStatus') }}</pre>
                            </div>
                            <button @click="copy(codeFor('orderStatus'), 'sms-code')"
                                class="absolute right-2 top-8 w-7 h-7 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white/60 hover:text-white transition-colors">
                                <component :is="copied === 'sms-code' ? Check : Copy" class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ════ BALANCE ════ -->
                <div v-if="activePage === 'balance'" class="space-y-4">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <Layers class="w-4 h-4 text-sky-500" /> Balance & Wallet
                        </h3>
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-400 mb-5">Check your wallet balance and transaction history programmatically.</p>

                        <div class="space-y-5">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">GET /api/v1/balance</p>
                                <div class="relative">
                                    <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                        <pre class="text-[12px] font-mono text-emerald-300 whitespace-pre-wrap">{{ codeFor('balance') }}</pre>
                                    </div>
                                    <button @click="copy(codeFor('balance'), 'bal')"
                                        class="absolute right-2 top-2 w-7 h-7 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white/60 hover:text-white transition-colors">
                                        <component :is="copied === 'bal' ? Check : Copy" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Response</p>
                                <div class="p-3 bg-[#0d1117] rounded-xl">
                                    <pre class="text-[12px] font-mono text-sky-300">{{ `{\n  "balance": "100.00",\n  "currency": "USD"\n}` }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ WEBHOOKS ════ -->
                <div v-if="activePage === 'webhooks'" class="space-y-4">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                            <Webhook class="w-4 h-4 text-sky-500" /> Webhooks
                        </h3>
                        <p class="text-[12.5px] text-slate-400 dark:text-slate-400 mb-5">Webhooks are used by payment gateways to notify the platform of successful deposits. If you're building a reseller platform, here's what the incoming payload looks like on your server-side.</p>

                        <div class="space-y-5">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Deposit Confirmation Payload (OxaPay → Platform)</p>
                                <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                    <pre class="text-[12px] font-mono text-sky-300">{{ `{\n  "status": "Paid",\n  "trackId": "oxapay_track_id",\n  "amount": "10.00",\n  "currency": "USDT",\n  "txHash": "0xabc123..."\n}` }}</pre>
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-white/[0.03] rounded-xl border border-slate-100 dark:border-white/[0.06] space-y-2">
                                <p class="text-[12.5px] font-semibold text-slate-700 dark:text-slate-300">Best practices</p>
                                <ul class="text-[12px] text-slate-400 dark:text-slate-400 space-y-1 list-disc list-inside">
                                    <li>Always verify the signature / trackId against your records</li>
                                    <li>Respond with HTTP 200 immediately — process async</li>
                                    <li>Implement idempotency to prevent double-credits</li>
                                    <li>Log all incoming payloads for audit trail</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ ALL ENDPOINTS ════ -->
                <div v-if="activePage === 'endpoints'" class="space-y-3">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1">REST API Reference</h3>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400">All endpoints require <code class="font-mono text-sky-500">Authorization: Bearer API_KEY</code> header.</p>
                    </div>

                    <div v-for="ep in endpoints" :key="ep.path"
                        class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                        <button class="w-full flex items-center gap-3 p-4 text-left hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors"
                            @click="expandedEp = expandedEp === ep.path ? null : ep.path">
                            <span :class="['text-[10px] font-black px-2 py-0.5 rounded-md flex-shrink-0', methodColor(ep.method)]">{{ ep.method }}</span>
                            <code class="flex-1 text-[12.5px] font-mono text-slate-700 dark:text-slate-300">{{ ep.path }}</code>
                            <span class="text-[11.5px] text-slate-400 dark:text-slate-600 hidden sm:block flex-shrink-0">{{ ep.label }}</span>
                            <ChevronDown class="w-4 h-4 text-slate-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': expandedEp === ep.path }" />
                        </button>
                        <div v-if="expandedEp === ep.path" class="px-4 pb-4 border-t border-slate-100 dark:border-white/[0.06] space-y-4">
                            <p class="text-[12.5px] text-slate-500 dark:text-slate-400 mt-3">{{ ep.desc }}</p>
                            <div v-if="ep.params.length > 0">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-2">Parameters</p>
                                <div class="space-y-1.5">
                                    <div v-for="p in ep.params" :key="p.name" class="flex items-center gap-3 text-[12px]">
                                        <code class="font-mono font-bold text-sky-500 flex-shrink-0 w-24">{{ p.name }}</code>
                                        <span class="text-slate-400 dark:text-slate-600 flex-shrink-0 w-16">{{ p.type }}</span>
                                        <span class="text-slate-500 dark:text-slate-400 flex-1">{{ p.desc }}</span>
                                        <span v-if="p.required" class="text-[9px] font-black uppercase text-rose-500">req</span>
                                        <span v-else class="text-[9px] font-black uppercase text-slate-300 dark:text-slate-700">opt</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600">Response</p>
                                    <button @click="copy(ep.response, ep.path)" class="flex items-center gap-1 text-[10px] font-semibold text-slate-400 hover:text-sky-500 transition-colors">
                                        <component :is="copied === ep.path ? Check : Copy" class="w-3 h-3" />
                                        {{ copied === ep.path ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                                <div class="p-3 bg-[#0d1117] rounded-xl overflow-x-auto">
                                    <pre class="text-[12px] font-mono text-emerald-300">{{ ep.response }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ ERROR CODES ════ -->
                <div v-if="activePage === 'errors'" class="space-y-3">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                        <h3 class="text-[15px] font-bold text-slate-900 dark:text-white mb-1">Error Codes</h3>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400">All errors return JSON with a <code class="font-mono text-sky-500">message</code> field.</p>
                    </div>
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                        <table class="w-full text-[13px]">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-white/[0.06] bg-slate-50 dark:bg-white/[0.02]">
                                    <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400">Code</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400">Status</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-400">Meaning</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.05]">
                                <tr v-for="e in errors" :key="e.code" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                    <td class="px-5 py-3">
                                        <code :class="['font-mono font-black text-[13px]', e.code >= 500 ? 'text-rose-500' : e.code >= 400 ? 'text-amber-500' : 'text-emerald-500']">{{ e.code }}</code>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">{{ e.label }}</td>
                                    <td class="px-4 py-3 text-[12px] text-slate-400 dark:text-slate-400">{{ e.desc }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-3">Example error response</p>
                        <div class="p-3 bg-[#0d1117] rounded-xl">
                            <pre class="text-[12px] font-mono text-rose-300">{{ `{\n  "message": "The quantity field is required.",\n  "errors": {\n    "quantity": ["The quantity field is required."]\n  }\n}` }}</pre>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { getPreferredTheme, setThemeInstant } from '@/utils/theme';
import { Layers3, Zap, Moon, Sun, ArrowLeft, ShieldCheck, Database, CreditCard, Cookie, Eye, Bell, Code2, User, Lock, Inbox } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const isDark = ref(false);
const themeIcon = computed(() => (isDark.value ? Sun : Moon));
const activeSection = ref('');
const supportLink = computed(() => usePage().props.contact_link || 'mailto:support@nexahub.io');

function setTheme(theme) {
    const resolved = setThemeInstant(theme);
    isDark.value = resolved === 'dark';
}

function toggleTheme() {
    setTheme(isDark.value ? 'light' : 'dark');
}

onMounted(() => {
    setTheme(getPreferredTheme());

    if (sections.length) activeSection.value = sections[0].id;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) activeSection.value = entry.target.id;
            });
        },
        { rootMargin: '-10% 0px -85% 0px' },
    );

    sections.forEach(({ id }) => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});

const sections = [
    { id: 'collection', icon: Database, title: 'Data We Collect', iconGrad: 'from-sky-500 to-blue-600' },
    { id: 'sms-data', icon: Inbox, title: 'SMS Message Data', iconGrad: 'from-emerald-400 to-cyan-500' },
    { id: 'wallet', icon: CreditCard, title: 'Wallet & Payments', iconGrad: 'from-emerald-500 to-teal-600' },
    { id: 'account', icon: User, title: 'Account Privacy', iconGrad: 'from-violet-500 to-purple-600' },
    { id: 'security', icon: ShieldCheck, title: 'Security & Fraud', iconGrad: 'from-pink-500 to-rose-600' },
    { id: 'api', icon: Code2, title: 'API & OTP Data', iconGrad: 'from-amber-500 to-orange-600' },
    { id: 'cookies', icon: Cookie, title: 'Cookies', iconGrad: 'from-slate-500 to-slate-700' },
    { id: 'communications', icon: Bell, title: 'Communications', iconGrad: 'from-teal-500 to-cyan-600' },
    { id: 'rights', icon: Lock, title: 'Your Rights', iconGrad: 'from-indigo-500 to-blue-700' },
];
</script>

<template>
    <Head title="Privacy Policy — NexaHub" />

    <div class="relative min-h-screen bg-white dark:bg-[#070d1a] text-slate-900 dark:text-white transition-colors duration-300">

        <!-- Ambient glow -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 right-0 h-[500px] w-[500px] rounded-full bg-sky-400/6 dark:bg-sky-500/4 blur-[120px]"></div>
        </div>

        <!-- Navbar -->
        <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-white/6 bg-white/85 dark:bg-[#070d1a]/90 backdrop-blur-2xl">
            <div class="mx-auto flex h-[68px] max-w-5xl items-center justify-between px-5 sm:px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-xl bg-sky-500/30 blur-md transition-all group-hover:blur-lg group-hover:bg-sky-500/40"></div>
                        <div class="relative flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 shadow-lg">
                            <Zap class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <span class="text-base font-black tracking-tight text-slate-900 dark:text-white">Nexa<span class="text-sky-500">Hub</span></span>
                </Link>

                <div class="flex items-center gap-3">
                    <button
                        class="rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-2.5 text-slate-600 dark:text-slate-400 transition-all hover:bg-slate-50 dark:hover:bg-white/10"
                        @click="toggleTheme"
                        aria-label="Toggle theme"
                    >
                        <component :is="themeIcon" class="h-4 w-4" />
                    </button>
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-all hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:border-sky-200 dark:hover:border-sky-500/30 hover:text-sky-700 dark:hover:text-sky-300"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to home
                    </Link>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-5 py-16 sm:px-8 lg:grid lg:grid-cols-[220px_1fr] lg:gap-16">

            <!-- Sidebar TOC -->
            <aside class="hidden lg:block">
                <div class="sticky top-24">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-4">On this page</p>
                    <nav class="space-y-1">
                        <a
                            v-for="s in sections"
                            :key="s.id"
                            :href="`#${s.id}`"
                            :class="[
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-all duration-150',
                                activeSection === s.id
                                    ? 'bg-sky-50 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300 font-semibold shadow-sm dark:shadow-[0_2px_16px_rgba(14,165,233,0.15)] dark:ring-1 dark:ring-sky-500/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-sky-50 dark:hover:bg-sky-900/40 hover:text-sky-700 dark:hover:text-sky-300',
                            ]"
                        >
                            <div :class="['flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md bg-gradient-to-br', s.iconGrad]">
                                <component :is="s.icon" class="h-3 w-3 text-white" />
                            </div>
                            {{ s.title }}
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Content -->
            <main class="min-w-0">

                <!-- Header -->
                <div class="mb-12 pb-8 border-b border-slate-100 dark:border-white/8">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 dark:border-sky-500/20 bg-sky-50 dark:bg-sky-500/8 px-4 py-2 text-sm font-semibold text-sky-700 dark:text-sky-400 mb-5">
                        <Eye class="h-3.5 w-3.5" />
                        Privacy
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white sm:text-5xl">Privacy Policy</h1>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">Last updated: May 2026</p>
                    <p class="mt-3 text-base leading-8 text-slate-600 dark:text-slate-400">
                        At NexaHub, your privacy is foundational to how we operate. This policy explains what data we collect, how we use it, and the rights you have over it across our virtual number, OTP activation, and SMS verification platform.
                    </p>
                </div>

                <div class="space-y-14">

                    <!-- 1. Data We Collect -->
                    <section id="collection">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 shadow-md">
                                <Database class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">1. Data We Collect</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>We collect the following categories of data to operate and improve the NexaHub platform:</p>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Account Information</p>
                                    <p class="text-sm">Full name, email address, phone number (where provided), and encrypted password. This data is required to create and maintain your account.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Financial & Transaction Data</p>
                                    <p class="text-sm">Wallet balances, transaction history, payment method metadata (last 4 digits, type), and order records. Full card numbers are never stored — they are processed and tokenized by our payment providers.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Usage & Technical Data</p>
                                    <p class="text-sm">IP address, browser type, device identifiers, pages visited, time spent, and referring URLs. This data is collected automatically and helps us detect fraud and improve user experience.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">KYC & Verification Data</p>
                                    <p class="text-sm">For users who require identity verification (e.g., for withdrawals above defined thresholds): government-issued ID, facial verification, and proof of address. This data is processed by our regulated KYC partners and retained only as long as required by law.</p>
                                </div>
                            </div>

                            <p>We do not sell your personal data to third parties for marketing purposes.</p>
                        </div>
                    </section>

                    <!-- 2. SMS Message Data -->
                    <section id="sms-data">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 shadow-md">
                                <Inbox class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">2. SMS Message Data</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>NexaHub's virtual number and Receive SMS services involve the delivery of SMS messages to your dashboard. Here is how we handle that message data:</p>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">OTP Content — Not Permanently Stored</p>
                                    <p class="text-sm">OTP codes and verification messages delivered through NexaHub virtual numbers are displayed on your dashboard for the duration of the session only. OTP message content is permanently purged from our servers within <strong class="text-slate-800 dark:text-slate-200">24 hours</strong> of session completion. We do not index, analyze, or retain OTP codes beyond this window.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Receive SMS Inbox Messages</p>
                                    <p class="text-sm">Messages received through the Receive SMS service are stored temporarily in your account inbox for the duration of the rental session plus a 24-hour grace period. After this, messages are automatically and permanently deleted. We do not read, share, or analyze the content of messages in your inbox.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">Activation Metadata — Retained for Support</p>
                                    <p class="text-sm">Metadata about your activations (service name, country, timestamp, session outcome — not message content) is retained for 90 days for support, refund processing, and fraud prevention purposes. This metadata does not include the text of SMS messages received.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-100 dark:border-sky-500/15 bg-slate-50 dark:bg-[#0d1e35] px-5 py-4">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Third-Party Sharing</p>
                                    <p class="text-sm">SMS message content is never shared with third parties, sold, or used for marketing purposes. Access to message content is restricted to the account holder and NexaHub's automated delivery system.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 3. Wallet & Payments -->
                    <section id="wallet">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-md">
                                <CreditCard class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">2. Wallet &amp; Payment Protection</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Your wallet and financial data are protected by industry-standard security measures:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>All wallet balances (NGN, USD, USDC) are stored in segregated accounts and are never commingled with operational funds.</li>
                                <li>All financial data is encrypted at rest using AES-256 and in transit using TLS 1.3.</li>
                                <li>Payment card data is tokenized by PCI-DSS compliant payment processors. NexaHub never stores raw card numbers.</li>
                                <li>Multi-factor authentication (MFA) is available and strongly recommended for all accounts holding wallet balances.</li>
                                <li>Withdrawal requests trigger additional verification checks, including device fingerprinting and anomaly detection.</li>
                            </ul>

                            <div class="rounded-2xl border border-sky-200 dark:border-sky-500/20 bg-sky-50 dark:bg-sky-500/8 px-5 py-4">
                                <p class="text-sm font-bold text-sky-800 dark:text-sky-300 mb-1">Cryptocurrency Deposits</p>
                                <p class="text-sm text-sky-700 dark:text-sky-400">Crypto deposits (USDT, BTC, ETH, USDC) are recorded on-chain and are publicly visible on the relevant blockchain. NexaHub does not control the public nature of blockchain data. Your NexaHub wallet balance and activation history remain private and are accessible only to you and NexaHub's authorized compliance team.</p>
                            </div>
                        </div>
                    </section>

                    <!-- 3. Account Privacy -->
                    <section id="account">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-md">
                                <User class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">3. Account Privacy</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Your NexaHub account data is private by default:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Your wallet balances, transaction history, and order details are only visible to you and authorized NexaHub staff for support and compliance purposes.</li>
                                <li>Your account email and personal details are never displayed publicly or shared with other users.</li>
                                <li>You may update your account information at any time from your Profile settings.</li>
                                <li>You may request a full export of your account data by contacting <a href="mailto:privacy@nexahub.io" class="text-sky-600 dark:text-sky-400 hover:underline font-medium">privacy@nexahub.io</a>.</li>
                                <li>Account deletion requests will result in removal of personal data within 30 days, except where retention is required by law (e.g., financial transaction records may be retained for up to 7 years for compliance).</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 4. Security & Fraud Monitoring -->
                    <section id="security">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 shadow-md">
                                <ShieldCheck class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">4. Security &amp; Fraud Monitoring</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>To protect our platform and users, NexaHub uses automated and manual fraud monitoring systems:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>We monitor transaction patterns, login locations, device changes, and order behavior to detect suspicious activity.</li>
                                <li>Flagged accounts may be temporarily suspended pending manual review. We will notify you via email as soon as we are able.</li>
                                <li>We share data with law enforcement only when legally required (e.g., court orders, regulatory requests). We will notify affected users unless legally prohibited from doing so.</li>
                                <li>We use third-party fraud detection services that process limited account metadata (IP, device type, behavioral patterns) under strict data processing agreements.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 5. API Data Usage -->
                    <section id="api">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-md">
                                <Code2 class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">5. API &amp; OTP Data Usage</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>For developers and businesses using NexaHub's REST API, and regarding OTP message handling:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>API request logs (endpoint, timestamp, response codes, IP) are retained for 90 days for debugging and security purposes.</li>
                                <li>API keys are treated as credentials — they are hashed in our database and never displayed after initial creation.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">OTP Content:</strong> OTP messages received on virtual numbers are displayed on your dashboard only and are not stored permanently. OTP content is purged from our servers within 24 hours of session completion.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Activation Logs:</strong> Records of which service and country was activated (not the OTP content itself) are retained for 90 days for support and fraud prevention purposes.</li>
                                <li>If you integrate NexaHub's API into your application, you are responsible for ensuring your users' data is handled in compliance with applicable privacy laws.</li>
                                <li>We do not use your API request data or activation history for advertising or sell it to third parties.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 6. Cookies -->
                    <section id="cookies">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 shadow-md">
                                <Cookie class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">6. Cookies &amp; Storage</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>NexaHub uses the following types of browser storage:</p>
                            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-white/8">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 dark:bg-[#0a1628]">
                                        <tr>
                                            <th class="px-5 py-3.5 text-left font-bold text-slate-700 dark:text-slate-300">Type</th>
                                            <th class="px-5 py-3.5 text-left font-bold text-slate-700 dark:text-slate-300">Purpose</th>
                                            <th class="px-5 py-3.5 text-left font-bold text-slate-700 dark:text-slate-300">Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                        <tr class="bg-white dark:bg-transparent">
                                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 font-medium">Session cookie</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Authentication — keeps you logged in</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Session / 30 days (Remember Me)</td>
                                        </tr>
                                        <tr class="bg-white dark:bg-transparent">
                                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 font-medium">Theme preference</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">localStorage — dark/light mode</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Persistent</td>
                                        </tr>
                                        <tr class="bg-white dark:bg-transparent">
                                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 font-medium">CSRF token</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Security — prevents cross-site request forgery</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Session</td>
                                        </tr>
                                        <tr class="bg-white dark:bg-transparent">
                                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 font-medium">Analytics</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Aggregate usage data (no PII)</td>
                                            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400">Up to 1 year</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p>You can clear cookies at any time via your browser settings. Clearing session cookies will log you out of NexaHub.</p>
                        </div>
                    </section>

                    <!-- 7. Communications -->
                    <section id="communications">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-md">
                                <Bell class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">7. Communication Preferences</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>We may send you the following types of communications:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Transactional emails</strong> — Order confirmations, payment receipts, wallet activity alerts. These are mandatory and cannot be disabled.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Security alerts</strong> — Login notifications, password changes, unusual activity. These are mandatory.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Product updates</strong> — New features, services, and platform announcements. You may opt out at any time from your account settings.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Marketing</strong> — Promotions, special offers, and partner deals. Opt-in only; you may unsubscribe via the link in any email.</li>
                            </ul>
                            <p>To update your communication preferences, go to Settings → Notifications in your dashboard.</p>
                        </div>
                    </section>

                    <!-- 8. Your Rights -->
                    <section id="rights">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-700 shadow-md">
                                <Lock class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">8. Your Privacy Rights</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Depending on your jurisdiction, you may have the following rights regarding your personal data:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Right of access</strong> — Request a copy of all personal data we hold about you.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Right to rectification</strong> — Correct inaccurate or incomplete data.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Right to erasure</strong> — Request deletion of your personal data (subject to legal retention requirements).</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Right to data portability</strong> — Receive your data in a structured, machine-readable format.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Right to object</strong> — Object to certain types of data processing, including direct marketing.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Right to restriction</strong> — Request that we limit how we process your data in certain circumstances.</li>
                            </ul>
                            <p>To exercise any of these rights, contact our Data Protection team at <a :href="supportLink" class="text-sky-600 dark:text-sky-400 hover:underline font-medium">support</a>. We will respond within 30 days. For complaints, you may also contact your local data protection authority.</p>
                        </div>
                    </section>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-slate-100 dark:border-white/8">
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-7">
                            This Privacy Policy was last updated in May 2026. We may update this policy periodically — you'll be notified of material changes via email. For questions, <a :href="supportLink" class="text-sky-600 dark:text-sky-400 hover:underline font-medium">contact support</a>.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-4">
                            <Link href="/" class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/25 transition-all hover:bg-sky-600 hover:-translate-y-px">
                                Back to NexaHub
                            </Link>
                            <Link :href="route('terms')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 px-5 py-3 text-sm font-bold text-slate-700 dark:text-slate-300 transition-all hover:bg-slate-50 dark:hover:bg-white/10">
                                View Terms of Service
                            </Link>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.space-y-14 section h2 {
    scroll-margin-top: 96px;
}
</style>

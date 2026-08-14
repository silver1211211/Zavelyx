<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { getPreferredTheme, setThemeInstant } from '@/utils/theme';
import {
    AlertTriangle,
    ArrowLeft,
    Banknote,
    Code2,
    FileText,
    Gavel,
    Inbox,
    MessageSquare,
    Moon,
    Phone,
    RefreshCcw,
    ShieldCheck,
    Sun,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const isDark = ref(false);
const themeIcon = computed(() => (isDark.value ? Sun : Moon));
const activeSection = ref('');
const supportLink = computed(() => usePage().props.contact_link || 'mailto:support@zavelyx.com');

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
    { id: 'overview', icon: FileText, title: 'Platform Overview', iconGrad: 'from-sky-500 to-blue-600' },
    { id: 'acceptable-use', icon: Users, title: 'Acceptable Use', iconGrad: 'from-emerald-500 to-teal-600' },
    { id: 'prohibited', icon: AlertTriangle, title: 'Prohibited Activities', iconGrad: 'from-red-500 to-rose-600' },
    { id: 'numbers-otp', icon: Phone, title: 'Numbers & OTP Policy', iconGrad: 'from-violet-500 to-purple-600' },
    { id: 'receive-sms', icon: Inbox, title: 'Receive SMS Service', iconGrad: 'from-sky-400 to-cyan-500' },
    { id: 'anti-spam', icon: MessageSquare, title: 'Anti-Spam Policy', iconGrad: 'from-orange-500 to-red-600' },
    { id: 'refunds', icon: RefreshCcw, title: 'Refund Policy', iconGrad: 'from-amber-500 to-orange-600' },
    { id: 'payments', icon: Banknote, title: 'Payments & Wallets', iconGrad: 'from-pink-500 to-rose-600' },
    { id: 'api', icon: Code2, title: 'API Usage', iconGrad: 'from-indigo-500 to-blue-700' },
    { id: 'termination', icon: Gavel, title: 'Account Termination', iconGrad: 'from-slate-600 to-slate-800' },
    { id: 'liability', icon: ShieldCheck, title: 'Liability & Legal', iconGrad: 'from-teal-500 to-cyan-600' },
];
</script>

<template>
    <Head title="Terms of Service — Zavelyx" />

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
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Legal
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white sm:text-5xl">Terms of Service</h1>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">Last updated: May 2026</p>
                    <p class="mt-3 text-base leading-8 text-slate-600 dark:text-slate-400">
                        These Terms of Service govern your use of Zavelyx — a virtual number and OTP activation platform. By accessing or using Zavelyx, you agree to be bound by these terms in full. Please read them carefully before registering or placing any activation.
                    </p>
                </div>

                <div class="prose-nexahub space-y-14">

                    <!-- 1. Platform Overview -->
                    <section id="overview">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 shadow-md">
                                <FileText class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">1. Platform Overview</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx is a virtual number and SMS verification platform. We provide temporary, real phone numbers that can receive SMS messages, enabling users to complete OTP-based verification flows for third-party platforms and services.</p>
                            <p>Zavelyx provides the following core service categories:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Virtual Number Activations:</strong> Temporary real phone numbers for single-use SMS verification across 300+ platforms including WhatsApp, Telegram, Google, Discord, Instagram, TikTok, Uber, Binance, and more.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Receive SMS Service:</strong> Live SMS inbox service allowing users to receive all incoming messages on a virtual number in real time through the Zavelyx dashboard.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">OTP Marketplace:</strong> Automated OTP activation service with real-time delivery, status tracking, and automatic refund on failure.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Multi-Country Numbers:</strong> Phone numbers from 150+ countries and 700+ operators to satisfy geo-specific verification requirements.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Account Wallet:</strong> Pre-funded balance system for purchasing activations and SMS sessions. Supports deposit via cryptocurrency and other supported methods.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Developer API:</strong> REST API access for automated OTP activation workflows, bulk number management, webhook events, and reseller integrations.</li>
                            </ul>
                            <p>Zavelyx is a technology intermediary and is not affiliated with, endorsed by, or partnered with any of the platforms whose verification services users access through our numbers. We reserve the right to modify, suspend, or discontinue any service at any time.</p>
                        </div>
                    </section>

                    <!-- 2. Acceptable Use -->
                    <section id="acceptable-use">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-md">
                                <Users class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">2. Acceptable Use</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx may be used for the following legitimate purposes:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Verifying your own accounts on third-party platforms where you do not wish to provide your personal phone number.</li>
                                <li>Testing applications, bots, or systems that require SMS verification as part of their onboarding or QA flow.</li>
                                <li>Research and development activities requiring OTP-based verification flows.</li>
                                <li>Reselling virtual number activations to end users via the Zavelyx API, provided your use complies with these terms.</li>
                            </ul>
                            <p>By using Zavelyx, you represent that you are at least 18 years old, have the legal capacity to enter into these terms, and are not located in a jurisdiction where the use of virtual number services is prohibited.</p>
                        </div>
                    </section>

                    <!-- 3. Prohibited Activities -->
                    <section id="prohibited">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-600 shadow-md">
                                <AlertTriangle class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">3. Prohibited Activities</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>The following uses are strictly prohibited and will result in immediate account suspension and potential legal action:</p>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/8 px-5 py-4">
                                    <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-1">Spam & Mass Account Creation</p>
                                    <p class="text-sm text-red-700 dark:text-red-400">Using Zavelyx to create mass accounts on any platform for the purpose of spamming, boosting, vote manipulation, or artificially inflating metrics. Creating more than one account per platform for deceptive purposes is prohibited.</p>
                                </div>
                                <div class="rounded-2xl border border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/8 px-5 py-4">
                                    <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-1">Fraud & Identity Theft</p>
                                    <p class="text-sm text-red-700 dark:text-red-400">Using virtual numbers to impersonate others, commit fraud, bypass legitimate security controls for unauthorized access, or engage in any form of identity theft.</p>
                                </div>
                                <div class="rounded-2xl border border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/8 px-5 py-4">
                                    <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-1">Financial Crime</p>
                                    <p class="text-sm text-red-700 dark:text-red-400">Using Zavelyx to facilitate money laundering, cryptocurrency scams, Ponzi schemes, unauthorized access to financial accounts, or any other financial crime.</p>
                                </div>
                                <div class="rounded-2xl border border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/8 px-5 py-4">
                                    <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-1">Abuse of Target Platforms</p>
                                    <p class="text-sm text-red-700 dark:text-red-400">Using Zavelyx to circumvent security systems, bans, or restrictions imposed by third-party platforms in a manner that violates those platforms' terms of service in a harmful or malicious way.</p>
                                </div>
                                <div class="rounded-2xl border border-red-100 dark:border-red-500/20 bg-red-50 dark:bg-red-500/8 px-5 py-4">
                                    <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-1">Harassment & Harm</p>
                                    <p class="text-sm text-red-700 dark:text-red-400">Using numbers or activations to harass, stalk, threaten, or cause harm to individuals or organizations.</p>
                                </div>
                            </div>

                            <p>Zavelyx operates automated abuse detection systems. Accounts engaging in prohibited activities will be suspended without refund. Zavelyx cooperates fully with law enforcement upon lawful request.</p>
                        </div>
                    </section>

                    <!-- 4. Virtual Numbers & OTP Policy -->
                    <section id="numbers-otp">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-md">
                                <Phone class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">4. Virtual Numbers &amp; OTP Policy</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Virtual numbers provided by Zavelyx are subject to the following conditions:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Single-use:</strong> Each number is allocated for one activation session. Once an OTP is received or the session expires, the number is released. Numbers are not reserved exclusively beyond the active session window.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Session Duration:</strong> Activation sessions are typically 20 minutes. If no OTP is received within this window, the session expires and an automatic refund is issued.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Third-party Dependency:</strong> OTP delivery depends on the target platform sending the SMS. Zavelyx is not responsible for delays, failures, or blocks caused by the target platform's systems, spam filters, or policies.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">No Guarantee of Delivery:</strong> While we maintain a 99.7%+ success rate, we cannot guarantee that every activation will result in an OTP. Automatic refunds cover failed activations.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Number Availability:</strong> Number availability by country and service may vary based on our provider pool. Zavelyx does not guarantee availability for any specific service or country at any given time.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Real Numbers Only:</strong> All numbers are real SIM-backed mobile numbers. We do not offer VOIP-only numbers for services that explicitly block them.</li>
                            </ul>

                            <div class="rounded-2xl border border-violet-200 dark:border-violet-500/20 bg-violet-50 dark:bg-violet-500/8 px-5 py-4">
                                <p class="text-sm font-bold text-violet-800 dark:text-violet-300 mb-1">Provider Disclaimer</p>
                                <p class="text-sm text-violet-700 dark:text-violet-400">Zavelyx sources numbers from third-party telecoms providers. While we rigorously vet our providers, we cannot be held liable for number quality issues, delayed message delivery, or provider-side outages beyond our control.</p>
                            </div>
                        </div>
                    </section>

                    <!-- 5. Receive SMS Service -->
                    <section id="receive-sms">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-cyan-500 shadow-md">
                                <Inbox class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">5. Receive SMS Service</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx's Receive SMS service provides virtual numbers with a live SMS inbox. Use of this service is subject to additional conditions:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Temporary Numbers:</strong> Numbers provided for Receive SMS are temporary and may be recycled after your session ends. Zavelyx does not guarantee that a number remains exclusively yours beyond the active rental window.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Message Retention:</strong> Incoming SMS messages are displayed in your dashboard in real time and are retained for a maximum of 24 hours after session completion, after which they are permanently deleted from our servers.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">No Guaranteed Delivery:</strong> While Zavelyx delivers the vast majority of incoming SMS, delivery depends on third-party telecoms networks. Zavelyx is not liable for messages not delivered due to sender-side or carrier-side issues.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Prohibited Content:</strong> You must not use the Receive SMS service to receive illegal content, facilitate fraud, intercept communications intended for others without authorization, or harvest personal data from received messages.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">No Interception:</strong> The Receive SMS service is designed for numbers you purchase. You must not use it to attempt to intercept SMS intended for someone else's account, phone number, or device.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Session Limits:</strong> Each Receive SMS session has a defined duration. Messages received after the session expires are not guaranteed to be delivered or displayed.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 6. Anti-Spam & Communications Policy -->
                    <section id="anti-spam">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-600 shadow-md">
                                <MessageSquare class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">6. Anti-Spam &amp; Communications Policy</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx maintains a strict zero-tolerance policy on spam and abusive messaging facilitated through our platform:</p>

                            <div class="rounded-2xl border border-orange-100 dark:border-orange-500/20 bg-orange-50 dark:bg-orange-500/8 px-5 py-4">
                                <p class="text-sm font-bold text-orange-800 dark:text-orange-300 mb-1">Zero Tolerance for Spam</p>
                                <p class="text-sm text-orange-700 dark:text-orange-400">Using Zavelyx to create virtual numbers or SMS inboxes for the purpose of sending, receiving, or facilitating spam — including unsolicited marketing, phishing attempts, or bulk unsolicited messages — is strictly prohibited and will result in permanent account termination.</p>
                            </div>

                            <p>Specifically prohibited communications-related activities include:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Using virtual numbers to register mass accounts on any platform for the purpose of sending spam or unsolicited messages.</li>
                                <li>Using the Receive SMS inbox to harvest phone numbers, OTPs, or personal data from messages not intended for you.</li>
                                <li>Reselling access to Zavelyx SMS inboxes for use in spam or bulk messaging campaigns.</li>
                                <li>Using Zavelyx services in connection with smishing (SMS phishing) attacks, vishing, or social engineering campaigns.</li>
                                <li>Automating the Receive SMS service to systematically collect or scrape incoming message content.</li>
                            </ul>

                            <p>Zavelyx reserves the right to implement automated detection systems to identify patterns consistent with spam or abusive use. Accounts flagged by these systems may be suspended pending review without prior notice.</p>
                        </div>
                    </section>

                    <!-- 7. Refund Policy -->
                    <section id="refunds">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-md">
                                <RefreshCcw class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">5. Refund Policy</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">

                            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/8 px-5 py-4">
                                <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300 mb-1">Automatic Refund — No OTP Received</p>
                                <p class="text-sm text-emerald-700 dark:text-emerald-400">If your activation session expires without an OTP being received, your balance is refunded automatically within minutes. No support ticket is required. This covers all cases where the number was provided but the target platform did not send an SMS.</p>
                            </div>

                            <p>Additional refund scenarios:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Invalid Number Provided:</strong> If the number allocated is invalid or cannot receive SMS (a provider-side error), a full refund is issued automatically.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Platform Already Used Number:</strong> If the target platform rejects the number as "already registered," you may request a refund via support within 5 minutes of activation.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">OTP Received — Non-Refundable:</strong> Once an OTP has been delivered to your dashboard, the activation is considered successful and is non-refundable, even if you are unable to use it.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Wallet Deposits:</strong> Wallet top-ups are non-refundable to external payment methods. Unused wallet balance remains available for future activations.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Suspended Accounts:</strong> Wallet balances on accounts suspended for abuse or fraud violations are forfeited and are not eligible for refund.</li>
                            </ul>

                            <p>All refunds are credited to your Zavelyx wallet balance. We do not issue refunds to external payment methods or bank accounts.</p>
                        </div>
                    </section>

                    <!-- 6. Payments & Wallets -->
                    <section id="payments">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 shadow-md">
                                <Banknote class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">6. Payments &amp; Wallets</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx operates a pre-funded wallet system. You must deposit balance before purchasing activations.</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li><strong class="text-slate-800 dark:text-slate-200">Deposit Methods:</strong> We accept cryptocurrency (USDT, BTC, ETH, USDC) and other supported payment methods shown at checkout. Minimum deposit is $1.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Pricing:</strong> Activation prices are displayed in your account dashboard before purchase. Prices are in USD and may change based on provider costs and availability. We display current pricing transparently at all times.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">No Hidden Fees:</strong> The price shown before activation is the final price charged. There are no additional fees per activation beyond the listed price.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Balance Expiry:</strong> Wallet balances do not expire and remain available indefinitely, subject to account standing.</li>
                                <li><strong class="text-slate-800 dark:text-slate-200">Chargebacks:</strong> Initiating a payment chargeback without first contacting our support team will result in immediate account suspension and a permanent ban from the platform.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 7. API Usage -->
                    <section id="api">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-700 shadow-md">
                                <Code2 class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">7. API Usage</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Access to Zavelyx's REST API is subject to the following conditions:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>API keys are personal credentials. Do not share them, expose them in client-side code, or commit them to public repositories. You are responsible for all activity under your API key.</li>
                                <li>Rate limits apply to all API access. Exceeding rate limits may result in temporary throttling or suspension of API access.</li>
                                <li>The API must not be used to automate prohibited activities listed in Section 3, including mass fake account creation or spam operations.</li>
                                <li>Reseller use of the API is permitted provided your end users operate within these terms and you do not enable prohibited activities.</li>
                                <li>Zavelyx reserves the right to revoke API access for terms violations without prior notice.</li>
                                <li>Uptime SLA (99.9%) applies to production API endpoints only, excluding scheduled maintenance windows communicated in advance.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 8. Account Termination -->
                    <section id="termination">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 shadow-md">
                                <Gavel class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">8. Account Termination</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>Zavelyx reserves the right to restrict, suspend, or permanently terminate accounts in the following circumstances:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Violation of any section of these Terms of Service.</li>
                                <li>Engaging in or facilitating any prohibited activity described in Section 3.</li>
                                <li>Providing false registration information or impersonating another person.</li>
                                <li>Initiating fraudulent chargebacks or payment disputes without contacting support first.</li>
                                <li>Attempting to reverse-engineer, scrape, or exploit the Zavelyx platform or API.</li>
                                <li>Creating multiple accounts to circumvent bans, spend limits, or promotional restrictions.</li>
                                <li>Any activity that Zavelyx determines, in its sole discretion, is harmful to the platform, other users, or third-party platforms.</li>
                            </ul>
                            <p>Upon account termination for abuse or fraud, any remaining wallet balance is forfeited. For other termination reasons, remaining balance may be accessible pending identity verification.</p>
                            <p>You may delete your own account at any time from your account settings. Account deletion does not entitle you to a refund of unused wallet balance.</p>
                        </div>
                    </section>

                    <!-- 9. Liability & Legal -->
                    <section id="liability">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-md">
                                <ShieldCheck class="h-5 w-5 text-white" />
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white">9. Limitation of Liability &amp; Compliance</h2>
                        </div>
                        <div class="space-y-4 text-slate-600 dark:text-slate-400 leading-8">
                            <p>To the maximum extent permitted by applicable law:</p>
                            <ul class="list-disc pl-6 space-y-2">
                                <li>Zavelyx is not liable for actions taken by third-party platforms in response to the use of our virtual numbers, including account bans, suspensions, or content removal.</li>
                                <li>Zavelyx is not responsible for OTP delivery failures caused by the target platform's own SMS blocking, spam filters, or system outages.</li>
                                <li>Zavelyx is not liable for indirect, incidental, special, consequential, or punitive damages arising from use of the platform.</li>
                                <li>Zavelyx's total aggregate liability for any claim relating to the platform is limited to the amount paid by you for activations in the 30 days prior to the claim.</li>
                                <li>Zavelyx provides the platform "as is" and "as available" without warranties of any kind, express or implied.</li>
                            </ul>

                            <div class="rounded-2xl border border-sky-200 dark:border-sky-500/20 bg-sky-50 dark:bg-sky-500/8 px-5 py-4">
                                <p class="text-sm font-bold text-sky-800 dark:text-sky-300 mb-1">Compliance & Law Enforcement</p>
                                <p class="text-sm text-sky-700 dark:text-sky-400">Zavelyx complies with applicable laws and regulations. We cooperate fully with law enforcement agencies upon receipt of valid legal process. User data may be disclosed to law enforcement as required by applicable law. We do not require a court order to disclose data in emergency situations involving imminent threat to life.</p>
                            </div>

                            <p>These Terms of Service are governed by and construed in accordance with the laws of the jurisdiction in which Zavelyx is incorporated, without regard to its conflict of law provisions. Any disputes arising from these terms shall be resolved through binding arbitration or in the courts of that jurisdiction.</p>
                        </div>
                    </section>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-slate-100 dark:border-white/8">
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-7">
                            These Terms of Service were last updated in May 2026. Zavelyx may update these terms periodically. Continued use of the platform following notice of changes constitutes acceptance of the revised terms. For questions,
                            <a :href="supportLink" class="text-sky-600 dark:text-sky-400 hover:underline font-medium">contact support</a>.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-4">
                            <Link href="/" class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/25 transition-all hover:bg-sky-600 hover:-translate-y-px">
                                Back to Zavelyx
                            </Link>
                            <Link :href="route('privacy')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 px-5 py-3 text-sm font-bold text-slate-700 dark:text-slate-300 transition-all hover:bg-slate-50 dark:hover:bg-white/10">
                                View Privacy Policy
                            </Link>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.prose-nexahub h2 {
    scroll-margin-top: 96px;
}
</style>

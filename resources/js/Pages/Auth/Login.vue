<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { getStoredTheme, setThemeInstant } from '@/utils/theme';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Eye,
    EyeOff,
    Moon,
    Sun,
    ShieldCheck,
    Zap,
    Check,
    Globe2,
    RefreshCcw,
    Inbox,
    Smartphone,
    Code2,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const siteSettings = computed(() => usePage().props.site_settings ?? {});
const authLogoUrl  = computed(() => siteSettings.value.logo_auth || siteSettings.value.logo_url || '');
const flashError   = computed(() => usePage().props.flash?.error ?? null);

const isDark = ref(false);
const showPassword = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const themeIcon = computed(() => (isDark.value ? Sun : Moon));

function setTheme(theme) {
    const resolved = setThemeInstant(theme);
    isDark.value = resolved === 'dark';
}

function toggleTheme() {
    setTheme(isDark.value ? 'light' : 'dark');
}

onMounted(() => {
    setTheme(getStoredTheme('dark'));
});

const highlights = [
    {
        icon: Smartphone,
        title: 'OTP & Virtual Numbers',
        desc: 'Instant activations across 300+ platforms. WhatsApp, Google, Telegram, Discord & more.',
        color: 'bg-sky-500/10 dark:bg-sky-500/12',
        iconColor: 'text-sky-600 dark:text-sky-300',
    },
    {
        icon: Inbox,
        title: 'Live SMS Inbox',
        desc: 'Receive SMS in a real-time dashboard inbox. Perfect for testing and verification workflows.',
        color: 'bg-emerald-500/10 dark:bg-emerald-500/12',
        iconColor: 'text-emerald-600 dark:text-emerald-300',
    },
    {
        icon: Code2,
        title: 'Developer API',
        desc: 'RESTful API for bulk activations, automation, webhooks, and reseller integrations.',
        color: 'bg-violet-500/10 dark:bg-violet-500/12',
        iconColor: 'text-violet-600 dark:text-violet-300',
    },
];
</script>

<template>
    <Head title="Sign In — NexaHub" />

    <div class="relative min-h-screen bg-white dark:bg-[#070d1a] text-slate-900 dark:text-white transition-colors duration-300 overflow-hidden">

        <!-- Ambient glows -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 right-0 h-[500px] w-[500px] rounded-full bg-sky-400/8 dark:bg-sky-500/10 blur-[100px]"></div>
            <div class="absolute bottom-0 -left-40 h-[400px] w-[400px] rounded-full bg-blue-400/6 dark:bg-blue-500/8 blur-[80px]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-sky-500/0 dark:bg-sky-500/4 blur-[140px] pointer-events-none hidden dark:block"></div>
        </div>

        <!-- Top bar -->
        <header class="absolute inset-x-0 top-0 z-20">
            <div class="mx-auto flex h-[68px] max-w-7xl items-center justify-between px-5 sm:px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <img v-if="authLogoUrl" :src="authLogoUrl" alt="Logo" class="h-9 max-w-[140px] object-contain" />
                    <template v-else>
                        <div class="relative">
                            <div class="absolute inset-0 rounded-xl blur-md transition-all group-hover:blur-lg"
                                style="background: color-mix(in srgb, var(--color-primary) 30%, transparent)"></div>
                            <div class="relative flex h-9 w-9 items-center justify-center rounded-xl shadow-lg"
                                style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary))">
                                <Zap class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <span class="text-base font-black tracking-tight text-slate-900 dark:text-white">{{ siteSettings.name || 'NexaHub' }}</span>
                    </template>
                </Link>

                <button
                    class="rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-2.5 text-slate-600 dark:text-slate-400 transition-all hover:border-sky-200 dark:hover:border-sky-500/30 hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-600 dark:hover:text-sky-300"
                    @click="toggleTheme"
                    aria-label="Toggle theme"
                >
                    <component :is="themeIcon" class="h-4 w-4" />
                </button>
            </div>
        </header>

        <!-- Split layout -->
        <div class="flex min-h-screen">

            <!-- ── Left panel: branding ── -->
            <div class="hidden lg:flex lg:w-[52%] lg:flex-col lg:justify-center lg:pt-[68px] lg:pb-8 lg:px-12 xl:px-16 relative">

                <!-- Left panel gradient wash -->
                <div class="absolute inset-0 bg-gradient-to-br from-sky-50/80 via-white to-white dark:from-[#050f1e] dark:via-[#070d1a] dark:to-[#070d1a] pointer-events-none"></div>
                <!-- Dark radial glow -->
                <div class="absolute top-0 left-0 w-full h-full pointer-events-none hidden dark:block" style="background: radial-gradient(ellipse 60% 50% at 25% 25%, rgba(14,165,233,0.07) 0%, transparent 70%)"></div>

                <div class="relative mx-auto w-full max-w-[440px]">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 dark:border-sky-500/25 bg-sky-50 dark:bg-sky-900/40 px-4 py-2 text-sm font-semibold text-sky-700 dark:text-sky-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                        Premium SMS &amp; OTP Infrastructure
                    </div>

                    <!-- Headline -->
                    <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight text-slate-900 dark:text-white xl:text-5xl">
                        Global SMS &amp;<br />
                        <span class="bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent">OTP Platform</span>
                    </h1>

                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-400">
                        Virtual numbers, SMS inbox, OTP activations, and developer API — all in one premium platform. 150+ countries, 300+ services.
                    </p>

                    <!-- Feature highlights -->
                    <div class="mt-10 space-y-3">
                        <div
                            v-for="h in highlights"
                            :key="h.title"
                            class="flex items-start gap-4 rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] p-4 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.4)] transition-all hover:border-sky-200 dark:hover:border-sky-500/30 hover:-translate-y-px dark:hover:bg-white/[0.04]"
                        >
                            <div :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl', h.color]">
                                <component :is="h.icon" :class="['h-5 w-5', h.iconColor]" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm">{{ h.title }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ h.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mini stats -->
                    <div class="mt-10 grid grid-cols-3 gap-4 border-t border-slate-100 dark:border-sky-500/15 pt-8">
                        <div class="text-center">
                            <p class="text-2xl font-black text-sky-600 dark:text-sky-300 dark:drop-shadow-[0_0_12px_rgba(56,189,248,0.5)]">2.4M+</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Activations</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-300 dark:drop-shadow-[0_0_12px_rgba(52,211,153,0.5)]">700+</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Operators</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-violet-600 dark:text-violet-300 dark:drop-shadow-[0_0_12px_rgba(167,139,250,0.5)]">300+</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Services</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Right panel: form ── -->
            <div class="flex w-full flex-col justify-center px-5 py-12 pt-24 sm:px-8 lg:w-[48%] lg:px-12 xl:px-16">

                <!-- Vertical separator line on desktop -->
                <div class="absolute left-[52%] top-0 hidden h-full w-px bg-gradient-to-b from-transparent via-slate-200 dark:via-sky-500/20 to-transparent lg:block"></div>

                <div class="mx-auto w-full max-w-[400px]">

                    <!-- Mobile logo -->
                    <div class="mb-10 flex flex-col items-center lg:hidden">
                        <div class="relative">
                            <div class="absolute inset-0 rounded-2xl bg-sky-500/30 blur-xl"></div>
                            <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 shadow-xl">
                                <Zap class="h-7 w-7 text-white" />
                            </div>
                        </div>
                        <h2 class="mt-5 text-2xl font-black text-slate-900 dark:text-white">Welcome back</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sign in to NexaHub</p>
                    </div>

                    <!-- Desktop heading -->
                    <div class="mb-8 hidden lg:block">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Sign in to NexaHub</h2>
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Enter your credentials to continue</p>
                    </div>

                    <!-- Status message -->
                    <div v-if="status" class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/8 px-4 py-3">
                        <Check class="h-4 w-4 flex-shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ status }}</p>
                    </div>

                    <!-- Error message (e.g. frozen account redirect) -->
                    <div v-if="flashError" class="mb-5 flex items-center gap-3 rounded-xl border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 px-4 py-3">
                        <span class="h-4 w-4 flex-shrink-0 text-rose-600 dark:text-rose-400">✕</span>
                        <p class="text-sm text-rose-700 dark:text-rose-300">{{ flashError }}</p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Email -->
                        <div>
                            <InputLabel
                                for="email"
                                value="Email address"
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"
                            />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="you@example.com"
                                class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/20 outline-none"
                            />
                            <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <InputLabel
                                    for="password"
                                    value="Password"
                                    class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                                />
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs font-semibold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition-colors"
                                >
                                    Forgot password?
                                </Link>
                            </div>
                            <div class="relative">
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 pr-12 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/20 outline-none"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 dark:text-slate-400 hover:text-slate-600 dark:hover:text-sky-300 transition-colors"
                                    @click="showPassword = !showPassword"
                                    tabindex="-1"
                                >
                                    <Eye v-if="showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.password" />
                        </div>

                        <!-- Remember me -->
                        <div class="flex items-center gap-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                v-model:checked="form.remember"
                                class="rounded border-slate-300 dark:border-white/20 text-sky-500 focus:ring-sky-400"
                            />
                            <label for="remember" class="text-sm text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                                Keep me signed in
                            </label>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="group w-full flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-sky-500/25 dark:shadow-sky-500/35 transition-all hover:bg-sky-600 hover:shadow-sky-500/40 dark:hover:shadow-sky-500/50 hover:-translate-y-px disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                Signing in…
                            </span>
                            <span v-else class="flex items-center gap-2">
                                Sign in to NexaHub
                                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-7 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200 dark:bg-white/8"></div>
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-400">or</span>
                        <div class="h-px flex-1 bg-slate-200 dark:bg-white/8"></div>
                    </div>

                    <!-- Sign up link -->
                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Don't have an account?
                        <Link :href="route('register')" class="font-bold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition-colors">
                            Create one free
                        </Link>
                    </p>

                    <!-- Security note -->
                    <div class="mt-8 flex items-center justify-center gap-2 text-xs text-slate-400 dark:text-slate-400">
                        <ShieldCheck class="h-3.5 w-3.5 text-sky-500/60 dark:text-sky-400/70" />
                        <span>Secured with 256-bit TLS encryption</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
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
    Globe2,
    RefreshCcw,
    Inbox,
    Smartphone,
    Code2,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const siteSettings = computed(() => usePage().props.site_settings ?? {});
const authLogoUrl  = computed(() => siteSettings.value.logo_auth || siteSettings.value.logo_url || '');

const isDark = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
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

const perks = [
    {
        icon: Smartphone,
        title: 'OTP & Virtual Numbers',
        desc: '300+ platforms: WhatsApp, Google, Telegram, TikTok, Discord & more.',
        iconGrad: 'from-sky-500 to-blue-600',
    },
    {
        icon: Inbox,
        title: 'Live SMS Inbox',
        desc: 'Receive real SMS online. Live inbox on your dashboard, 24/7.',
        iconGrad: 'from-emerald-500 to-teal-600',
    },
    {
        icon: RefreshCcw,
        title: 'Auto Refund',
        desc: 'No OTP received? Auto-refunded instantly. No support ticket needed.',
        iconGrad: 'from-pink-500 to-rose-600',
    },
    {
        icon: Code2,
        title: 'Developer API',
        desc: 'REST API with webhooks for automation, resellers, and integrations.',
        iconGrad: 'from-violet-500 to-purple-600',
    },
];

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return score;
});

const strengthLabel = computed(() => {
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    return labels[passwordStrength.value] || '';
});

const strengthColor = computed(() => {
    const colors = ['', 'bg-red-500', 'bg-amber-500', 'bg-sky-500', 'bg-emerald-500'];
    return colors[passwordStrength.value] || '';
});

const strengthTextColor = computed(() => {
    const colors = ['', 'text-red-500 dark:text-red-400', 'text-amber-500 dark:text-amber-400', 'text-sky-600 dark:text-sky-400', 'text-emerald-600 dark:text-emerald-400'];
    return colors[passwordStrength.value] || '';
});
</script>

<template>
    <Head title="Create Account — Zavelyx" />

    <div class="relative min-h-screen bg-white dark:bg-[#070d1a] text-slate-900 dark:text-white transition-colors duration-300 overflow-hidden">

        <!-- Ambient glows -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 right-0 h-[500px] w-[500px] rounded-full bg-sky-400/8 dark:bg-sky-500/10 blur-[100px]"></div>
            <div class="absolute bottom-0 -left-40 h-[400px] w-[400px] rounded-full bg-blue-400/6 dark:bg-blue-500/8 blur-[80px]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full dark:bg-sky-600/4 blur-[140px] hidden dark:block"></div>
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
                        <span class="text-base font-black tracking-tight text-slate-900 dark:text-white">{{ siteSettings.name || 'Zavelyx' }}</span>
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

                <div class="absolute inset-0 bg-gradient-to-br from-sky-50/80 via-white to-white dark:from-[#050f1e] dark:via-[#070d1a] dark:to-[#070d1a] pointer-events-none"></div>
                <div class="absolute top-0 left-0 w-full h-full pointer-events-none hidden dark:block" style="background: radial-gradient(ellipse 60% 50% at 30% 20%, rgba(14,165,233,0.07) 0%, transparent 70%)"></div>

                <div class="relative mx-auto w-full max-w-[440px]">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 dark:border-sky-500/25 bg-sky-50 dark:bg-sky-900/40 px-4 py-2 text-sm font-semibold text-sky-700 dark:text-sky-300">
                        <Zap class="h-3.5 w-3.5" />
                        Join Zavelyx
                    </div>

                    <!-- Headline -->
                    <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight text-slate-900 dark:text-white xl:text-5xl">
                        Complete SMS &amp;<br />
                        <span class="bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent">OTP platform</span>
                    </h1>

                    <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-400">
                        Virtual numbers, live SMS inbox, OTP activations, and developer API — 150+ countries, 300+ services, all in one dashboard.
                    </p>

                    <!-- Perks grid -->
                    <div class="mt-10 grid grid-cols-2 gap-3">
                        <div
                            v-for="p in perks"
                            :key="p.title"
                            class="rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] p-4 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.4)] transition-all hover:border-sky-200 dark:hover:border-sky-500/30 hover:-translate-y-px dark:hover:bg-white/[0.04]"
                        >
                            <div :class="['flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br shadow-md mb-3', p.iconGrad]">
                                <component :is="p.icon" class="h-4 w-4 text-white" />
                            </div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ p.title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ p.desc }}</p>
                        </div>
                    </div>

                    <!-- Social proof -->
                    <div class="mt-8 flex items-center gap-3 rounded-2xl border border-slate-100 dark:border-white/[0.09] bg-slate-50/80 dark:bg-[#0a1628] px-5 py-4 dark:shadow-[0_4px_24px_rgba(0,0,0,0.4)]">
                        <div class="flex -space-x-2">
                            <div v-for="i in 4" :key="i" :class="['flex h-8 w-8 items-center justify-center rounded-full border-2 border-white dark:border-[#0d1e35] text-xs font-bold text-white shadow-sm', ['bg-sky-500','bg-emerald-500','bg-violet-500','bg-pink-500'][i-1]]">
                                {{ ['J','A','M','S'][i-1] }}
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Join 12K+ users</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">receiving OTPs with Zavelyx</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Right panel: form ── -->
            <div class="flex w-full flex-col justify-start px-5 py-12 pt-24 sm:px-8 lg:w-[48%] lg:justify-center lg:overflow-y-auto lg:px-12 lg:pt-[84px] xl:px-16">

                <!-- Vertical separator -->
                <div class="absolute left-[52%] top-0 hidden h-full w-px bg-gradient-to-b from-transparent via-slate-200 dark:via-sky-500/20 to-transparent lg:block"></div>

                <div class="mx-auto w-full max-w-[400px]">

                    <!-- Mobile logo -->
                    <div class="mb-8 flex flex-col items-center lg:hidden">
                        <div class="relative">
                            <div class="absolute inset-0 rounded-2xl bg-sky-500/30 blur-xl"></div>
                            <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 shadow-xl">
                                <Zap class="h-7 w-7 text-white" />
                            </div>
                        </div>
                        <h2 class="mt-5 text-2xl font-black text-slate-900 dark:text-white">Create your account</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Free to join. No credit card needed.</p>
                    </div>

                    <!-- Desktop heading -->
                    <div class="mb-7 hidden lg:block">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Create your account</h2>
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Free to join · No credit card required</p>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Full name -->
                        <div>
                            <InputLabel
                                for="name"
                                value="Full name"
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"
                            />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Your full name"
                                class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 text-base sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/25 outline-none"
                            />
                            <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400" :message="form.errors.name" />
                        </div>

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
                                autocomplete="username"
                                placeholder="you@example.com"
                                class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 text-base sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/25 outline-none"
                            />
                            <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400" :message="form.errors.email" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel
                                for="password"
                                value="Password"
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"
                            />
                            <div class="relative">
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Min. 8 characters"
                                    class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 pr-12 text-base sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/20 outline-none"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-300 transition-colors"
                                    @click="showPassword = !showPassword"
                                    tabindex="-1"
                                >
                                    <Eye v-if="showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <!-- Password strength -->
                            <div v-if="form.password" class="mt-2.5">
                                <div class="flex gap-1">
                                    <div
                                        v-for="i in 4"
                                        :key="i"
                                        :class="[
                                            'h-1.5 flex-1 rounded-full transition-all duration-300',
                                            i <= passwordStrength ? strengthColor : 'bg-slate-200 dark:bg-white/15'
                                        ]"
                                    ></div>
                                </div>
                                <p :class="['text-xs mt-1 font-medium', strengthTextColor]">{{ strengthLabel }} password</p>
                            </div>
                            <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400" :message="form.errors.password" />
                        </div>

                        <!-- Confirm password -->
                        <div>
                            <InputLabel
                                for="password_confirmation"
                                value="Confirm password"
                                class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"
                            />
                            <div class="relative">
                                <TextInput
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Re-enter your password"
                                    class="block w-full rounded-xl border border-slate-200 dark:border-white/12 bg-white dark:bg-[#0f1e30] px-4 py-3 pr-12 text-base sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 transition focus:border-sky-400 dark:focus:border-sky-500 focus:ring-2 focus:ring-sky-400/20 dark:focus:ring-sky-500/20 outline-none"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-300 transition-colors"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    tabindex="-1"
                                >
                                    <Eye v-if="showConfirmPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400" :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Terms -->
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-sky-500/20 bg-slate-50 dark:bg-[#0d1e35] px-4 py-3.5">
                            <input
                                id="terms"
                                v-model="form.terms"
                                type="checkbox"
                                required
                                class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-slate-300 dark:border-white/25 text-sky-500 focus:ring-sky-400 cursor-pointer"
                            />
                            <label for="terms" class="text-xs leading-relaxed text-slate-600 dark:text-slate-300 cursor-pointer">
                                I agree to Zavelyx's
                                <Link href="/terms" class="font-semibold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition-colors">Terms of Service</Link>
                                and
                                <Link :href="route('privacy')" class="font-semibold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition-colors">Privacy Policy</Link>.
                            </label>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing || !form.terms"
                            class="group w-full flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-sky-500/25 dark:shadow-sky-500/35 transition-all hover:bg-sky-600 hover:shadow-sky-500/40 dark:hover:shadow-sky-500/50 hover:-translate-y-px disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                Creating account…
                            </span>
                            <span v-else class="flex items-center gap-2">
                                Create my account
                                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-6 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200 dark:bg-white/10"></div>
                        <span class="text-xs font-medium text-slate-400 dark:text-slate-400">or</span>
                        <div class="h-px flex-1 bg-slate-200 dark:bg-white/10"></div>
                    </div>

                    <!-- Sign in link -->
                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Already have an account?
                        <Link :href="route('login')" class="font-bold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition-colors">
                            Sign in
                        </Link>
                    </p>

                    <!-- Security note -->
                    <div class="mt-7 flex items-center justify-center gap-2 text-xs text-slate-400 dark:text-slate-400">
                        <ShieldCheck class="h-3.5 w-3.5 text-sky-500/60 dark:text-sky-400/70" />
                        <span>Your data is encrypted and never shared</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

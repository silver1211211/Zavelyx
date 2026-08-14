<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { getPreferredTheme, setThemeInstant } from '@/utils/theme';
import {
    AlertCircle,
    ArrowRight,
    CheckCircle2,
    Layers3,
    Mail,
    Moon,
    RefreshCw,
    ShieldCheck,
    Sun,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    status: { type: String, default: null },
    email: { type: String, required: true },
    expires_at: { type: String, default: null },
    resend_available_at: { type: String, default: null },
});

// ── Theme ─────────────────────────────────────────────────────────────────────
const isDark = ref(false);
const themeIcon = computed(() => (isDark.value ? Sun : Moon));

function setTheme(theme) {
    const resolved = setThemeInstant(theme);
    isDark.value = resolved === 'dark';
}
function toggleTheme() {
    setTheme(isDark.value ? 'light' : 'dark');
}

// ── OTP digits ────────────────────────────────────────────────────────────────
const digits = ref(['', '', '', '', '', '']);
const inputRefs = ref([]);

// ── Forms ─────────────────────────────────────────────────────────────────────
const form = useForm({ code: '' });
const resendForm = useForm({});

// ── Timers ────────────────────────────────────────────────────────────────────
const timeLeftSeconds = ref(0);
const resendCooldownSeconds = ref(0);
let timerInterval = null;

const formattedTimeLeft = computed(() => {
    const m = Math.floor(timeLeftSeconds.value / 60);
    const s = timeLeftSeconds.value % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
});

const isExpired = computed(() => timeLeftSeconds.value <= 0);
const canResend = computed(() => resendCooldownSeconds.value <= 0);
const isComplete = computed(() => digits.value.every((d) => d !== ''));

// ── Email mask ────────────────────────────────────────────────────────────────
const maskedEmail = computed(() => {
    if (!props.email) return '';
    const [local, domain] = props.email.split('@');
    if (!local || local.length <= 2) return props.email;
    return `${local[0]}${'*'.repeat(local.length - 2)}${local[local.length - 1]}@${domain}`;
});

// ── Timer logic ───────────────────────────────────────────────────────────────
function updateTimers() {
    const now = Date.now();
    timeLeftSeconds.value = props.expires_at
        ? Math.max(0, Math.floor((new Date(props.expires_at).getTime() - now) / 1000))
        : 0;
    resendCooldownSeconds.value = props.resend_available_at
        ? Math.max(0, Math.floor((new Date(props.resend_available_at).getTime() - now) / 1000))
        : 0;
}

function initTimers() {
    clearInterval(timerInterval);
    updateTimers();
    timerInterval = setInterval(updateTimers, 1000);
}

// ── OTP input handlers ────────────────────────────────────────────────────────
function handleInput(index, event) {
    const val = event.target.value.replace(/\D/g, '').slice(-1);
    digits.value[index] = val;
    if (val && index < 5) {
        nextTick(() => inputRefs.value[index + 1]?.focus());
    }
}

function handleKeydown(index, event) {
    if (event.key === 'Backspace') {
        if (digits.value[index]) {
            digits.value[index] = '';
        } else if (index > 0) {
            digits.value[index - 1] = '';
            nextTick(() => inputRefs.value[index - 1]?.focus());
        }
    } else if (event.key === 'ArrowLeft' && index > 0) {
        nextTick(() => inputRefs.value[index - 1]?.focus());
    } else if (event.key === 'ArrowRight' && index < 5) {
        nextTick(() => inputRefs.value[index + 1]?.focus());
    }
}

function handlePaste(event) {
    event.preventDefault();
    const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);
    pasted.split('').forEach((char, i) => {
        if (i < 6) digits.value[i] = char;
    });
    const focusIdx = Math.min(pasted.length, 5);
    nextTick(() => inputRefs.value[focusIdx]?.focus());
}

function handleFocus(event) {
    event.target.select();
}

// ── Auto-submit when all 6 digits filled ──────────────────────────────────────
watch(isComplete, (val) => {
    if (val) submit();
});

// ── Submit ────────────────────────────────────────────────────────────────────
function submit() {
    if (!isComplete.value || form.processing || isExpired.value) return;
    form.code = digits.value.join('');
    form.post(route('verification.verify'), {
        onError: () => {
            digits.value = ['', '', '', '', '', ''];
            nextTick(() => inputRefs.value[0]?.focus());
        },
    });
}

// ── Resend ────────────────────────────────────────────────────────────────────
function resend() {
    if (!canResend.value || resendForm.processing) return;
    resendForm.post(route('verification.send'), {
        onSuccess: () => {
            digits.value = ['', '', '', '', '', ''];
            nextTick(() => inputRefs.value[0]?.focus());
        },
    });
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    setTheme(getPreferredTheme());
    initTimers();
    nextTick(() => inputRefs.value[0]?.focus());
});

watch([() => props.expires_at, () => props.resend_available_at], initTimers);

onUnmounted(() => clearInterval(timerInterval));
</script>

<template>
    <Head title="Verify Your Email — NexaHub" />

    <div
        class="relative min-h-screen bg-white dark:bg-[#070d1a] text-slate-900 dark:text-white transition-colors duration-300 flex flex-col items-center justify-center px-5 py-20"
    >
        <!-- Ambient glows -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 right-0 h-[500px] w-[500px] rounded-full bg-sky-400/8 dark:bg-sky-500/10 blur-[100px]"
            ></div>
            <div
                class="absolute bottom-0 -left-32 h-[400px] w-[400px] rounded-full bg-blue-400/6 dark:bg-blue-500/8 blur-[80px]"
            ></div>
        </div>

        <!-- Top bar -->
        <header class="absolute inset-x-0 top-0 z-20">
            <div class="mx-auto flex h-[68px] max-w-7xl items-center justify-between px-5 sm:px-8">
                <Link href="/" class="group flex items-center gap-3">
                    <div class="relative">
                        <div
                            class="absolute inset-0 rounded-xl bg-sky-500/25 blur-md transition-all group-hover:blur-lg"
                        ></div>
                        <div
                            class="relative flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 shadow-lg"
                        >
                            <Layers3 class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <span class="text-base font-black tracking-tight text-slate-900 dark:text-white"
                        >Nexa<span class="text-sky-500">Hub</span></span
                    >
                </Link>

                <button
                    class="rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-2.5 text-slate-600 dark:text-slate-400 transition-all hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:border-sky-200 dark:hover:border-sky-500/30 hover:text-sky-600 dark:hover:text-sky-300"
                    @click="toggleTheme"
                    aria-label="Toggle theme"
                >
                    <component :is="themeIcon" class="h-4 w-4" />
                </button>
            </div>
        </header>

        <!-- Content -->
        <div class="relative w-full max-w-[440px]">

            <!-- Resend success banner -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="status === 'verification-link-sent'"
                    class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 dark:border-emerald-500/25 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3.5"
                >
                    <CheckCircle2
                        class="h-5 w-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5"
                    />
                    <div>
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">
                            New code sent!
                        </p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">
                            A fresh 6-digit code has been sent to your email.
                        </p>
                    </div>
                </div>
            </Transition>

            <!-- Main card -->
            <div
                class="rounded-3xl border border-slate-200/80 dark:border-sky-500/20 bg-white dark:bg-[#0d1b2e] p-8 shadow-2xl shadow-slate-900/8 dark:shadow-[0_32px_80px_-12px_rgba(14,165,233,0.12)]"
            >
                <!-- Mail icon -->
                <div class="flex justify-center">
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400/15 to-blue-500/15 dark:from-sky-500/20 dark:to-blue-600/15 border border-sky-200/60 dark:border-sky-500/25 shadow-md dark:shadow-[0_8px_24px_rgba(14,165,233,0.12)]"
                    >
                        <Mail class="h-7 w-7 text-sky-600 dark:text-sky-400" />
                    </div>
                </div>

                <!-- Heading -->
                <div class="mt-6 text-center">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                        Enter verification code
                    </h1>
                    <p class="mt-2.5 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        We sent a 6-digit code to<br />
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{
                            maskedEmail
                        }}</span>
                    </p>
                </div>

                <!-- Expiry timer -->
                <div class="mt-5 flex justify-center">
                    <div
                        v-if="!isExpired"
                        class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 dark:border-sky-500/25 bg-sky-50 dark:bg-sky-500/10 px-3.5 py-1.5"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-sky-500 animate-pulse flex-shrink-0"
                        ></span>
                        <span class="text-xs font-semibold text-sky-700 dark:text-sky-300">
                            Expires in {{ formattedTimeLeft }}
                        </span>
                    </div>
                    <div
                        v-else
                        class="inline-flex items-center gap-1.5 rounded-full border border-red-200 dark:border-red-500/25 bg-red-50 dark:bg-red-500/10 px-3.5 py-1.5"
                    >
                        <AlertCircle
                            class="h-3.5 w-3.5 text-red-500 dark:text-red-400 flex-shrink-0"
                        />
                        <span class="text-xs font-semibold text-red-700 dark:text-red-400">
                            Code expired — request a new one
                        </span>
                    </div>
                </div>

                <!-- OTP input boxes -->
                <div class="mt-8 flex items-center justify-center gap-2 sm:gap-3">
                    <input
                        v-for="(digit, i) in digits"
                        :key="i"
                        :ref="(el) => (inputRefs[i] = el)"
                        :value="digit"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="2"
                        :class="[
                            'w-11 h-14 sm:w-[52px] sm:h-[60px] rounded-xl border-2 text-center text-xl sm:text-2xl font-black transition-all duration-150 outline-none',
                            'bg-slate-50 dark:bg-[#111827] text-slate-900 dark:text-white',
                            form.errors.code
                                ? 'border-red-400 dark:border-red-500/70 bg-red-50/50 dark:bg-red-950/20'
                                : digit
                                  ? 'border-sky-400 dark:border-sky-500 shadow-[0_0_0_3px_rgba(56,189,248,0.12)] dark:shadow-[0_0_14px_rgba(14,165,233,0.22)]'
                                  : 'border-slate-200 dark:border-white/10 focus:border-sky-400 dark:focus:border-sky-500 focus:shadow-[0_0_0_3px_rgba(56,189,248,0.1)] dark:focus:shadow-[0_0_14px_rgba(14,165,233,0.15)]',
                        ]"
                        @input="handleInput(i, $event)"
                        @keydown="handleKeydown(i, $event)"
                        @paste="handlePaste"
                        @focus="handleFocus"
                    />
                </div>

                <!-- Error message -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="form.errors.code"
                        class="mt-4 flex items-center justify-center gap-1.5"
                    >
                        <AlertCircle
                            class="h-4 w-4 flex-shrink-0 text-red-500 dark:text-red-400"
                        />
                        <p class="text-sm text-red-600 dark:text-red-400 text-center">
                            {{ form.errors.code }}
                        </p>
                    </div>
                </Transition>

                <!-- Verify button -->
                <button
                    type="button"
                    :disabled="!isComplete || form.processing || isExpired"
                    @click="submit"
                    :class="[
                        'mt-6 w-full flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 text-sm font-bold text-white shadow-lg transition-all duration-150',
                        isComplete && !isExpired
                            ? 'bg-sky-500 shadow-sky-500/25 dark:shadow-sky-500/30 hover:bg-sky-600 hover:shadow-sky-500/40 dark:hover:shadow-sky-500/50 hover:-translate-y-px active:translate-y-0'
                            : 'bg-slate-300 dark:bg-slate-700 shadow-none cursor-not-allowed opacity-60',
                    ]"
                >
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <span
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        Verifying…
                    </span>
                    <span v-else class="flex items-center gap-2">
                        Verify Code
                        <ArrowRight class="h-4 w-4" />
                    </span>
                </button>

                <!-- Divider -->
                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-slate-100 dark:bg-white/8"></div>
                    <span class="text-xs text-slate-400 dark:text-slate-400">Didn't receive it?</span>
                    <div class="h-px flex-1 bg-slate-100 dark:bg-white/8"></div>
                </div>

                <!-- Resend button -->
                <button
                    type="button"
                    :disabled="!canResend || resendForm.processing"
                    @click="resend"
                    :class="[
                        'w-full flex items-center justify-center gap-2 rounded-xl border py-3 text-sm font-semibold transition-all duration-150',
                        canResend
                            ? 'border-slate-200 dark:border-sky-500/25 text-slate-700 dark:text-slate-300 hover:border-sky-300 dark:hover:border-sky-500/40 hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-700 dark:hover:text-sky-200'
                            : 'border-slate-100 dark:border-white/8 text-slate-400 dark:text-slate-600 cursor-not-allowed',
                    ]"
                >
                    <span v-if="resendForm.processing" class="flex items-center gap-2">
                        <span
                            class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                        ></span>
                        Sending…
                    </span>
                    <span v-else class="flex items-center gap-2">
                        <RefreshCw class="h-4 w-4" />
                        {{
                            canResend
                                ? 'Resend verification code'
                                : `Resend available in ${resendCooldownSeconds}s`
                        }}
                    </span>
                </button>

                <!-- Security note -->
                <div
                    class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400 dark:text-slate-400"
                >
                    <ShieldCheck class="h-3.5 w-3.5 flex-shrink-0" />
                    <span>Code expires in 10 min · Never share it with anyone</span>
                </div>
            </div>

            <!-- Sign out -->
            <div class="mt-5 text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors"
                >
                    Sign out and use a different account
                </Link>
            </div>
        </div>
    </div>
</template>

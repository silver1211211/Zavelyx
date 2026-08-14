<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { getPreferredTheme, setThemeInstant } from '@/utils/theme';
import { Eye, EyeOff, Loader2, Moon, Shield, Sun, Zap } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const isDark = ref(false);
const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
});

function submit() {
    form.post(route('admin.login.post'), { preserveScroll: true });
}

function toggleTheme() {
    isDark.value = !isDark.value;
    setThemeInstant(isDark.value ? 'dark' : 'light');
}

onMounted(() => {
    isDark.value = getPreferredTheme() === 'dark';
    setThemeInstant(isDark.value ? 'dark' : 'light');
});
</script>

<template>
    <Head title="Admin Login — NexaHub" />

    <div class="min-h-screen bg-[#f0f4f8] dark:bg-[#060d18] flex items-center justify-center p-4 transition-colors duration-300">

        <!-- Theme toggle (top right) -->
        <button
            @click="toggleTheme"
            class="fixed top-4 right-4 p-2 rounded-xl border border-slate-200 dark:border-white/8 bg-white dark:bg-[#0d1e35] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm"
        >
            <Sun v-if="isDark" class="w-4 h-4" />
            <Moon v-else class="w-4 h-4" />
        </button>

        <!-- Login card -->
        <div class="w-full max-w-[360px]">

            <!-- Logo -->
            <div class="flex flex-col items-center mb-8">
                <div class="relative mb-4">
                    <div class="absolute inset-0 bg-sky-500/20 rounded-2xl blur-xl" />
                    <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-xl shadow-sky-500/30">
                        <Zap class="w-7 h-7 text-white" :stroke-width="2.5" />
                    </div>
                </div>
                <h1 class="text-[22px] font-black tracking-tight text-slate-900 dark:text-white">
                    Nexa<span class="text-sky-500">Hub</span>
                </h1>
                <div class="flex items-center gap-1.5 mt-1">
                    <Shield class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <p class="text-[12px] font-medium text-slate-400 dark:text-slate-600 uppercase tracking-widest">Admin Panel</p>
                </div>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 shadow-xl dark:shadow-[0_8px_48px_rgba(0,0,0,0.4)] overflow-hidden">

                <!-- Card header -->
                <div class="px-6 pt-6 pb-5 border-b border-slate-100 dark:border-sky-500/8">
                    <h2 class="text-[16px] font-bold text-slate-900 dark:text-white">Administrator Login</h2>
                    <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Enter your credentials to access the admin panel.</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="px-6 py-5 space-y-4">

                    <!-- General error -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="form.errors.username" class="flex items-center gap-2.5 p-3 bg-rose-50 dark:bg-rose-500/8 border border-rose-200 dark:border-rose-500/20 rounded-xl">
                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full flex-shrink-0"></span>
                            <p class="text-[12px] font-medium text-rose-600 dark:text-rose-400">{{ form.errors.username }}</p>
                        </div>
                    </Transition>

                    <!-- Username -->
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Username</label>
                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="username"
                            placeholder="Enter username"
                            :class="[
                                'w-full h-10 px-3.5 text-[13.5px] bg-slate-50 dark:bg-white/5 border rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:bg-white dark:focus:bg-white/8 focus:border-sky-300 dark:focus:border-sky-500/30 transition-all',
                                form.errors.username && !form.errors.username.includes('credentials')
                                    ? 'border-rose-400 dark:border-rose-500/50'
                                    : 'border-slate-200 dark:border-white/8',
                            ]"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Password</label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="Enter password"
                                class="w-full h-10 pl-3.5 pr-10 text-[13.5px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:bg-white dark:focus:bg-white/8 focus:border-sky-300 dark:focus:border-sky-500/30 transition-all"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="w-3.5 h-3.5" />
                                <Eye v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing || !form.username || !form.password"
                        class="w-full h-11 flex items-center justify-center gap-2 bg-sky-500 hover:bg-sky-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[14px] font-bold rounded-xl shadow-lg shadow-sky-500/30 hover:shadow-sky-500/40 transition-all"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Shield v-else class="w-4 h-4" />
                        {{ form.processing ? 'Verifying…' : 'Sign In to Admin' }}
                    </button>
                </form>
            </div>

            <!-- Footer note -->
            <p class="text-center text-[11px] text-slate-400 dark:text-slate-600 mt-5">
                NexaHub Admin — Restricted Access
            </p>
        </div>
    </div>
</template>

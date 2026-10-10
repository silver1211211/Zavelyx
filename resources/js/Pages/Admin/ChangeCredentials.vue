<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Loader2, ShieldCheck } from 'lucide-vue-next';

defineProps({
    current_username: { type: String, default: '' },
});

const form = useForm({
    current_password: '',
    username: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('admin.credentials.update'));
}
</script>

<template>
    <Head title="Secure Administrator Credentials — Zavelyx" />
    <main class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-[#060d18] p-4">
        <div class="w-full max-w-lg">
            <BrandLogo class="mx-auto mb-6 h-12 w-[190px]" />
            <section class="rounded-2xl border border-slate-200 dark:border-sky-500/15 bg-white dark:bg-[#0d1e35] p-6 shadow-xl">
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-500/25 bg-amber-50 dark:bg-amber-500/10 p-4">
                    <AlertTriangle class="mt-0.5 h-5 w-5 flex-none text-amber-600" />
                    <div>
                        <h1 class="font-bold text-slate-900 dark:text-white">Replace temporary credentials</h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            Choose a new username and strong password before accessing the administrator dashboard.
                        </p>
                    </div>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="label">Temporary password</label>
                        <input v-model="form.current_password" type="password" autocomplete="current-password" class="input" />
                        <p v-if="form.errors.current_password" class="error">{{ form.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="label">New administrator username</label>
                        <input v-model="form.username" type="text" autocomplete="username" class="input" :placeholder="`Different from ${current_username}`" />
                        <p v-if="form.errors.username" class="error">{{ form.errors.username }}</p>
                    </div>
                    <div>
                        <label class="label">New password</label>
                        <input v-model="form.password" type="password" autocomplete="new-password" class="input" />
                        <p class="mt-1 text-xs text-slate-400">At least 12 characters with uppercase, lowercase, a number, and a symbol.</p>
                        <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label">Confirm new password</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="input" />
                    </div>
                    <button type="submit" :disabled="form.processing" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-sky-500 font-bold text-white disabled:opacity-60">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <ShieldCheck v-else class="h-4 w-4" />
                        Secure administrator account
                    </button>
                </form>
            </section>
        </div>
    </main>
</template>

<style scoped>
.label { @apply mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300; }
.input { @apply h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-500/20 dark:border-white/10 dark:bg-white/5 dark:text-white; }
.error { @apply mt-1 text-xs text-rose-500; }
</style>

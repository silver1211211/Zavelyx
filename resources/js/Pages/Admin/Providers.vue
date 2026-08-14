<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ providers: Object });
</script>

<template>
    <Head title="Provider Management" />
    <AuthenticatedLayout>
        <div class="fin-card">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-sky-600">Integrations</p>
                    <h1 class="text-2xl font-black">Provider management</h1>
                </div>
                <button class="fin-button">Add provider</button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="provider in providers.data" :key="provider.id" class="rounded-2xl border border-sky-100 p-5 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="font-black">{{ provider.name }}</h2>
                            <p class="mt-1 text-sm uppercase text-slate-500">{{ provider.type }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="provider.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'">
                            {{ provider.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mt-5 text-sm text-slate-500 dark:text-slate-400">{{ provider.base_url || 'Provider endpoint pending' }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

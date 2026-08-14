<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Grid3x3, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    services: { type: Array, default: () => [] },
});

const { symbol, formatAmount: convertPrice } = useCurrency();

const platformEmoji = {
    instagram: '📸', tiktok: '🎵', youtube: '▶️',
    telegram: '✈️', facebook: '👤', twitter: '🐦', x: '🐦',
};

function platformIcon(slug) {
    const s = slug?.toLowerCase() ?? '';
    for (const [key, emoji] of Object.entries(platformEmoji)) {
        if (s.includes(key)) return emoji;
    }
    return '📊';
}

const searchQuery = ref('');
const selectedCategory = ref(null);

const categories = computed(() => {
    const map = new Map();
    for (const s of props.services) {
        const cat = s.category ?? { id: 0, name: 'Other', slug: 'other' };
        if (!map.has(cat.id)) map.set(cat.id, { ...cat, count: 0 });
        map.get(cat.id).count++;
    }
    return Array.from(map.values());
});

const visibleServices = computed(() => {
    let list = props.services;
    if (selectedCategory.value !== null) list = list.filter(s => s.category_id === selectedCategory.value);
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(s => s.name.toLowerCase().includes(q));
    }
    return list;
});

// Group by category for display
const groupedServices = computed(() => {
    const map = new Map();
    for (const s of visibleServices.value) {
        const catId = s.category_id ?? 0;
        const cat = s.category ?? { id: catId, name: 'Other', slug: 'other' };
        if (!map.has(catId)) map.set(catId, { ...cat, services: [] });
        map.get(catId).services.push(s);
    }
    return Array.from(map.values());
});
</script>

<template>
    <Head title="Services" />
    <AuthenticatedLayout>
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">SMM Services</h1>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">Browse {{ services.length }} available services across all platforms.</p>
            </div>
            <Link :href="route('orders.create')" class="flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-[13px] font-semibold rounded-xl shadow-lg shadow-sky-500/30 transition-all">
                <Plus class="w-4 h-4" />Order Now
            </Link>
        </div>

        <!-- Search + filter bar -->
        <div class="mb-5 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-600 pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search services..."
                        class="w-full h-9 pl-9 pr-4 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 transition-all"
                    />
                </div>
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button
                        @click="selectedCategory = null"
                        :class="['flex-shrink-0 px-3 py-1.5 rounded-xl text-[12px] font-semibold transition-all',
                            selectedCategory === null
                                ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30'
                                : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10']"
                    >All ({{ services.length }})</button>
                    <button
                        v-for="cat in categories" :key="cat.id"
                        @click="selectedCategory = cat.id"
                        :class="['flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12px] font-semibold transition-all',
                            selectedCategory === cat.id
                                ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30'
                                : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10']"
                    >
                        <span>{{ platformIcon(cat.slug) }}</span>
                        {{ cat.name }}
                        <span class="text-[10px] opacity-70">({{ cat.count }})</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="groupedServices.length === 0" class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 py-16 flex flex-col items-center gap-3 text-center px-6">
            <Grid3x3 class="w-10 h-10 text-slate-300 dark:text-slate-700" />
            <template v-if="services.length === 0">
                <p class="text-[14px] font-semibold text-slate-600 dark:text-slate-400">No services available yet</p>
                <p class="text-[12px] text-slate-400 dark:text-slate-600 max-w-xs">Services will appear here once they are imported by the admin. Check back soon.</p>
            </template>
            <template v-else>
                <p class="text-[14px] font-semibold text-slate-600 dark:text-slate-400">No services match your search</p>
                <p class="text-[12px] text-slate-400 dark:text-slate-600">Try a different search term or category.</p>
            </template>
        </div>

        <!-- Service groups by category -->
        <div v-else class="space-y-6">
            <div v-for="group in groupedServices" :key="group.id">
                <!-- Category header -->
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-2xl">{{ platformIcon(group.slug) }}</span>
                    <h2 class="text-[15px] font-bold text-slate-800 dark:text-white">{{ group.name }}</h2>
                    <span class="text-[11px] font-semibold px-2 py-0.5 bg-sky-500/10 text-sky-600 dark:text-sky-400 rounded-full">{{ group.services.length }} services</span>
                </div>

                <!-- Services table -->
                <div class="bg-white dark:bg-[var(--surface-card)] rounded-2xl border border-slate-200 dark:border-white/[0.08] overflow-hidden">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-white/[0.08] bg-slate-50 dark:bg-white/[0.04]">
                                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Service</th>
                                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Rate / 1K</th>
                                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Min</th>
                                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Max</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            <tr v-for="service in group.services" :key="service.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.04] transition-colors">
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ service.name }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-bold text-sky-600 dark:text-sky-400">{{ symbol }}{{ convertPrice(service.selling_price) }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400">{{ (service.min_amount ?? 10).toLocaleString() }}</td>
                                <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400">{{ (service.max_amount ?? 100000).toLocaleString() }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    <Link
                                        :href="route('orders.create') + '?service=' + service.id"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-500/10 hover:bg-sky-500 text-sky-600 dark:text-sky-400 hover:text-white text-[11px] font-semibold rounded-lg transition-all"
                                    >
                                        <Plus class="w-3 h-3" />Order
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

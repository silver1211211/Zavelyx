<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle, ChevronRight, Palette } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    presets: Object,
    current: Object,
});

const flash = computed(() => usePage().props.flash ?? {});

const form = useForm({
    preset:          props.current.preset,
    primary:         props.current.primary,
    secondary:       props.current.secondary,
    accent:          props.current.accent,
    dark_bg:         props.current.dark_bg,
    dark_card:       props.current.dark_card,
    glow:            props.current.glow,
    glow_intensity:  props.current.glow_intensity,
    border_radius:   props.current.border_radius,
});

function applyPreset(key) {
    const preset = props.presets[key];
    if (!preset) return;
    form.preset    = key;
    form.primary   = preset.primary;
    form.secondary = preset.secondary;
    form.accent    = preset.accent;
    form.dark_bg   = preset.dark_bg;
    form.dark_card = preset.dark_card;
    form.glow      = preset.glow;
}

function applyLiveTheme() {
    const root = document.documentElement;
    root.style.setProperty('--color-primary',   form.primary);
    root.style.setProperty('--color-secondary', form.secondary);
    root.style.setProperty('--color-accent',    form.accent);
    root.style.setProperty('--color-dark-bg',   form.dark_bg);
    root.style.setProperty('--color-dark-card', form.dark_card);
    root.style.setProperty('--color-glow',      form.glow);
    root.style.setProperty('--glow-intensity',  form.glow_intensity);
}

watch(
    [() => form.primary, () => form.secondary, () => form.accent, () => form.dark_bg, () => form.dark_card, () => form.glow, () => form.glow_intensity],
    ([primary, secondary, accent], [oldPrimary, oldSecondary, oldAccent]) => {
        if (primary !== oldPrimary || secondary !== oldSecondary || accent !== oldAccent) {
            form.preset = 'custom';
        }
        applyLiveTheme();
    },
);

function save() {
    form.post(route('admin.settings.theme.save'));
}

const previewStyle = computed(() => ({
    '--preview-primary':   form.primary,
    '--preview-secondary': form.secondary,
    '--preview-accent':    form.accent,
    '--preview-bg':        form.dark_bg,
    '--preview-card':      form.dark_card,
}));

const radiusOptions = [
    { value: 'none', label: 'None', class: 'rounded-none' },
    { value: 'sm',   label: 'Small', class: 'rounded' },
    { value: 'md',   label: 'Medium', class: 'rounded-md' },
    { value: 'lg',   label: 'Large', class: 'rounded-lg' },
    { value: 'xl',   label: 'XL', class: 'rounded-xl' },
    { value: '2xl',  label: '2XL', class: 'rounded-2xl' },
];
</script>

<template>
    <Head title="Theme Settings — Admin" />
    <AdminLayout>

        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-6">
            <Link :href="route('admin.settings.index')" class="hover:text-sky-500 transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-medium">Theme</span>
        </div>

        <div v-if="flash.success" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>

        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-pink-500 dark:text-pink-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Theme Settings</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">Choose a preset or customize your platform's color palette.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">

            <!-- Left: Controls -->
            <div class="space-y-5">

                <!-- Presets -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Color Presets</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <button v-for="(preset, key) in presets" :key="key"
                            @click="applyPreset(key)"
                            :class="['group relative p-4 rounded-xl border-2 text-left transition-all duration-150',
                                form.preset === key
                                    ? 'border-sky-500 bg-sky-50 dark:bg-sky-500/10 shadow-sky-500/20 shadow-lg'
                                    : 'border-slate-200 dark:border-white/8 hover:border-slate-300 dark:hover:border-white/15']">
                            <!-- Color dots -->
                            <div class="flex items-center gap-1.5 mb-2.5">
                                <div class="w-4 h-4 rounded-full shadow-sm" :style="{ background: preset.primary }" />
                                <div class="w-4 h-4 rounded-full shadow-sm" :style="{ background: preset.secondary }" />
                                <div class="w-4 h-4 rounded-full shadow-sm" :style="{ background: preset.accent }" />
                                <div class="w-4 h-4 rounded-full shadow-sm border border-black/10" :style="{ background: preset.dark_bg }" />
                            </div>
                            <p class="text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ preset.name }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5">{{ preset.description }}</p>
                            <div v-if="form.preset === key" class="absolute top-2 right-2">
                                <div class="w-5 h-5 rounded-full bg-sky-500 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Custom Colors -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">Custom Colors</h2>
                    <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-4">Editing any color below switches to "custom" preset.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div v-for="field in [
                            { key: 'primary',   label: 'Primary' },
                            { key: 'secondary', label: 'Secondary' },
                            { key: 'accent',    label: 'Accent' },
                            { key: 'dark_bg',   label: 'Dark Background' },
                            { key: 'dark_card', label: 'Dark Card' },
                            { key: 'glow',      label: 'Glow Color' },
                        ]" :key="field.key">
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ field.label }}</label>
                            <div class="flex items-center gap-2">
                                <input type="color" v-model="form[field.key]"
                                    class="w-9 h-9 rounded-lg border border-slate-200 dark:border-white/10 cursor-pointer bg-transparent p-0.5 overflow-hidden" />
                                <input type="text" v-model="form[field.key]" maxlength="7"
                                    class="flex-1 px-2.5 py-2 text-[12px] font-mono bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/30" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glow & Radius -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Style Options</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                Glow Intensity — <span class="font-mono text-sky-500">{{ form.glow_intensity }}</span>
                            </label>
                            <input type="range" v-model.number="form.glow_intensity" min="0" max="1" step="0.05"
                                class="w-full accent-sky-500" />
                            <div class="flex justify-between text-[11px] text-slate-400 mt-0.5">
                                <span>None</span><span>Max</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Border Radius</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="opt in radiusOptions" :key="opt.value"
                                    @click="form.border_radius = opt.value"
                                    :class="['px-3 py-1.5 text-[12px] font-semibold border transition-all', opt.class,
                                        form.border_radius === opt.value
                                            ? 'border-sky-500 bg-sky-50 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400'
                                            : 'border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:border-slate-300']">
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button @click="save" :disabled="form.processing" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-white text-[13px] font-bold shadow-pink-500/25 shadow-lg hover:from-pink-600 hover:to-purple-600 hover:-translate-y-px transition-all active:scale-95 disabled:opacity-60 disabled:translate-y-0 disabled:cursor-not-allowed">
                        {{ form.processing ? 'Saving…' : 'Apply Theme' }}
                    </button>
                </div>
            </div>

            <!-- Right: Live Preview -->
            <div class="xl:sticky xl:top-24 h-fit">
                <div class="rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/[0.06]">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Live Preview</p>
                    </div>
                    <div class="p-4" :style="{ background: form.dark_bg }">
                        <!-- Nav preview -->
                        <div class="flex items-center gap-2 mb-4 px-3 py-2 rounded-xl" :style="{ background: form.dark_card }">
                            <div class="w-5 h-5 rounded-lg flex-shrink-0" :style="{ background: form.primary }" />
                            <span class="text-[11px] font-bold text-white">NexaHub</span>
                            <div class="ml-auto flex gap-1.5">
                                <div class="h-4 w-10 rounded-md" :style="{ background: form.primary + '30' }" />
                                <div class="h-4 w-14 rounded-md" :style="{ background: form.primary }" />
                            </div>
                        </div>
                        <!-- Stats preview -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div v-for="i in 3" :key="i" class="rounded-xl p-2.5" :style="{ background: form.dark_card, boxShadow: `0 0 12px ${form.glow}${Math.round(form.glow_intensity * 99).toString(16).padStart(2,'0')}` }">
                                <div class="h-4 w-10 rounded mb-1" :style="{ background: i === 1 ? form.primary : i === 2 ? form.secondary : form.accent }" />
                                <div class="h-2 w-8 rounded" :style="{ background: '#ffffff15' }" />
                            </div>
                        </div>
                        <!-- Card preview -->
                        <div class="rounded-xl p-3 mb-4" :style="{ background: form.dark_card, border: `1px solid ${form.primary}20` }">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-7 h-7 rounded-lg" :style="{ background: form.primary }" />
                                <div>
                                    <div class="h-2.5 w-20 rounded mb-1" :style="{ background: '#ffffff30' }" />
                                    <div class="h-1.5 w-14 rounded" :style="{ background: '#ffffff15' }" />
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="h-7 flex-1 rounded-lg" :style="{ background: form.primary }" />
                                <div class="h-7 flex-1 rounded-lg" :style="{ background: form.secondary + '40' }" />
                            </div>
                        </div>
                        <!-- Badge -->
                        <div class="text-center">
                            <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full" :style="{ background: form.accent + '25', color: form.accent }">Active Theme</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

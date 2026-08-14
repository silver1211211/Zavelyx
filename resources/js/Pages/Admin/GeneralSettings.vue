<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle,
    ChevronRight,
    Home,
    Image,
    ImagePlus,
    Search,
    Settings,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    general:  Object,
    seo:      Object,
    homepage: Object,
    branding: Object,
});

const flash = computed(() => usePage().props.flash ?? {});
const activeTab = ref('general');

const tabs = [
    { key: 'general',  label: 'General',  icon: Settings  },
    { key: 'branding', label: 'Branding', icon: ImagePlus  },
    { key: 'seo',      label: 'SEO',      icon: Search     },
    { key: 'homepage', label: 'Homepage', icon: Home       },
];

// ── General form ──────────────────────────────────────────────────────────────
const generalForm = useForm({ ...props.general });
function saveGeneral() {
    generalForm.post(route('admin.settings.general.save-general'));
}

// ── SEO form ──────────────────────────────────────────────────────────────────
const seoForm = useForm({ ...props.seo });
function saveSeo() {
    seoForm.post(route('admin.settings.general.save-seo'));
}

// ── Homepage form ─────────────────────────────────────────────────────────────
const homepageForm = useForm({
    ...props.homepage,
    announcement_cta:    props.homepage.announcement_cta    ?? '',
    announcement_color:  props.homepage.announcement_color  ?? 'sky',
    announcement_icon:   props.homepage.announcement_icon   ?? '',
    announcement_pinned: props.homepage.announcement_pinned ?? false,
});
function saveHomepage() {
    homepageForm.post(route('admin.settings.general.save-homepage'));
}

// ── Branding: multi-logo system ───────────────────────────────────────────────
const LOGO_SLOTS = [
    { type: 'main',      label: 'Main Logo',       desc: 'Primary logo used on homepage and as default fallback',              accept: 'image/png,image/jpeg,image/webp,image/svg+xml', field: 'logo', maxMb: 2  },
    { type: 'admin',     label: 'Admin Logo',       desc: 'Shown in the admin panel sidebar. Falls back to Main Logo.',         accept: 'image/png,image/jpeg,image/webp,image/svg+xml', field: 'logo', maxMb: 2  },
    { type: 'dashboard', label: 'Dashboard Logo',   desc: 'Shown in the user dashboard sidebar. Falls back to Main Logo.',     accept: 'image/png,image/jpeg,image/webp,image/svg+xml', field: 'logo', maxMb: 2  },
    { type: 'auth',      label: 'Auth Logo',        desc: 'Shown on login, register, forgot-password pages. Falls back to Main Logo.', accept: 'image/png,image/jpeg,image/webp,image/svg+xml', field: 'logo', maxMb: 2  },
    { type: 'footer',    label: 'Footer Logo',      desc: 'Shown in the homepage footer. Falls back to Main Logo.',            accept: 'image/png,image/jpeg,image/webp,image/svg+xml', field: 'logo', maxMb: 2  },
    { type: 'favicon',   label: 'Favicon',          desc: 'Browser tab icon. Supports ICO, PNG, SVG.',                        accept: 'image/x-icon,image/png,image/webp,image/svg+xml', field: 'favicon', maxMb: 0.5 },
];

// Per-slot state
const brandingPreviews = ref(
    Object.fromEntries(LOGO_SLOTS.map(s => [s.type, props.branding?.[s.type === 'favicon' ? 'favicon' : `logo_${s.type}`] ?? '']))
);
const brandingFiles    = ref(Object.fromEntries(LOGO_SLOTS.map(s => [s.type, null])));
const brandingForms    = Object.fromEntries(
    LOGO_SLOTS.map(s => [s.type, useForm({ [s.field]: null })])
);
const brandingInputs   = ref(Object.fromEntries(LOGO_SLOTS.map(s => [s.type, null])));
const deletingType     = ref(null);

function onBrandingFileChange(e, slot) {
    const file = e.target.files[0];
    if (!file) return;
    brandingFiles.value[slot.type]          = file;
    brandingForms[slot.type][slot.field]    = file;
    brandingPreviews.value[slot.type]       = URL.createObjectURL(file);
}

function uploadBranding(slot) {
    brandingForms[slot.type].post(route('admin.settings.general.upload-branding', { type: slot.type }), {
        forceFormData: true,
        onSuccess: () => {
            brandingFiles.value[slot.type]       = null;
            brandingForms[slot.type][slot.field] = null;
        },
    });
}

function deleteBranding(type) {
    deletingType.value = type;
    router.delete(route('admin.settings.general.delete-branding', { type }), {
        onSuccess: () => {
            brandingPreviews.value[type] = '';
            brandingFiles.value[type]   = null;
            deletingType.value          = null;
        },
        onError: () => { deletingType.value = null; },
    });
}

// ── Timezones ─────────────────────────────────────────────────────────────────
const timezones = [
    'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'America/Toronto', 'America/Sao_Paulo', 'Europe/London', 'Europe/Paris', 'Europe/Berlin',
    'Europe/Moscow', 'Africa/Lagos', 'Africa/Nairobi', 'Africa/Johannesburg',
    'Asia/Dubai', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Dhaka', 'Asia/Bangkok',
    'Asia/Singapore', 'Asia/Shanghai', 'Asia/Tokyo', 'Australia/Sydney',
];
</script>

<template>
    <Head title="General Settings — Admin" />
    <AdminLayout>

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-6">
            <Link :href="route('admin.settings.index')" class="hover:text-sky-500 transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-medium">General Settings</span>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />{{ flash.success }}
        </div>

        <!-- Header -->
        <div class="mb-6">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-500 dark:text-sky-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">General Settings</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">Configure site identity, SEO meta tags, and homepage content.</p>
        </div>

        <!-- Tabs -->
        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-white/5 rounded-xl mb-6 flex-wrap">
            <button v-for="tab in tabs" :key="tab.key"
                @click="activeTab = tab.key"
                :class="['flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-semibold transition-all duration-150',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-[#0d1e35] text-sky-600 dark:text-sky-400 shadow-sm border border-slate-200/60 dark:border-sky-500/20'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']">
                <component :is="tab.icon" class="w-3.5 h-3.5" />
                {{ tab.label }}
            </button>
        </div>

        <!-- ── GENERAL TAB ─────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'general'" class="space-y-5">

            <!-- Site Identity -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Site Identity</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Site Name</label>
                            <input v-model="generalForm.site_name" type="text" class="input" placeholder="Zavelyx" />
                            <p v-if="generalForm.errors.site_name" class="field-error">{{ generalForm.errors.site_name }}</p>
                        </div>
                        <div>
                            <label class="label">Tagline</label>
                            <input v-model="generalForm.site_tagline" type="text" class="input" placeholder="Global SMS & Virtual Number Infrastructure" />
                        </div>
                    </div>
                    <div>
                        <label class="label">Site Description <span class="text-slate-400 font-normal normal-case">(used in meta tags)</span></label>
                        <textarea v-model="generalForm.site_description" rows="2" class="input resize-none" placeholder="Short description of your platform…" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Timezone</label>
                            <select v-model="generalForm.timezone" class="input">
                                <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Business Address</label>
                            <input v-model="generalForm.business_address" type="text" class="input" placeholder="123 Main St, City, Country" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support & Contact -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">Support & Contact</h2>
                <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-4">These appear in emails and platform info sections.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Support Email</label>
                        <input v-model="generalForm.support_email" type="email" class="input" placeholder="support@zavelyx.com" />
                    </div>
                    <div>
                        <label class="label">Support Phone</label>
                        <input v-model="generalForm.support_phone" type="text" class="input" placeholder="+1 234 567 8900" />
                    </div>
                    <div>
                        <label class="label">Telegram</label>
                        <input v-model="generalForm.support_telegram" type="text" class="input" placeholder="https://t.me/zavelyx_support" />
                    </div>
                    <div>
                        <label class="label">WhatsApp</label>
                        <input v-model="generalForm.support_whatsapp" type="text" class="input" placeholder="https://wa.me/1234567890" />
                    </div>
                    <div>
                        <label class="label">Discord</label>
                        <input v-model="generalForm.support_discord" type="text" class="input" placeholder="https://discord.gg/nexahub" />
                    </div>
                    <div>
                        <label class="label">Global Contact Link <span class="text-slate-400 font-normal normal-case">(homepage/footer/FAQ)</span></label>
                        <input v-model="generalForm.contact_link" type="text" class="input" placeholder="mailto:support@zavelyx.com" />
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Maintenance Mode</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">When enabled, the site shows a maintenance page to all visitors.</p>
                    </div>
                    <label class="flex-shrink-0 cursor-pointer">
                        <div @click="generalForm.maintenance_mode = !generalForm.maintenance_mode"
                            :class="['relative w-11 h-6 rounded-full transition-colors', generalForm.maintenance_mode ? 'bg-rose-500' : 'bg-slate-200 dark:bg-white/10']">
                            <div :class="['absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform', generalForm.maintenance_mode ? 'translate-x-5' : 'translate-x-0.5']" />
                        </div>
                    </label>
                </div>
                <div v-if="generalForm.maintenance_mode" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 mb-4">
                    <AlertTriangle class="w-4 h-4 text-rose-500 flex-shrink-0" />
                    <p class="text-[12px] text-rose-600 dark:text-rose-400 font-medium">Maintenance mode is active — users cannot access the site.</p>
                </div>
                <div>
                    <label class="label">Maintenance Message</label>
                    <textarea v-model="generalForm.maintenance_message" rows="2" class="input resize-none" placeholder="We are performing scheduled maintenance. We'll be back shortly." />
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="saveGeneral" :disabled="generalForm.processing"
                    class="btn-primary">{{ generalForm.processing ? 'Saving…' : 'Save General Settings' }}</button>
            </div>
        </div>

        <!-- ── BRANDING TAB ──────────────────────────────────────────────── -->
        <div v-if="activeTab === 'branding'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">Logo & Branding</h2>
                <p class="text-[12px] text-slate-400 dark:text-slate-400 mb-5">Each context can have its own logo. If a context logo is not set, it falls back to the Main Logo.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div v-for="slot in LOGO_SLOTS" :key="slot.type"
                        class="rounded-xl border border-slate-200 dark:border-white/8 p-4 flex flex-col gap-3">

                        <!-- Label + description -->
                        <div>
                            <p class="text-[12px] font-bold text-slate-800 dark:text-slate-200">{{ slot.label }}</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-400 mt-0.5 leading-relaxed">{{ slot.desc }}</p>
                        </div>

                        <!-- Preview -->
                        <div class="relative w-full aspect-[3/1] rounded-lg border border-dashed border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/3 flex items-center justify-center overflow-hidden group">
                            <img v-if="brandingPreviews[slot.type]"
                                :src="brandingPreviews[slot.type]"
                                :alt="slot.label"
                                class="max-w-full max-h-full object-contain p-2" />
                            <div v-else class="flex flex-col items-center gap-1 text-slate-300 dark:text-slate-700">
                                <Image class="w-6 h-6" />
                                <span class="text-[10px] font-medium">No image</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Hidden file input -->
                            <input
                                :ref="el => brandingInputs[slot.type] = el"
                                type="file"
                                :accept="slot.accept"
                                class="hidden"
                                @change="e => onBrandingFileChange(e, slot)"
                            />

                            <!-- Choose / Replace -->
                            <button type="button"
                                @click="brandingInputs[slot.type]?.click()"
                                class="flex items-center gap-1.5 text-[11px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">
                                <Upload class="w-3 h-3" />
                                {{ brandingPreviews[slot.type] ? 'Replace' : 'Upload' }}
                            </button>

                            <!-- Upload button (appears after selecting file) -->
                            <button v-if="brandingFiles[slot.type]"
                                type="button"
                                @click="uploadBranding(slot)"
                                :disabled="brandingForms[slot.type].processing"
                                class="flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold bg-sky-500 hover:bg-sky-600 text-white rounded-lg transition-colors disabled:opacity-60">
                                {{ brandingForms[slot.type].processing ? 'Saving…' : 'Save' }}
                            </button>

                            <!-- Delete -->
                            <button v-if="brandingPreviews[slot.type] && !brandingFiles[slot.type]"
                                type="button"
                                @click="deleteBranding(slot.type)"
                                :disabled="deletingType === slot.type"
                                class="ml-auto flex items-center gap-1 text-[11px] font-semibold text-rose-500 hover:text-rose-600 dark:text-rose-400 hover:underline disabled:opacity-50 transition-colors">
                                <Trash2 class="w-3 h-3" />
                                {{ deletingType === slot.type ? 'Removing…' : 'Delete' }}
                            </button>
                        </div>

                        <!-- Format hint -->
                        <p class="text-[10px] text-slate-400">
                            {{ slot.type === 'favicon' ? 'ICO, PNG, WebP, SVG · max 512 KB' : 'PNG, JPG, WebP, SVG · max 2 MB' }}
                        </p>

                        <!-- Upload error -->
                        <p v-if="brandingForms[slot.type].errors[slot.field]"
                            class="text-[11px] text-rose-500">{{ brandingForms[slot.type].errors[slot.field] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl px-4 py-3 flex items-start gap-2">
                <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                <p class="text-[12px] text-amber-700 dark:text-amber-400">
                    <strong>Favicon tip:</strong> After uploading a new favicon, you may need to force-refresh your browser (Ctrl+Shift+R / Cmd+Shift+R) to see the updated browser tab icon.
                </p>
            </div>
        </div>

        <!-- ── SEO TAB ─────────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'seo'" class="space-y-5">
            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Meta Tags</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Meta Title <span class="text-slate-400 font-normal normal-case">(max 200 chars)</span></label>
                        <input v-model="seoForm.meta_title" type="text" class="input" placeholder="Zavelyx — Global SMS & Virtual Number Infrastructure" />
                        <p class="mt-1 text-[11px] text-slate-400">{{ seoForm.meta_title?.length ?? 0 }} / 200</p>
                    </div>
                    <div>
                        <label class="label">Meta Description <span class="text-slate-400 font-normal normal-case">(max 500 chars)</span></label>
                        <textarea v-model="seoForm.meta_description" rows="3" class="input resize-none" placeholder="Receive OTPs instantly on 150+ countries and 700+ operators…" />
                        <p class="mt-1 text-[11px] text-slate-400">{{ seoForm.meta_description?.length ?? 0 }} / 500</p>
                    </div>
                    <div>
                        <label class="label">Keywords <span class="text-slate-400 font-normal normal-case">(comma separated)</span></label>
                        <input v-model="seoForm.meta_keywords" type="text" class="input" placeholder="virtual number, OTP, SMS verification" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">OpenGraph</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">OG Title</label>
                        <input v-model="seoForm.og_title" type="text" class="input" placeholder="Leave blank to use Meta Title" />
                    </div>
                    <div>
                        <label class="label">OG Description</label>
                        <textarea v-model="seoForm.og_description" rows="2" class="input resize-none" placeholder="Leave blank to use Meta Description" />
                    </div>
                    <div>
                        <label class="label">OG Image URL</label>
                        <input v-model="seoForm.og_image_url" type="url" class="input" placeholder="https://cdn.zavelyx.com/og-image.png" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Twitter / X Cards</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Twitter Title</label>
                        <input v-model="seoForm.twitter_title" type="text" class="input" placeholder="Leave blank to use Meta Title" />
                    </div>
                    <div>
                        <label class="label">Twitter Description</label>
                        <textarea v-model="seoForm.twitter_description" rows="2" class="input resize-none" placeholder="Leave blank to use Meta Description" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="saveSeo" :disabled="seoForm.processing" class="btn-primary">{{ seoForm.processing ? 'Saving…' : 'Save SEO Settings' }}</button>
            </div>
        </div>

        <!-- ── HOMEPAGE TAB ────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'homepage'" class="space-y-5">

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Announcement Bar</h2>
                        <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">Dismissable banner displayed at the very top of the homepage.</p>
                    </div>
                    <div @click="homepageForm.announcement_enabled = !homepageForm.announcement_enabled"
                        :class="['relative w-9 h-5 rounded-full cursor-pointer flex-shrink-0 transition-colors', homepageForm.announcement_enabled ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                        <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', homepageForm.announcement_enabled ? 'translate-x-4' : 'translate-x-0.5']" />
                    </div>
                </div>

                <!-- Live preview -->
                <div v-if="homepageForm.announcement_enabled && homepageForm.announcement_text"
                    :class="['rounded-xl px-4 py-2.5 mb-4 flex items-center gap-3 text-[12px] font-semibold text-white',
                        homepageForm.announcement_color === 'violet'   ? 'bg-gradient-to-r from-violet-600 to-purple-600' :
                        homepageForm.announcement_color === 'emerald'  ? 'bg-gradient-to-r from-emerald-600 to-teal-600' :
                        homepageForm.announcement_color === 'amber'    ? 'bg-gradient-to-r from-amber-500 to-orange-500' :
                        homepageForm.announcement_color === 'rose'     ? 'bg-gradient-to-r from-rose-600 to-pink-600' :
                        homepageForm.announcement_color === 'gradient' ? 'bg-gradient-to-r from-sky-500 via-violet-500 to-pink-500' :
                        'bg-gradient-to-r from-sky-600 to-blue-600']">
                    <span v-if="homepageForm.announcement_icon" class="text-[16px] flex-shrink-0">{{ homepageForm.announcement_icon }}</span>
                    <span class="flex-1">{{ homepageForm.announcement_text }}</span>
                    <span v-if="homepageForm.announcement_cta" class="px-2.5 py-1 bg-white/20 rounded-lg text-[11px] font-bold">{{ homepageForm.announcement_cta }}</span>
                    <span class="opacity-60 text-[10px]">× dismiss</span>
                </div>
                <p v-if="homepageForm.announcement_enabled && !homepageForm.announcement_text" class="text-[11px] text-amber-600 dark:text-amber-400 mb-3">Enter announcement text below to see a preview.</p>

                <div v-if="homepageForm.announcement_enabled" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_80px] gap-3">
                        <div>
                            <label class="label">Announcement Text</label>
                            <input v-model="homepageForm.announcement_text" type="text" class="input" placeholder="🎉 New: 50+ new services added this week!" />
                        </div>
                        <div>
                            <label class="label">Icon <span class="normal-case font-normal">(emoji)</span></label>
                            <input v-model="homepageForm.announcement_icon" type="text" class="input text-center text-[18px]" placeholder="🎉" maxlength="4" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Link URL <span class="normal-case font-normal">(optional)</span></label>
                            <input v-model="homepageForm.announcement_link" type="url" class="input" placeholder="https://zavelyx.com/services" />
                        </div>
                        <div>
                            <label class="label">CTA Button Text <span class="normal-case font-normal">(optional)</span></label>
                            <input v-model="homepageForm.announcement_cta" type="text" class="input" placeholder="Learn more →" maxlength="40" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Bar Color</label>
                            <div class="flex gap-2 flex-wrap mt-1">
                                <button v-for="c in [
                                    { key: 'sky',      label: 'Sky',      class: 'bg-gradient-to-r from-sky-600 to-blue-600' },
                                    { key: 'violet',   label: 'Violet',   class: 'bg-gradient-to-r from-violet-600 to-purple-600' },
                                    { key: 'emerald',  label: 'Emerald',  class: 'bg-gradient-to-r from-emerald-600 to-teal-600' },
                                    { key: 'amber',    label: 'Amber',    class: 'bg-gradient-to-r from-amber-500 to-orange-500' },
                                    { key: 'rose',     label: 'Rose',     class: 'bg-gradient-to-r from-rose-600 to-pink-600' },
                                    { key: 'gradient', label: 'Rainbow',  class: 'bg-gradient-to-r from-sky-500 via-violet-500 to-pink-500' },
                                ]" :key="c.key" type="button"
                                    @click="homepageForm.announcement_color = c.key"
                                    :class="['w-7 h-7 rounded-lg border-2 transition-all', c.class,
                                        homepageForm.announcement_color === c.key ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-60 hover:opacity-100']"
                                    :title="c.label" />
                            </div>
                        </div>
                        <div class="flex items-center gap-3 pt-5">
                            <div @click="homepageForm.announcement_pinned = !homepageForm.announcement_pinned"
                                :class="['relative w-9 h-5 rounded-full cursor-pointer flex-shrink-0 transition-colors', homepageForm.announcement_pinned ? 'bg-sky-500' : 'bg-slate-200 dark:bg-white/10']">
                                <div :class="['absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform', homepageForm.announcement_pinned ? 'translate-x-4' : 'translate-x-0.5']" />
                            </div>
                            <div>
                                <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-300">Pin bar (non-dismissable)</p>
                                <p class="text-[11px] text-slate-400">Users cannot close it when pinned.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Hero Section</h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Hero Title <span class="text-slate-400 font-normal normal-case">(leave blank to use default)</span></label>
                        <input v-model="homepageForm.hero_title" type="text" class="input" placeholder="Global SMS & Virtual Number Infrastructure" />
                    </div>
                    <div>
                        <label class="label">Hero Subtitle</label>
                        <textarea v-model="homepageForm.hero_subtitle" rows="2" class="input resize-none" placeholder="Receive OTPs in seconds across 150+ countries…" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Primary CTA Text</label>
                            <input v-model="homepageForm.cta_primary_text" type="text" class="input" placeholder="Start Receiving SMS Now" />
                        </div>
                        <div>
                            <label class="label">Secondary CTA Text</label>
                            <input v-model="homepageForm.cta_secondary_text" type="text" class="input" placeholder="View API Docs" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Stats Bar</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Activations (M)</label>
                        <input v-model="homepageForm.stats_activations" type="number" step="0.1" class="input" placeholder="2.4" />
                    </div>
                    <div>
                        <label class="label">Countries</label>
                        <input v-model="homepageForm.stats_countries" type="number" class="input" placeholder="150" />
                    </div>
                    <div>
                        <label class="label">Operators</label>
                        <input v-model="homepageForm.stats_operators" type="number" class="input" placeholder="700" />
                    </div>
                    <div>
                        <label class="label">Success Rate (%)</label>
                        <input v-model="homepageForm.stats_success_rate" type="number" step="0.1" max="100" class="input" placeholder="99.7" />
                    </div>
                    <div>
                        <label class="label">Platform Uptime (%)</label>
                        <input v-model="homepageForm.stats_uptime" type="number" step="0.1" max="100" class="input" placeholder="99.9" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-3">Footer</h2>
                <div>
                    <label class="label">Footer Copyright Text</label>
                    <input v-model="homepageForm.footer_text" type="text" class="input" placeholder="© 2026 Zavelyx. All rights reserved." />
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="saveHomepage" :disabled="homepageForm.processing" class="btn-primary">{{ homepageForm.processing ? 'Saving…' : 'Save Homepage Settings' }}</button>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
.label {
    @apply block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5;
}
.input {
    @apply w-full px-3 py-2.5 text-[13px] bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400 dark:focus:border-sky-500 transition-all;
}
.field-error {
    @apply mt-1 text-[11px] text-rose-500;
}
.btn-primary {
    @apply px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-500 text-white text-[13px] font-bold shadow-sky-500/25 shadow-lg hover:from-sky-600 hover:to-blue-600 hover:-translate-y-px transition-all active:scale-95 disabled:opacity-60 disabled:translate-y-0 disabled:cursor-not-allowed;
}
</style>

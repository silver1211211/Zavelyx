<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, ChevronRight, ExternalLink, Mail, MessageCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({ contact_link: String });

const form = useForm({ contact_link: props.contact_link ?? 'mailto:support@nexahub.io' });
const flash = computed(() => usePage().props.flash ?? {});

const linkType = computed(() => {
    const v = form.contact_link ?? '';
    if (v.startsWith('mailto:'))       return 'email';
    if (v.includes('t.me') || v.includes('telegram')) return 'telegram';
    if (v.includes('wa.me') || v.includes('whatsapp')) return 'whatsapp';
    if (v.includes('discord'))         return 'discord';
    if (v.startsWith('https://') || v.startsWith('http://')) return 'url';
    return null;
});

const typeLabel = computed(() => ({
    email:    { icon: '✉️', text: 'Email link detected', color: 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/10 border-sky-200 dark:border-sky-500/20' },
    telegram: { icon: '✈️', text: 'Telegram link detected', color: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20' },
    whatsapp: { icon: '💬', text: 'WhatsApp link detected', color: 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/20' },
    discord:  { icon: '🎮', text: 'Discord link detected', color: 'text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/10 border-violet-200 dark:border-violet-500/20' },
    url:      { icon: '🔗', text: 'Custom URL detected', color: 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-white/10' },
}[linkType.value] ?? null));

const presets = [
    { label: 'Email',     placeholder: 'mailto:support@nexahub.io',   icon: '✉️' },
    { label: 'Telegram',  placeholder: 'https://t.me/nexahub_support', icon: '✈️' },
    { label: 'WhatsApp',  placeholder: 'https://wa.me/1234567890',     icon: '💬' },
    { label: 'Discord',   placeholder: 'https://discord.gg/nexahub',   icon: '🎮' },
];

function applyPreset(placeholder) {
    form.contact_link = placeholder;
}

function save() {
    form.post(route('admin.settings.contact.save'));
}
</script>

<template>
    <Head title="Contact Settings — Admin" />
    <AdminLayout>
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-[12px] text-slate-400 dark:text-slate-400 mb-6">
            <Link :href="route('admin.settings.index')" class="hover:text-sky-500 dark:hover:text-sky-400 transition-colors">Settings</Link>
            <ChevronRight class="w-3 h-3" />
            <span class="text-slate-600 dark:text-slate-300 font-medium">Contact Settings</span>
        </div>

        <div class="mb-8">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-teal-500 dark:text-teal-400 mb-0.5">Settings</p>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">Contact Settings</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                The global support link used on the homepage, FAQ, and footer. Every "Contact Support" button platform-wide points here.
            </p>
        </div>

        <!-- Success flash -->
        <div v-if="flash.success" class="flex items-center gap-3 mb-6 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[13px] font-medium">
            <CheckCircle class="w-4 h-4 flex-shrink-0" />
            {{ flash.success }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main form -->
            <div class="lg:col-span-2 bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-6">
                <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-1">Support Link</h2>
                <p class="text-[12px] text-slate-500 dark:text-slate-400 mb-5">Paste any valid link: an email address, Telegram channel, WhatsApp number, Discord server, or any URL.</p>

                <!-- Quick presets -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <button
                        v-for="preset in presets"
                        :key="preset.label"
                        type="button"
                        @click="applyPreset(preset.placeholder)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:border-sky-400 dark:hover:border-sky-500/40 hover:text-sky-600 dark:hover:text-sky-400 transition-all duration-150"
                    >
                        <span>{{ preset.icon }}</span>
                        {{ preset.label }}
                    </button>
                </div>

                <!-- Input -->
                <div class="mb-3">
                    <label class="block text-[12px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Contact URL</label>
                    <input
                        v-model="form.contact_link"
                        type="text"
                        placeholder="mailto:support@nexahub.io"
                        class="w-full px-4 py-3 rounded-xl border text-[13px] font-mono text-slate-800 dark:text-slate-100 bg-white dark:bg-[#060d1a] placeholder-slate-400 dark:placeholder-slate-600 transition-all duration-150 outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-400 dark:focus:border-sky-500"
                        :class="form.errors.contact_link
                            ? 'border-rose-400 dark:border-rose-500/50'
                            : 'border-slate-200 dark:border-white/10'"
                    />
                    <p v-if="form.errors.contact_link" class="mt-1.5 text-[11px] text-rose-500 flex items-center gap-1">
                        <AlertCircle class="w-3 h-3" /> {{ form.errors.contact_link }}
                    </p>
                </div>

                <!-- Type detection badge -->
                <div v-if="typeLabel" :class="['inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-semibold border mb-5', typeLabel.color]">
                    <span>{{ typeLabel.icon }}</span>
                    {{ typeLabel.text }}
                </div>
                <div v-else-if="form.contact_link" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-semibold border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 mb-5">
                    <AlertCircle class="w-3 h-3" />
                    Must start with mailto:, https://, or http://
                </div>

                <!-- Save -->
                <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-500 text-white text-[13px] font-bold shadow-lg shadow-sky-500/25 hover:from-sky-600 hover:to-blue-600 hover:-translate-y-px transition-all duration-150 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0"
                    >
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                    <a
                        v-if="form.contact_link && typeLabel"
                        :href="form.contact_link"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-[12px] text-slate-500 dark:text-slate-400 hover:text-sky-500 dark:hover:text-sky-400 transition-colors"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        Test link
                    </a>
                </div>
            </div>

            <!-- Sidebar — how it works -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <div class="w-9 h-9 rounded-xl bg-teal-500/10 dark:bg-teal-500/15 flex items-center justify-center mb-3">
                        <MessageCircle class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                    </div>
                    <h3 class="text-[13px] font-bold text-slate-900 dark:text-white mb-2">Where it appears</h3>
                    <ul class="space-y-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                        <li class="flex items-start gap-2"><span class="text-teal-500 mt-0.5">→</span> Homepage FAQ "Contact Support"</li>
                        <li class="flex items-start gap-2"><span class="text-teal-500 mt-0.5">→</span> Homepage footer links</li>
                        <li class="flex items-start gap-2"><span class="text-teal-500 mt-0.5">→</span> Terms of Service contact</li>
                        <li class="flex items-start gap-2"><span class="text-teal-500 mt-0.5">→</span> Privacy Policy contact</li>
                    </ul>
                </div>

                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <h3 class="text-[13px] font-bold text-slate-900 dark:text-white mb-3">Format examples</h3>
                    <div class="space-y-2">
                        <div v-for="preset in presets" :key="preset.label" class="text-[11px]">
                            <p class="font-semibold text-slate-500 dark:text-slate-400">{{ preset.icon }} {{ preset.label }}</p>
                            <p class="font-mono text-slate-400 dark:text-slate-400 break-all">{{ preset.placeholder }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Head } from '@inertiajs/vue3';
import { Check, Copy, Gift, Link2, Users, Zap } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    referralCode:  { type: String, default: null },
    referralBonus: { type: Number, default: 0 },
    referralCount: { type: Number, default: 0 },
    referrals:     { type: Array,  default: () => [] },
});

const { symbol, formatAmount: convertAmount } = useCurrency();

const referralLink = computed(() => {
    if (!props.referralCode) return '';
    return window.location.origin + '/register?ref=' + props.referralCode;
});

const copiedLink = ref(false);
const copiedCode = ref(false);

async function copyLink() {
    await navigator.clipboard.writeText(referralLink.value);
    copiedLink.value = true;
    setTimeout(() => { copiedLink.value = false; }, 2000);
}

async function copyCode() {
    await navigator.clipboard.writeText(props.referralCode ?? '');
    copiedCode.value = true;
    setTimeout(() => { copiedCode.value = false; }, 2000);
}

function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Referrals" />
    <AuthenticatedLayout>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Referrals</h1>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5">Invite friends and earn bonus credits.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- Left: stats + share -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Stats row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 text-center">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 dark:bg-sky-500/15 flex items-center justify-center mx-auto mb-3">
                            <Users class="w-5 h-5 text-sky-500" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ referralCount }}</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">Friends invited</p>
                    </div>
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5 text-center">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15 flex items-center justify-center mx-auto mb-3">
                            <Gift class="w-5 h-5 text-emerald-500" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ symbol }}{{ convertAmount(referralBonus) }}</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">Total bonus earned</p>
                    </div>
                    <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-sky-500/15 p-5 text-center">
                        <div class="w-10 h-10 rounded-xl bg-violet-500/10 dark:bg-violet-500/15 flex items-center justify-center mx-auto mb-3">
                            <Zap class="w-5 h-5 text-violet-500" />
                        </div>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">5%</p>
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">Per referral bonus</p>
                    </div>
                </div>

                <!-- Referral link card -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <h2 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">Your Referral Link</h2>

                    <div v-if="!referralCode" class="text-center py-6">
                        <p class="text-[13px] text-slate-400 dark:text-slate-400">Your referral code is being generated.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <!-- Referral link -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1.5">Referral URL</label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 flex items-center h-10 px-3 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl overflow-hidden">
                                    <Link2 class="w-3.5 h-3.5 text-slate-400 mr-2 flex-shrink-0" />
                                    <span class="text-[12px] text-slate-600 dark:text-slate-400 truncate font-mono">{{ referralLink }}</span>
                                </div>
                                <button @click="copyLink" class="flex items-center gap-1.5 px-3.5 py-2 bg-sky-500 hover:bg-sky-600 text-white text-[12px] font-semibold rounded-xl shadow-sm shadow-sky-500/30 transition-all flex-shrink-0">
                                    <Check v-if="copiedLink" class="w-3.5 h-3.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    {{ copiedLink ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <!-- Referral code -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-600 mb-1.5">Referral Code</label>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center h-10 px-4 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/8 rounded-xl">
                                    <span class="text-[15px] font-black font-mono tracking-widest text-slate-800 dark:text-white">{{ referralCode }}</span>
                                </div>
                                <button @click="copyCode" class="flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-white/8 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 text-[12px] font-medium rounded-xl transition-all flex-shrink-0">
                                    <Check v-if="copiedCode" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    {{ copiedCode ? 'Copied' : 'Copy code' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referred users -->
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-sky-500/8">
                        <h2 class="text-[14px] font-bold text-slate-900 dark:text-white">Invited Friends</h2>
                    </div>

                    <div v-if="referrals.length === 0" class="py-10 flex flex-col items-center gap-2 text-center">
                        <Users class="w-8 h-8 text-slate-300 dark:text-slate-700" />
                        <p class="text-[13px] text-slate-400 dark:text-slate-600">No referrals yet. Share your link to get started!</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-sky-500/8">
                        <div v-for="user in referrals" :key="user.id" class="flex items-center gap-3 px-5 py-3.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0">
                                {{ user.name?.[0]?.toUpperCase() }}
                            </div>
                            <div class="flex-1">
                                <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-200">{{ user.name }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-600">Joined {{ formatDate(user.created_at) }}</p>
                            </div>
                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">+Bonus</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: how it works -->
            <div>
                <div class="bg-white dark:bg-[#0d1e35] rounded-2xl border border-slate-200 dark:border-sky-500/12 p-5">
                    <h3 class="text-[14px] font-bold text-slate-900 dark:text-white mb-4">How it works</h3>
                    <div class="space-y-4">
                        <div v-for="(step, i) in [
                            { title: 'Share your link', desc: 'Share your unique referral URL or code with friends.' },
                            { title: 'They sign up', desc: 'They register using your link and become a Zavelyx user.' },
                            { title: 'You both earn', desc: 'You get a 5% bonus on their first deposit added to your balance.' },
                        ]" :key="i" class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-sky-500/15 flex items-center justify-center text-sky-600 dark:text-sky-400 text-[11px] font-black flex-shrink-0 mt-0.5">{{ i + 1 }}</div>
                            <div>
                                <p class="text-[13px] font-semibold text-slate-800 dark:text-white">{{ step.title }}</p>
                                <p class="text-[12px] text-slate-400 dark:text-slate-400 mt-0.5">{{ step.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3.5 rounded-xl bg-gradient-to-br from-sky-500/8 to-blue-600/8 border border-sky-500/15">
                        <p class="text-[12px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            Bonuses are credited automatically once your referred user makes their first deposit. There is no limit to how many friends you can refer.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

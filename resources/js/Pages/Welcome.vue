<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    BarChart3,
    Bell,
    Check,
    ChevronDown,
    Code2,
    Globe2,
    Headphones,
    Inbox,
    Menu,
    MessageCircle,
    Moon,
    Phone,
    RefreshCcw,
    ShieldCheck,
    Smartphone,
    Sun,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { getStoredTheme, setThemeInstant } from '@/utils/theme';

const props = defineProps({ canLogin: Boolean, canRegister: Boolean, contactLink: String });

const supportLink  = computed(() => props.contactLink || 'mailto:support@zavelyx.com');
const siteSettings = computed(() => usePage().props.site_settings ?? {});

const menuOpen   = ref(false);
const isDark     = ref(false);
const isScrolled = ref(false);

// Announcement dismiss — keyed by text so new announcements always show
const announcementDismissed = ref(false);

function dismissAnnouncement() {
    announcementDismissed.value = true;
    try {
        const key = 'nxa_ann_' + btoa(siteSettings.value.announcement_text ?? '').slice(0, 16);
        localStorage.setItem(key, '1');
    } catch {}
}

const announcementVisible = computed(() => {
    if (!siteSettings.value.announcement_enabled) return false;
    if (!siteSettings.value.announcement_text)    return false;
    if (siteSettings.value.announcement_pinned)   return true; // pinned: always show, cannot dismiss
    return !announcementDismissed.value;
});

const announcementColorClass = computed(() => {
    const c = siteSettings.value.announcement_color ?? 'sky';
    const map = {
        sky:      'from-sky-600 to-blue-600',
        violet:   'from-violet-600 to-purple-600',
        emerald:  'from-emerald-600 to-teal-600',
        amber:    'from-amber-500 to-orange-500',
        rose:     'from-rose-600 to-pink-600',
        gradient: 'from-sky-500 via-violet-500 to-pink-500',
    };
    return map[c] ?? map.sky;
});
const liveIndex  = ref(0);
const inboxIndex = ref(0);
const faqOpen    = ref(null);

// Animated counters
const statsConfig = computed(() => [
    { label: 'OTP Activations', target: siteSettings.value.stats_activations  ?? 2.4,  format: v => v.toFixed(1) + 'M+', color: 'text-sky-500 dark:text-sky-400'          },
    { label: 'Countries',       target: siteSettings.value.stats_countries     ?? 150,  format: v => Math.round(v) + '+',  color: 'text-emerald-500 dark:text-emerald-400' },
    { label: 'Operators',       target: siteSettings.value.stats_operators     ?? 700,  format: v => Math.round(v) + '+',  color: 'text-violet-500 dark:text-violet-400'   },
    { label: 'Success Rate',    target: siteSettings.value.stats_success_rate  ?? 99.7, format: v => v.toFixed(1) + '%',   color: 'text-amber-500 dark:text-amber-400'     },
    { label: 'Platform Uptime', target: siteSettings.value.stats_uptime        ?? 99.9, format: v => v.toFixed(1) + '%',   color: 'text-rose-500 dark:text-rose-400'       },
]);
const statValues = ref([0, 0, 0, 0, 0]);
let statsObserver = null;

function animateStats() {
    const duration = 1800;
    const start = performance.now();
    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        statsConfig.value.forEach((s, i) => { statValues.value[i] = s.target * eased; });
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

let liveTimer, inboxTimer;

const navItems = [
    { label: 'Services',    href: '#services'    },
    { label: 'Receive SMS', href: '#receive-sms' },
    { label: 'OTP',         href: '#otp'         },
    { label: 'API',         href: '#api'          },
    { label: 'FAQ',         href: '#faq'          },
];

const capabilities = [
    { icon: Phone,      title: 'Virtual Numbers',  desc: 'Temporary real phone numbers for instant OTP verification across 300+ platforms worldwide.',    color: 'sky',    stat: '300+ platforms',  gradient: 'from-sky-500 to-blue-600'      },
    { icon: Inbox,      title: 'Receive SMS',       desc: 'Online SMS inbox. Get a number and receive real messages from any sender, live.',                color: 'emerald',stat: '24/7 delivery',   gradient: 'from-emerald-500 to-teal-600'  },
    { icon: Smartphone, title: 'OTP Marketplace',   desc: 'Automated OTP activations with real-time delivery, live status, and auto-refund on failure.',   color: 'violet', stat: '99.7% success',   gradient: 'from-violet-500 to-purple-600' },
    { icon: Code2,      title: 'Developer API',     desc: 'RESTful API for SMS automation, number management, and reseller integrations at scale.',        color: 'amber',  stat: 'Fast & scalable',  gradient: 'from-amber-400 to-orange-500'  },
];

const services = [
    {
        name: 'WhatsApp', brand: '#25D366',
        glow: 'hover:shadow-[0_8px_32px_rgba(37,211,102,0.3)] hover:border-green-400/40',
        price: '$0.05', popular: true, rate: '98%',
        icon: `<svg viewBox="0 0 24 24" fill="#25D366" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`,
    },
    {
        name: 'Telegram', brand: '#2AABEE',
        glow: 'hover:shadow-[0_8px_32px_rgba(42,171,238,0.3)] hover:border-sky-400/40',
        price: '$0.03', popular: false, rate: '99%',
        icon: `<svg viewBox="0 0 24 24" fill="#2AABEE" width="20" height="20"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>`,
    },
    {
        name: 'Google', brand: '#4285F4',
        glow: 'hover:shadow-[0_8px_32px_rgba(66,133,244,0.3)] hover:border-blue-400/40',
        price: '$0.07', popular: true, rate: '97%',
        icon: `<svg viewBox="0 0 24 24" fill="#4285F4" width="20" height="20"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>`,
    },
    {
        name: 'TikTok', brand: '#FE2C55',
        glow: 'hover:shadow-[0_8px_32px_rgba(254,44,85,0.3)] hover:border-rose-400/40',
        price: '$0.04', popular: false, rate: '96%',
        icon: `<svg viewBox="0 0 24 24" fill="#FE2C55" width="20" height="20"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>`,
    },
    {
        name: 'Facebook', brand: '#1877F2',
        glow: 'hover:shadow-[0_8px_32px_rgba(24,119,242,0.3)] hover:border-blue-500/40',
        price: '$0.05', popular: false, rate: '97%',
        icon: `<svg viewBox="0 0 24 24" fill="#1877F2" width="20" height="20"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`,
    },
    {
        name: 'Discord', brand: '#5865F2',
        glow: 'hover:shadow-[0_8px_32px_rgba(88,101,242,0.3)] hover:border-indigo-400/40',
        price: '$0.04', popular: false, rate: '98%',
        icon: `<svg viewBox="0 0 24 24" fill="#5865F2" width="20" height="20"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057.1 18.1.127 18.14.17 18.16a19.9 19.9 0 0 0 5.993 3.03.077.077 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>`,
    },
    {
        name: 'Instagram', brand: '#E1306C',
        glow: 'hover:shadow-[0_8px_32px_rgba(225,48,108,0.3)] hover:border-pink-400/40',
        price: '$0.06', popular: false, rate: '96%',
        icon: `<svg viewBox="0 0 24 24" fill="#E1306C" width="20" height="20"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>`,
    },
    {
        name: 'Uber', brand: '#000000',
        glow: 'hover:shadow-[0_8px_32px_rgba(100,116,139,0.3)] hover:border-slate-400/40',
        price: '$0.08', popular: false, rate: '95%',
        icon: `<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20" class="text-slate-700 dark:text-slate-200"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm.105 19.04c-3.956 0-7.156-3.2-7.156-7.156V7.128h2.34v4.757c0 2.652 2.163 4.814 4.816 4.814s4.815-2.162 4.815-4.814V7.128h2.34v4.756c0 3.956-3.2 7.157-7.155 7.157z"/></svg>`,
    },
    {
        name: 'Binance', brand: '#F3BA2F',
        glow: 'hover:shadow-[0_8px_32px_rgba(243,186,47,0.3)] hover:border-amber-400/40',
        price: '$0.09', popular: false, rate: '97%',
        icon: `<svg viewBox="0 0 24 24" fill="#F3BA2F" width="20" height="20"><path d="M16.624 13.9202l2.7175 2.7154-7.353 7.353-7.353-7.352 2.7175-2.7164 4.6355 4.6595 4.6355-4.6595zm4.6289-4.6236l2.7175 2.7154-2.7175 2.7154-2.7177-2.7154 2.7177-2.7154zm-9.253 0l2.7175 2.7154-2.7175 2.7154-2.7177-2.7154 2.7177-2.7154zm-9.256 0l2.7175 2.7154-2.7175 2.7154-2.7177-2.7154 2.7177-2.7154zM11.9999 0l7.353 7.3518-2.7175 2.7164-4.6355-4.6595-4.6355 4.6595-2.7175-2.7164L11.9999 0z"/></svg>`,
    },
    {
        name: 'Gmail', brand: '#EA4335',
        glow: 'hover:shadow-[0_8px_32px_rgba(234,67,53,0.3)] hover:border-red-400/40',
        price: '$0.06', popular: false, rate: '97%',
        icon: `<svg viewBox="0 0 24 24" fill="#EA4335" width="20" height="20"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.908 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/></svg>`,
    },
];

const inboxMessages = [
    { service: 'WhatsApp', flag: '🇺🇸', number: '+1 929-555-0142', text: 'Your WhatsApp code: 847291. Do not share this code.', time: '2s ago',  dot: 'bg-emerald-400', code: '847291' },
    { service: 'Google',   flag: '🇬🇧', number: '+44 7700 900 123', text: 'G-348291 is your Google verification code.',           time: '18s ago', dot: 'bg-blue-400',   code: '348291' },
    { service: 'Telegram', flag: '🇩🇪', number: '+49 176 123 4567', text: 'Your Telegram login code: 48291. Do not share it.',     time: '45s ago', dot: 'bg-sky-400',    code: '48291'  },
    { service: 'Binance',  flag: '🇨🇦', number: '+1 437-555-0198',  text: 'Binance verification code: 294810. Valid 15 min.',      time: '1m ago',  dot: 'bg-amber-400',  code: '294810' },
    { service: 'Discord',  flag: '🇫🇷', number: '+33 6 12 34 56 78',text: 'Your Discord code is: 193847. Never share it.',         time: '2m ago',  dot: 'bg-indigo-400', code: '193847' },
];

const countryCoverage = [
    { flag: '🇺🇸', name: 'United States' }, { flag: '🇬🇧', name: 'United Kingdom' },
    { flag: '🇩🇪', name: 'Germany'        }, { flag: '🇫🇷', name: 'France'         },
    { flag: '🇮🇳', name: 'India'          }, { flag: '🇧🇷', name: 'Brazil'         },
    { flag: '🇨🇦', name: 'Canada'         }, { flag: '🇦🇺', name: 'Australia'      },
    { flag: '🇳🇬', name: 'Nigeria'        }, { flag: '🇲🇽', name: 'Mexico'         },
    { flag: '🇷🇺', name: 'Russia'         }, { flag: '🇵🇱', name: 'Poland'         },
    { flag: '🇰🇷', name: 'South Korea'    }, { flag: '🇯🇵', name: 'Japan'          },
    { flag: '🇪🇸', name: 'Spain'          }, { flag: '🇮🇩', name: 'Indonesia'      },
    { flag: '🇵🇭', name: 'Philippines'    }, { flag: '🇿🇦', name: 'South Africa'   },
    { flag: '🇺🇦', name: 'Ukraine'        }, { flag: '🇸🇪', name: 'Sweden'         },
    { flag: '🇸🇬', name: 'Singapore'      }, { flag: '🇦🇪', name: 'UAE'            },
    { flag: '🇹🇷', name: 'Turkey'         }, { flag: '🇳🇱', name: 'Netherlands'    },
];

const liveActivities = [
    { flag: '🇺🇸', country: 'United States',  service: 'WhatsApp',  number: '+1 (929) xxx-xx47',   type: 'OTP Received',      time: '2s ago',  dot: 'bg-emerald-400' },
    { flag: '🇬🇧', country: 'United Kingdom',  service: 'Google',    number: '+44 7700 xxx-xx3',    type: 'OTP Received',      time: '5s ago',  dot: 'bg-blue-400'    },
    { flag: '🇩🇪', country: 'Germany',         service: 'Telegram',  number: '+49 176 xxx-xx61',    type: 'SMS Received',      time: '8s ago',  dot: 'bg-sky-400'     },
    { flag: '🇧🇷', country: 'Brazil',          service: 'Instagram', number: '+55 11 xxx-xx29',     type: 'OTP Received',      time: '12s ago', dot: 'bg-pink-400'    },
    { flag: '🇮🇳', country: 'India',           service: 'Facebook',  number: '+91 98 xxx-xx14',     type: 'OTP Received',      time: '16s ago', dot: 'bg-blue-500'    },
    { flag: '🇫🇷', country: 'France',          service: 'Discord',   number: '+33 6 xx-xx-xx88',    type: 'SMS Received',      time: '21s ago', dot: 'bg-indigo-400'  },
    { flag: '🇨🇦', country: 'Canada',          service: 'Binance',   number: '+1 (437) xxx-xx55',   type: 'OTP Received',      time: '25s ago', dot: 'bg-amber-400'   },
    { flag: '🇳🇬', country: 'Nigeria',         service: 'TikTok',    number: '+234 80 xxx-xx32',    type: 'Number Allocated',  time: '30s ago', dot: 'bg-rose-400'    },
];

const features = [
    { icon: Zap,         title: 'Instant Activation', desc: 'Numbers assigned in milliseconds. OTPs delivered in under 10 seconds average.',              color: 'sky'    },
    { icon: RefreshCcw,  title: 'Auto Refund',         desc: 'No OTP received? Full refund automatically — zero support tickets needed.',                   color: 'emerald'},
    { icon: Globe2,      title: '150+ Countries',      desc: 'Real SIM-backed numbers across North America, Europe, Asia, Africa and more.',                color: 'violet' },
    { icon: ShieldCheck, title: 'Secure Infrastructure',desc: 'TLS encryption, anti-fraud monitoring, and private number allocation per account.',          color: 'amber'  },
    { icon: Inbox,       title: 'Live SMS Inbox',       desc: 'Full SMS inbox for every number. See all incoming messages as they arrive.',                  color: 'rose'   },
    { icon: Activity,    title: '99.9% Uptime',         desc: 'Redundant infrastructure with 24/7 monitoring and automatic failover.',                      color: 'teal'   },
    { icon: Code2,       title: 'Scalable API',         desc: 'RESTful API supporting bulk activations, webhook events, and full automation.',               color: 'indigo' },
    { icon: Bell,        title: 'Real-time Alerts',     desc: 'Instant notifications the moment your OTP arrives — dashboard and webhook.',                  color: 'pink'   },
    { icon: BarChart3,   title: 'Analytics',            desc: 'Track all activations, delivery rates, and spend history from one dashboard.',                color: 'amber'  },
    { icon: Headphones,  title: '24/7 Support',         desc: 'Dedicated support team available around the clock via chat and ticket system.',               color: 'sky'    },
];

const faqs = [
    { q: 'What is a virtual number for OTP verification?',  a: "A virtual number is a real phone number that can receive SMS. You use it instead of your personal number when signing up for platforms. The OTP appears on your Zavelyx dashboard within seconds of being sent." },
    { q: 'What is the Receive SMS service?',                a: "Receive SMS gives you a virtual number with a live inbox. All incoming SMS messages to that number are displayed in real time on your dashboard — ideal for testing, monitoring verifications, or receiving SMS without exposing your personal number." },
    { q: 'How long do I have access to the number?',        a: "OTP activation sessions are typically 20 minutes. If no OTP arrives, your balance is automatically refunded. For Receive SMS, longer-term rental windows are available for extended use cases." },
    { q: "What if I don't receive the OTP?",                a: "If no OTP arrives within the window, the session is marked failed and your balance is refunded automatically — no support ticket required." },
    { q: 'Are the numbers real mobile numbers?',            a: "Yes. All Zavelyx numbers are real SIM-backed mobile numbers. They are not VOIP and work with strict SMS-only systems including WhatsApp, Google, major banks, and crypto exchanges." },
    { q: 'How does the Developer API work?',                a: "Authenticate with your API key, POST a request specifying service and country, receive a number and session ID, then poll or receive a webhook when the OTP arrives. Full docs are in your dashboard." },
    { q: 'Which payment methods are accepted?',             a: "We accept USDT, BTC, ETH, USDC, and other methods shown at checkout. Minimum deposit $1. Funds are credited after confirmation." },
    { q: 'Can I use a number multiple times?',              a: "OTP activations are single-use. For the Receive SMS service, you can receive multiple messages during the active rental window." },
    { q: 'Is my data safe?',                                a: "OTP content is never stored permanently — purged within 24 hours of session end. Activations are private, encrypted, and never shared. We follow strict TLS and data protection standards." },
    { q: 'What services and countries are available?',      a: "150+ countries, 700+ operators, 300+ services including WhatsApp, Google, Telegram, TikTok, Facebook, Discord, Instagram, Uber, Binance, Gmail, and hundreds more. Full availability shown in your dashboard before purchase." },
];

const themeIcon = computed(() => (isDark.value ? Sun : Moon));
const visibleActivities = computed(() => [0, 1, 2, 3].map(i => liveActivities[(liveIndex.value + i) % liveActivities.length]));
const visibleInbox      = computed(() => [0, 1, 2].map(i => inboxMessages[(inboxIndex.value + i) % inboxMessages.length]));

function setTheme(t) {
    const theme = setThemeInstant(t);
    isDark.value = theme === 'dark';
}
function toggleTheme() { setTheme(isDark.value ? 'light' : 'dark'); }
function toggleFaq(i)  { faqOpen.value = faqOpen.value === i ? null : i; }

onMounted(() => {
    setTheme(getStoredTheme('dark'));

    // Restore announcement dismiss state
    try {
        if (!siteSettings.value.announcement_pinned && siteSettings.value.announcement_text) {
            const key = 'nxa_ann_' + btoa(siteSettings.value.announcement_text).slice(0, 16);
            announcementDismissed.value = localStorage.getItem(key) === '1';
        }
    } catch {}

    window.addEventListener('scroll', () => { isScrolled.value = window.scrollY > 20; }, { passive: true });

    liveTimer  = setInterval(() => { liveIndex.value  = (liveIndex.value  + 1) % liveActivities.length; }, 2800);
    inboxTimer = setInterval(() => { inboxIndex.value = (inboxIndex.value + 1) % inboxMessages.length;  }, 3400);

    // Animate stat counters on first intersection
    const statsEl = document.getElementById('stats-bar');
    if (statsEl) {
        statsObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                animateStats();
                statsObserver.disconnect();
            }
        }, { threshold: 0.3 });
        statsObserver.observe(statsEl);
    }
});

onBeforeUnmount(() => {
    clearInterval(liveTimer);
    clearInterval(inboxTimer);
    statsObserver?.disconnect();
});
</script>

<template>
    <Head title="Zavelyx — Global SMS, OTP & Virtual Number Infrastructure" />

    <div class="premium-page relative min-h-screen bg-white dark:bg-[#060d1a] text-slate-900 dark:text-white">

        <!-- Ambient glows -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden z-0" aria-hidden="true">
            <div class="premium-atmosphere absolute inset-0"></div>
            <div class="absolute inset-x-0 top-0 h-[720px] bg-[linear-gradient(180deg,rgba(14,165,233,0.08),transparent_68%)] dark:bg-[linear-gradient(180deg,rgba(14,165,233,0.16),transparent_70%)]"></div>
            <div class="absolute inset-0 opacity-[0.45] dark:opacity-[0.28] bg-[linear-gradient(115deg,transparent_0%,rgba(14,165,233,0.08)_32%,transparent_54%,rgba(34,211,238,0.07)_76%,transparent_100%)]"></div>
        </div>

        <!-- ── Announcement Bar ── MUST be before the navbar in DOM, fixed z-[60] above navbar z-50 -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div v-if="announcementVisible"
                :class="['fixed inset-x-0 top-0 z-[60] bg-gradient-to-r text-white', announcementColorClass]">
                <div class="mx-auto max-w-7xl flex items-center justify-between gap-3 px-4 py-2">
                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                        <span v-if="siteSettings.announcement_icon" class="text-[15px] flex-shrink-0">{{ siteSettings.announcement_icon }}</span>
                        <a v-if="siteSettings.announcement_link" :href="siteSettings.announcement_link"
                            class="text-[12px] font-semibold truncate hover:underline underline-offset-2">
                            {{ siteSettings.announcement_text }}
                        </a>
                        <span v-else class="text-[12px] font-semibold truncate">{{ siteSettings.announcement_text }}</span>
                        <a v-if="siteSettings.announcement_cta && siteSettings.announcement_link"
                            :href="siteSettings.announcement_link"
                            class="flex-shrink-0 px-2.5 py-1 bg-white/25 hover:bg-white/35 rounded-lg text-[11px] font-bold transition-colors">
                            {{ siteSettings.announcement_cta }}
                        </a>
                        <span v-else-if="siteSettings.announcement_cta"
                            class="flex-shrink-0 px-2.5 py-1 bg-white/20 rounded-lg text-[11px] font-bold">
                            {{ siteSettings.announcement_cta }}
                        </span>
                    </div>
                    <button v-if="!siteSettings.announcement_pinned"
                        @click="dismissAnnouncement"
                        class="flex-shrink-0 p-1 rounded-lg hover:bg-white/20 transition-colors opacity-80 hover:opacity-100"
                        aria-label="Dismiss announcement">
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ── Navbar ── offset top by announcement bar height (40px) when visible -->
        <header :class="['fixed inset-x-0 z-50 transition-all duration-300',
            announcementVisible ? 'top-[40px]' : 'top-0',
            isScrolled ? 'border-b border-slate-200/80 dark:border-white/[0.07] bg-white/92 dark:bg-[#060d1a]/96 backdrop-blur-2xl shadow-sm dark:shadow-[0_1px_0_rgba(255,255,255,0.05)]' : 'bg-transparent']">
            <div class="mx-auto flex h-[68px] max-w-7xl items-center justify-between px-5 sm:px-8">

                <!-- Logo -->
                <Link href="/" class="group flex items-center gap-3 flex-shrink-0">
                    <img v-if="siteSettings.logo_url" :src="siteSettings.logo_url" alt="Logo"
                        class="h-9 max-w-[140px] object-contain" />
                    <template v-else>
                        <div class="relative">
                            <div class="absolute inset-0 rounded-xl blur-md transition-all duration-300 group-hover:blur-lg"
                                style="background: color-mix(in srgb, var(--color-primary) 35%, transparent)"></div>
                            <div class="relative flex h-9 w-9 items-center justify-center rounded-xl shadow-lg"
                                style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary)); box-shadow: 0 4px 16px color-mix(in srgb, var(--color-primary) 30%, transparent)">
                                <Zap class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <span class="text-base font-black tracking-tight">{{ siteSettings.name || 'Zavelyx' }}</span>
                    </template>
                </Link>

                <!-- Desktop nav links -->
                <nav class="hidden lg:flex items-center gap-0.5">
                    <a v-for="item in navItems" :key="item.label" :href="item.href"
                       class="rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-600 dark:text-slate-400 transition-all duration-150 hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-700 dark:hover:text-sky-300">
                        {{ item.label }}
                    </a>
                </nav>

                <!-- Desktop actions -->
                <div class="hidden lg:flex items-center gap-2.5">
                    <!-- Theme toggle -->
                    <button
                        class="rounded-xl border border-slate-200 dark:border-white/[0.10] bg-white dark:bg-white/[0.05] p-2.5 text-slate-500 dark:text-slate-400 transition-all duration-150 hover:border-sky-300 dark:hover:border-sky-500/40 hover:bg-sky-50 dark:hover:bg-sky-500/12 hover:text-sky-600 dark:hover:text-sky-300 shadow-sm dark:shadow-[0_1px_4px_rgba(0,0,0,0.3)] hover:shadow-sky-500/10 dark:hover:shadow-[0_2px_12px_rgba(14,165,233,0.18)] active:scale-95"
                        @click="toggleTheme" aria-label="Toggle theme">
                        <component :is="themeIcon" class="h-4 w-4" />
                    </button>
                    <!-- Sign in -->
                    <Link v-if="canLogin" :href="route('login')"
                          class="rounded-xl border border-slate-200 dark:border-white/[0.10] bg-white dark:bg-white/[0.05] px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-all duration-150 hover:border-sky-300 dark:hover:border-sky-500/40 hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-700 dark:hover:text-sky-300 shadow-sm dark:shadow-[0_1px_4px_rgba(0,0,0,0.3)] active:scale-95">
                        Sign in
                    </Link>
                    <!-- Get Started CTA -->
                    <Link v-if="canRegister" :href="route('register')"
                          class="rounded-xl bg-gradient-to-r from-sky-500 to-blue-500 px-5 py-2 text-sm font-bold text-white shadow-lg shadow-sky-500/30 dark:shadow-sky-500/40 transition-all duration-150 hover:from-sky-600 hover:to-blue-600 hover:-translate-y-px hover:shadow-sky-500/50 dark:hover:shadow-sky-500/55 active:scale-95 active:translate-y-0">
                        Get Started Free
                    </Link>
                </div>

                <!-- Mobile actions -->
                <div class="flex items-center gap-2 lg:hidden">
                    <button class="rounded-xl border border-slate-200 dark:border-white/[0.10] bg-white dark:bg-white/[0.05] p-2.5 text-slate-500 dark:text-slate-400 transition-all active:scale-95" @click="toggleTheme" aria-label="Toggle theme">
                        <component :is="themeIcon" class="h-4 w-4" />
                    </button>
                    <button class="rounded-xl border border-slate-200 dark:border-white/[0.10] bg-white dark:bg-white/[0.05] p-2.5 text-slate-500 dark:text-slate-400 transition-all active:scale-95" @click="menuOpen = !menuOpen" aria-label="Toggle menu">
                        <X v-if="menuOpen" class="h-4 w-4" /><Menu v-else class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="menuOpen" class="border-t border-slate-200 dark:border-white/[0.07] bg-white/96 dark:bg-[#060d1a]/98 backdrop-blur-2xl lg:hidden">
                <div class="px-5 py-4 space-y-1">
                    <a v-for="item in navItems" :key="item.label" :href="item.href"
                       class="flex rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-all hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-700 dark:hover:text-sky-300"
                       @click="menuOpen = false">{{ item.label }}</a>
                    <div class="flex gap-3 pt-3 border-t border-slate-100 dark:border-white/[0.07]">
                        <Link v-if="canLogin"    :href="route('login')"    class="flex-1 rounded-xl border border-slate-200 dark:border-white/[0.10] px-4 py-3 text-center text-sm font-bold text-slate-700 dark:text-slate-300">Sign in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="flex-1 rounded-xl bg-gradient-to-r from-sky-500 to-blue-500 px-4 py-3 text-center text-sm font-bold text-white shadow-lg shadow-sky-500/30">Get Started</Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── HERO ── extra top padding when announcement bar is visible (40px announcement + ~68px navbar) -->
        <section id="home" :class="['relative pb-20 sm:pb-28 px-5 sm:px-8 overflow-hidden',
            announcementVisible ? 'pt-[160px] sm:pt-[184px]' : 'pt-[120px] sm:pt-[144px]']">
            <!-- Dot grid overlay (dark only) -->
            <div class="hero-grid pointer-events-none absolute inset-0 opacity-0 dark:opacity-100" aria-hidden="true"></div>
            <!-- Radial fade at edges -->
            <div class="pointer-events-none absolute inset-0 dark:bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,transparent_60%,#060d1a_100%)]" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-7xl">
                <div class="flex flex-col lg:flex-row lg:items-center lg:gap-16 xl:gap-20">

                    <!-- Left: Text -->
                    <div class="flex-1 lg:max-w-[56%]">

                        <!-- Badge -->
                        <div class="premium-badge inline-flex items-center gap-2.5 rounded-full border border-sky-200 dark:border-sky-500/35 bg-sky-50 dark:bg-sky-500/[0.08] px-5 py-2.5 text-sm font-semibold text-sky-700 dark:text-sky-300 mb-8 shadow-sm dark:shadow-[0_0_20px_rgba(14,165,233,0.12)]">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                            </span>
                            Premium SMS &amp; OTP Infrastructure · 2.4M+ Activations
                        </div>

                        <!-- Headline -->
                        <h1 class="premium-title text-5xl sm:text-6xl lg:text-[66px] xl:text-[72px] font-black tracking-tight leading-[1.04] text-slate-900 dark:text-white mb-6">
                            Global SMS &amp;<br />
                            <span class="relative inline-block">
                                <span class="bg-gradient-to-r from-sky-400 via-cyan-400 to-blue-400 bg-clip-text text-transparent dark:[filter:drop-shadow(0_0_48px_rgba(34,211,238,0.45))]">Virtual Number</span>
                            </span><br />
                            <span class="text-slate-800 dark:text-slate-100">Infrastructure</span>
                        </h1>

                        <!-- Sub -->
                        <p class="text-xl text-slate-600 dark:text-slate-400 max-w-[540px] leading-8 mb-6">
                            Receive OTPs instantly. Access virtual numbers worldwide. Activate accounts across 300+ platforms. Build SMS verification workflows at scale.
                        </p>

                        <!-- Pills -->
                        <div class="flex flex-wrap gap-2 mb-10">
                            <span class="rounded-full bg-sky-100 dark:bg-sky-500/15 border border-sky-200 dark:border-sky-500/25 px-3 py-1.5 text-xs font-bold text-sky-700 dark:text-sky-400">Virtual Numbers</span>
                            <span class="rounded-full bg-emerald-100 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/25 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">Receive SMS</span>
                            <span class="rounded-full bg-violet-100 dark:bg-violet-500/15 border border-violet-200 dark:border-violet-500/25 px-3 py-1.5 text-xs font-bold text-violet-700 dark:text-violet-400">OTP Marketplace</span>
                            <span class="rounded-full bg-amber-100 dark:bg-amber-500/15 border border-amber-200 dark:border-amber-500/25 px-3 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-400">Developer API</span>
                            <span class="rounded-full bg-rose-100 dark:bg-rose-500/15 border border-rose-200 dark:border-rose-500/25 px-3 py-1.5 text-xs font-bold text-rose-700 dark:text-rose-400">150+ Countries</span>
                        </div>

                        <!-- CTAs -->
                        <div class="flex flex-col sm:flex-row items-start gap-4 mb-10">
                            <Link :href="route('register')"
                                  class="premium-primary group inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-blue-500 px-8 py-4 text-base font-bold text-white shadow-xl shadow-sky-500/30 dark:shadow-sky-500/40 transition-all duration-150 hover:from-sky-600 hover:to-blue-600 hover:-translate-y-0.5 hover:shadow-sky-500/50 active:scale-[0.98] active:translate-y-0">
                                Get Started Free <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                            </Link>
                            <a href="#api"
                               class="premium-secondary inline-flex items-center gap-2 rounded-2xl border border-slate-200 dark:border-white/[0.12] bg-white dark:bg-white/[0.04] px-8 py-4 text-base font-bold text-slate-700 dark:text-slate-300 transition-all duration-150 hover:border-sky-200 dark:hover:border-sky-500/35 hover:bg-sky-50 dark:hover:bg-sky-500/10 hover:text-sky-700 dark:hover:text-sky-300 shadow-sm dark:shadow-[0_2px_12px_rgba(0,0,0,0.3)]">
                                <Code2 class="h-4 w-4" /> Explore API
                            </a>
                        </div>

                        <!-- Trust row -->
                        <div class="premium-trust-row flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500 dark:text-slate-400">
                            <div class="hero-trust-pill flex items-center gap-2"><ShieldCheck class="h-4 w-4 text-emerald-500" />No KYC required</div>
                            <div class="hero-trust-pill flex items-center gap-2"><Zap class="h-4 w-4 text-sky-500" />Instant delivery</div>
                            <div class="hero-trust-pill flex items-center gap-2"><RefreshCcw class="h-4 w-4 text-violet-500" />Auto refund</div>
                            <div class="hero-trust-pill flex items-center gap-2"><Globe2 class="h-4 w-4 text-amber-500" />150+ countries</div>
                        </div>
                    </div>

                    <!-- Right: Floating cards (desktop) -->
                    <div class="hidden lg:block lg:flex-1 relative h-[500px] xl:h-[520px]" aria-hidden="true">
                        <div class="hero-console absolute inset-x-10 top-14 bottom-12 rounded-[2rem] border border-sky-200/60 dark:border-sky-400/15 bg-white/60 dark:bg-[#061326]/60 shadow-[0_40px_100px_rgba(14,165,233,0.16)] dark:shadow-[0_42px_120px_rgba(0,0,0,0.62)] backdrop-blur-2xl overflow-hidden">
                            <div class="absolute inset-0 hero-console-grid"></div>
                            <div class="absolute inset-x-8 top-8 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.75)]"></span>
                                    <span class="text-[11px] font-black uppercase text-slate-500 dark:text-slate-400">Live Network</span>
                                </div>
                                <span class="rounded-full border border-sky-200/70 dark:border-sky-400/20 bg-sky-50/80 dark:bg-sky-500/10 px-3 py-1 text-[10px] font-black text-sky-700 dark:text-sky-300">99.7%</span>
                            </div>
                            <div class="signal-line signal-line-one"></div>
                            <div class="signal-line signal-line-two"></div>
                            <div class="signal-line signal-line-three"></div>
                            <div class="absolute bottom-8 left-8 right-8 grid grid-cols-3 gap-3">
                                <div class="rounded-2xl border border-white/50 dark:border-white/[0.08] bg-white/70 dark:bg-white/[0.05] px-3 py-3">
                                    <p class="text-[10px] font-bold text-slate-400">Routes</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">700+</p>
                                </div>
                                <div class="rounded-2xl border border-white/50 dark:border-white/[0.08] bg-white/70 dark:bg-white/[0.05] px-3 py-3">
                                    <p class="text-[10px] font-bold text-slate-400">Inbox</p>
                                    <p class="text-lg font-black text-sky-600 dark:text-sky-300">Live</p>
                                </div>
                                <div class="rounded-2xl border border-white/50 dark:border-white/[0.08] bg-white/70 dark:bg-white/[0.05] px-3 py-3">
                                    <p class="text-[10px] font-bold text-slate-400">Latency</p>
                                    <p class="text-lg font-black text-emerald-600 dark:text-emerald-300">4s</p>
                                </div>
                            </div>
                        </div>

                        <!-- OTP Card -->
                        <div class="premium-card premium-shine absolute top-0 right-0 w-[290px] will-change-transform animate-float rounded-2xl border border-slate-200/60 dark:border-white/[0.13] bg-white/95 dark:bg-white/[0.07] backdrop-blur-xl p-5 shadow-2xl dark:shadow-[0_24px_60px_rgba(0,0,0,0.65)] dark:ring-1 dark:ring-inset dark:ring-white/[0.05]">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">OTP Received</span>
                                </div>
                                <span class="text-xs text-slate-400">2s ago</span>
                            </div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl shadow-md" style="background-color:rgba(37,211,102,0.18)">
                                    <svg viewBox="0 0 24 24" fill="#25D366" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">WhatsApp</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">+1 929-555-0142</p>
                                </div>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-black/30 border border-slate-200 dark:border-white/[0.09] px-4 py-3 mb-4">
                                <p class="text-[10px] text-slate-400 dark:text-slate-400 mb-1 font-medium uppercase tracking-wide">Verification Code</p>
                                <p class="text-3xl font-black tracking-[0.25em] text-slate-900 dark:text-white font-mono dark:[text-shadow:0_0_24px_rgba(34,211,238,0.35)]">847291</p>
                            </div>
                            <button class="w-full rounded-xl bg-sky-500 py-2.5 text-sm font-bold text-white shadow-md shadow-sky-500/30 hover:bg-sky-600 transition-colors">Copy Code</button>
                        </div>

                        <!-- SMS Inbox Card -->
                        <div class="premium-card premium-shine absolute bottom-0 left-4 w-[268px] will-change-transform animate-float-delayed rounded-2xl border border-slate-200/60 dark:border-white/[0.13] bg-white/95 dark:bg-white/[0.07] backdrop-blur-xl shadow-2xl dark:shadow-[0_24px_60px_rgba(0,0,0,0.6)] dark:ring-1 dark:ring-inset dark:ring-white/[0.05] overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-white/[0.08] bg-slate-50/80 dark:bg-black/25">
                                <div class="flex items-center gap-2">
                                    <Inbox class="h-3.5 w-3.5 text-sky-500" />
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">SMS Inbox</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">Live</span>
                                </div>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                <div class="px-4 py-3"><div class="flex items-center justify-between mb-1"><span class="text-xs font-bold text-slate-900 dark:text-white">Google</span><span class="text-[10px] text-slate-400">12s ago</span></div><p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">G-348291 is your verification code</p></div>
                                <div class="px-4 py-3"><div class="flex items-center justify-between mb-1"><span class="text-xs font-bold text-slate-900 dark:text-white">Telegram</span><span class="text-[10px] text-slate-400">45s ago</span></div><p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Your login code: 48291</p></div>
                                <div class="px-4 py-3"><div class="flex items-center justify-between mb-1"><span class="text-xs font-bold text-slate-900 dark:text-white">Binance</span><span class="text-[10px] text-slate-400">1m ago</span></div><p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Verification code: 294810</p></div>
                            </div>
                        </div>

                        <!-- Stat badge -->
                        <div class="premium-card absolute top-1/2 -translate-y-1/2 left-0 will-change-transform animate-float rounded-2xl border border-slate-200/60 dark:border-white/[0.13] bg-white/95 dark:bg-white/[0.07] backdrop-blur-xl px-4 py-3.5 shadow-xl dark:shadow-[0_8px_32px_rgba(0,0,0,0.55)] dark:ring-1 dark:ring-inset dark:ring-white/[0.05]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/12 dark:bg-sky-500/22">
                                    <Activity class="h-4 w-4 text-sky-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">12,847</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">activations today</p>
                                </div>
                            </div>
                        </div>

                        <!-- Country badge -->
                        <div class="premium-card absolute top-16 left-12 will-change-transform animate-float-delayed rounded-xl border border-slate-200/60 dark:border-emerald-500/30 bg-white/95 dark:bg-white/[0.07] backdrop-blur-xl px-3 py-2 shadow-lg dark:shadow-[0_4px_20px_rgba(0,0,0,0.45)] dark:ring-1 dark:ring-inset dark:ring-white/[0.05]">
                            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">Coverage</p>
                            <p class="text-sm font-black text-emerald-600 dark:text-emerald-400">150+ Countries</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Stats Bar ── -->
        <section id="stats-bar" class="py-10 border-y border-slate-100 dark:border-white/[0.07] bg-slate-50/60 dark:bg-[var(--bg-page)]">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div v-for="(s, i) in statsConfig" :key="s.label"
                         class="premium-card premium-shine group relative rounded-2xl border border-slate-200/60 dark:border-white/[0.07] bg-white dark:bg-[#0a1628] px-5 py-5 text-center transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-200 dark:hover:border-sky-500/25 hover:shadow-lg dark:hover:shadow-[0_8px_32px_rgba(0,0,0,0.45)] cursor-default">
                        <div class="absolute inset-0 rounded-2xl opacity-0 dark:group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" style="background:radial-gradient(circle at center,rgba(14,165,233,0.05) 0%,transparent 70%)"></div>
                        <p :class="['text-3xl font-black tracking-tight tabular-nums', s.color]">{{ s.format(statValues[i]) }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">{{ s.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Platform Capabilities ── -->
        <section id="services" class="py-24 px-5 sm:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 dark:border-sky-500/25 bg-sky-50 dark:bg-sky-500/10 px-4 py-2 text-sm font-semibold text-sky-700 dark:text-sky-400 mb-5">
                        <Zap class="h-3.5 w-3.5" /> Platform Capabilities
                    </div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-4">One platform. Four core services.</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Zavelyx combines virtual numbers, SMS receiving, OTP activations, and developer API into one unified platform.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="cap in capabilities" :key="cap.title"
                         class="premium-card premium-shine group relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] p-7 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.4)] transition-all duration-200 hover:-translate-y-1 hover:shadow-xl dark:hover:shadow-[0_16px_48px_rgba(14,165,233,0.10)] hover:border-sky-200 dark:hover:border-sky-500/30">
                        <!-- Bottom gradient bar -->
                        <div class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300" :class="cap.gradient"></div>
                        <!-- Top corner glow -->
                        <div class="absolute top-0 right-0 h-24 w-24 rounded-full opacity-0 dark:group-hover:opacity-100 transition-opacity duration-300 pointer-events-none blur-2xl" :class="'bg-gradient-to-br ' + cap.gradient" style="opacity:0.06;transform:translate(30%,-30%)"></div>
                        <div :class="['flex items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg mb-5', cap.gradient]" style="height:52px;width:52px;">
                            <component :is="cap.icon" class="h-6 w-6 text-white" />
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">{{ cap.title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-4">{{ cap.desc }}</p>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 dark:bg-white/[0.07] border border-slate-200 dark:border-white/[0.07] px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                            {{ cap.stat }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Receive SMS ── -->
        <section id="receive-sms" class="py-24 px-5 sm:px-8 bg-slate-50/70 dark:bg-[#050b16] border-y border-slate-100 dark:border-white/[0.07]">
            <div class="mx-auto max-w-7xl">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 dark:border-emerald-500/25 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-6">
                            <Inbox class="h-3.5 w-3.5" /> Receive SMS Online
                        </div>
                        <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-5">Get a live SMS inbox,<br />instantly</h2>
                        <p class="text-lg text-slate-600 dark:text-slate-400 leading-8 mb-8">
                            Request a virtual number and receive all incoming SMS messages in a live, real-time inbox on your dashboard. No app needed — just your browser.
                        </p>
                        <ul class="space-y-4 mb-8">
                            <li v-for="item in ['Real-time message delivery — see SMS as it arrives', 'Public or private number options', 'Message history and export', 'Works with any SMS sender, globally', 'Automatic number replacement on failure']" :key="item" class="flex items-start gap-3">
                                <div class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-500/15 mt-0.5">
                                    <Check class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ item }}</span>
                            </li>
                        </ul>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition-all hover:bg-emerald-600 hover:-translate-y-px hover:shadow-emerald-500/40 active:scale-[0.98]">
                            Try Receive SMS <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <!-- Inbox mockup -->
                    <div class="premium-card premium-shine rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] shadow-2xl dark:shadow-[0_24px_60px_rgba(0,0,0,0.55)] dark:ring-1 dark:ring-inset dark:ring-white/[0.05] overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/[0.08] bg-slate-50 dark:bg-[#060e1c]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 shadow-sm shadow-emerald-500/30">
                                    <Inbox class="h-4 w-4 text-white" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">SMS Inbox</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">+1 (929) 555-0142 · 🇺🇸</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Active</span>
                            </div>
                        </div>

                        <!-- Fixed-height wrapper prevents layout shift -->
                        <div class="relative overflow-hidden" style="min-height:276px">
                            <TransitionGroup name="inbox-slide" tag="div" class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                                <div v-for="msg in visibleInbox" :key="msg.service + msg.code"
                                     class="px-5 py-4 hover:bg-slate-50 dark:hover:bg-white/[0.025] transition-colors duration-150">
                                    <div class="flex items-start gap-3">
                                        <span class="text-xl flex-shrink-0 mt-0.5">{{ msg.flag }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <div class="flex items-center gap-2">
                                                    <span :class="['h-2 w-2 rounded-full flex-shrink-0', msg.dot]"></span>
                                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ msg.service }}</span>
                                                </div>
                                                <span class="text-[11px] text-slate-400 flex-shrink-0">{{ msg.time }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mb-0.5">{{ msg.number }}</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ msg.text }}</p>
                                            <div class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-sky-50 dark:bg-sky-500/15 border border-sky-200 dark:border-sky-500/20 px-2.5 py-1">
                                                <span class="text-xs font-black text-sky-700 dark:text-sky-400 font-mono tracking-widest">{{ msg.code }}</span>
                                                <Check class="h-3 w-3 text-sky-500" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 py-3 border-t border-slate-100 dark:border-white/[0.08] bg-slate-50 dark:bg-[#060e1c] flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">Auto-refreshes every 5s</span>
                            <span class="text-[11px] text-sky-600 dark:text-sky-400 font-semibold">18:42 left in session</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── OTP Activations ── -->
        <section id="otp" class="py-24 px-5 sm:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-200 dark:border-violet-500/25 bg-violet-50 dark:bg-violet-500/10 px-4 py-2 text-sm font-semibold text-violet-700 dark:text-violet-400 mb-5">
                        <Smartphone class="h-3.5 w-3.5" /> OTP Marketplace
                    </div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Activate any account instantly</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Choose from 300+ platforms. Buy a real number, receive your OTP in seconds. Starting from $0.03 per activation.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div v-for="svc in services" :key="svc.name"
                         :class="['premium-card premium-shine relative group rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] p-5 transition-all duration-200 hover:-translate-y-1 cursor-pointer', svc.glow]">
                        <div v-if="svc.popular" class="absolute -top-2.5 -right-2.5 rounded-full bg-sky-500 px-2.5 py-0.5 text-[10px] font-bold text-white shadow-md shadow-sky-500/30">Popular</div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl mb-4 transition-transform duration-200 group-hover:scale-110" :style="{ backgroundColor: svc.brand + '20', border: '1px solid ' + svc.brand + '30' }" v-html="svc.icon"></div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">{{ svc.name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">from {{ svc.price }}</p>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200/60 dark:border-white/[0.07]">
                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">{{ svc.rate }} success</span>
                            <Link :href="route('register')" class="text-[10px] font-bold text-sky-600 dark:text-sky-400 transition-colors flex items-center gap-0.5">
                                Buy <ArrowRight class="h-2.5 w-2.5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-8">
                    + 290 more services: Amazon, Twitter, Snapchat, LinkedIn, all major exchanges.
                    <Link :href="route('register')" class="text-sky-600 dark:text-sky-400 font-semibold hover:underline ml-1">View all →</Link>
                </p>
            </div>
        </section>

        <!-- ── API & Developers ── -->
        <section id="api" class="py-24 px-5 sm:px-8 bg-slate-50/70 dark:bg-[#050b16] border-y border-slate-100 dark:border-white/[0.07]">
            <div class="mx-auto max-w-7xl">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 dark:border-amber-500/25 bg-amber-50 dark:bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-700 dark:text-amber-400 mb-6">
                            <Code2 class="h-3.5 w-3.5" /> Developer API
                        </div>
                        <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-5">Built for developers<br />and resellers</h2>
                        <p class="text-lg text-slate-600 dark:text-slate-400 leading-8 mb-8">
                            Integrate Zavelyx's SMS and virtual number capabilities directly into your application. REST API with full documentation, webhook events, and a sandbox environment.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li v-for="item in ['RESTful API with JSON responses', 'Real-time webhook events on OTP delivery', 'Sandbox environment for testing', 'API key management and rate limiting', 'Bulk activation support', 'Reseller pricing available']" :key="item" class="flex items-center gap-3">
                                <div class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/15">
                                    <Check class="h-3 w-3 text-amber-600 dark:text-amber-400" />
                                </div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ item }}</span>
                            </li>
                        </ul>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-7 py-3.5 text-sm font-bold text-amber-700 dark:text-amber-300 transition-all hover:bg-amber-100 dark:hover:bg-amber-500/18 hover:-translate-y-px active:scale-[0.98]">
                            Get API Access <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <!-- Code block -->
                    <div class="premium-terminal rounded-2xl overflow-hidden border border-slate-200 dark:border-sky-500/20 shadow-2xl dark:shadow-[0_24px_60px_rgba(0,0,0,0.6)] dark:ring-1 dark:ring-sky-500/10">
                        <div class="flex items-center gap-1 px-4 py-3 bg-slate-100 dark:bg-[#040910] border-b border-slate-200 dark:border-white/[0.07]">
                            <div class="h-3 w-3 rounded-full bg-red-400/80"></div>
                            <div class="h-3 w-3 rounded-full bg-amber-400/80 ml-1"></div>
                            <div class="h-3 w-3 rounded-full bg-emerald-400/80 ml-1"></div>
                            <span class="ml-4 text-xs text-slate-400 dark:text-slate-400 font-mono">POST /api/v1/number</span>
                        </div>
                        <div class="bg-[#090f1e] dark:bg-[#040910] p-5 border-b border-white/[0.05]">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Request</p>
                            <pre class="text-xs font-mono leading-6 overflow-x-auto"><span class="text-sky-400">POST</span> <span class="text-white">/api/v1/number</span> <span class="text-slate-500">HTTP/1.1</span>
<span class="text-slate-400">Authorization:</span> <span class="text-emerald-400">Bearer nhk_live_xxxxxxxxxx</span>
<span class="text-slate-400">Content-Type:</span> <span class="text-emerald-400">application/json</span>

<span class="text-slate-300">{</span>
  <span class="text-sky-300">"service"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"whatsapp"</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"country"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"US"</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"operator"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"any"</span>
<span class="text-slate-300">}</span></pre>
                        </div>
                        <div class="bg-[#060c18] dark:bg-[#030810] p-5">
                            <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-3">Response · 200 OK</p>
                            <pre class="text-xs font-mono leading-6 overflow-x-auto"><span class="text-slate-300">{</span>
  <span class="text-sky-300">"success"</span><span class="text-slate-300">:</span> <span class="text-violet-400">true</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"number"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"+1 929-555-0142"</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"session_id"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"act_8k2xYnPqR4"</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"expires_in"</span><span class="text-slate-300">:</span> <span class="text-emerald-400">1200</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"country"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"US"</span><span class="text-slate-300">,</span>
  <span class="text-sky-300">"service"</span><span class="text-slate-300">:</span> <span class="text-amber-300">"whatsapp"</span>
<span class="text-slate-300">}</span></pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Global Coverage ── -->
        <section id="coverage" class="py-24 px-5 sm:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 dark:border-emerald-500/25 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-5">
                        <Globe2 class="h-3.5 w-3.5" /> Global Coverage
                    </div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Numbers from everywhere</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-xl mx-auto">Real SIM-backed numbers across 150+ countries and 700+ carriers.</p>
                </div>

                <!-- Coverage stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-12">
                    <div v-for="s in [{val:'150+',label:'Countries',color:'sky'},{val:'700+',label:'Operators',color:'emerald'},{val:'2.4M+',label:'Activations',color:'violet'},{val:'99.7%',label:'Success Rate',color:'amber'}]" :key="s.label"
                         :class="['premium-card rounded-2xl border p-6 text-center dark:ring-1 dark:ring-inset', s.color==='sky'&&'border-sky-200 dark:border-sky-500/20 bg-sky-50 dark:bg-sky-500/[0.07] dark:ring-sky-500/10', s.color==='emerald'&&'border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/[0.07] dark:ring-emerald-500/10', s.color==='violet'&&'border-violet-200 dark:border-violet-500/20 bg-violet-50 dark:bg-violet-500/[0.07] dark:ring-violet-500/10', s.color==='amber'&&'border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-500/[0.07] dark:ring-amber-500/10']">
                        <p :class="['text-4xl font-black tracking-tight', s.color==='sky'&&'text-sky-600 dark:text-sky-400', s.color==='emerald'&&'text-emerald-600 dark:text-emerald-400', s.color==='violet'&&'text-violet-600 dark:text-violet-400', s.color==='amber'&&'text-amber-600 dark:text-amber-400']">{{ s.val }}</p>
                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 mt-1">{{ s.label }}</p>
                    </div>
                </div>

                <!-- Country grid -->
                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3">
                    <div v-for="c in countryCoverage" :key="c.name"
                         class="premium-card group flex flex-col items-center gap-1.5 rounded-xl border border-slate-200/80 dark:border-white/[0.07] bg-white dark:bg-[#0a1628] p-3 text-center transition-all duration-200 hover:-translate-y-1 hover:scale-105 hover:border-sky-200 dark:hover:border-sky-500/30 hover:shadow-md dark:hover:shadow-[0_8px_24px_rgba(14,165,233,0.12)] hover:z-10 cursor-default">
                        <span class="text-2xl transition-transform duration-200 group-hover:scale-110">{{ c.flag }}</span>
                        <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 leading-tight">{{ c.name }}</span>
                    </div>
                </div>
                <p class="text-center text-sm text-slate-400 dark:text-slate-400 mt-6">+ 126 more countries available in your dashboard</p>
            </div>
        </section>

        <!-- ── Live Activity ── -->
        <section class="py-24 px-5 sm:px-8 bg-slate-50/70 dark:bg-[#050b16] border-y border-slate-100 dark:border-white/[0.07]">
            <div class="mx-auto max-w-5xl">
                <div class="grid lg:grid-cols-2 gap-14 items-start">
                    <div class="lg:sticky lg:top-28">
                        <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 dark:border-sky-500/25 bg-sky-50 dark:bg-sky-500/10 px-4 py-2 text-sm font-semibold text-sky-700 dark:text-sky-400 mb-6">
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span></span>
                            Live Activity
                        </div>
                        <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-5">Real activations,<br />happening now</h2>
                        <p class="text-lg text-slate-600 dark:text-slate-400 leading-8 mb-8">Thousands of OTP activations and SMS deliveries happen through Zavelyx every hour. Join them — your first activation is seconds away.</p>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 rounded-2xl bg-sky-500 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-sky-500/30 transition-all hover:bg-sky-600 hover:-translate-y-px hover:shadow-sky-500/45 active:scale-[0.98]">
                            Start Now <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div>
                        <!-- Fixed-height wrapper prevents layout shift on item cycle -->
                        <div class="relative" style="min-height:316px">
                            <TransitionGroup name="live-feed" tag="div" class="space-y-3">
                                <div v-for="activity in visibleActivities" :key="activity.country + activity.service"
                                     class="premium-card flex items-center gap-4 rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] px-5 py-4 shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.35)] transition-colors hover:border-sky-200 dark:hover:border-sky-500/20 dark:hover:bg-[#0c1c34]">
                                    <div class="text-2xl flex-shrink-0">{{ activity.flag }}</div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ activity.service }} · {{ activity.country }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ activity.number }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['h-1.5 w-1.5 rounded-full', activity.dot]"></span>
                                            <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ activity.type }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">{{ activity.time }}</span>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </div>
                        <div class="mt-3 rounded-2xl border border-sky-200 dark:border-sky-500/20 bg-sky-50 dark:bg-sky-500/[0.07] dark:ring-1 dark:ring-inset dark:ring-sky-500/10 px-5 py-3 text-center">
                            <p class="text-sm text-sky-700 dark:text-sky-400 font-semibold"><span class="font-black text-sky-600 dark:text-sky-300">2,400,000+</span> total activations across all services</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Why Zavelyx ── -->
        <section id="features" class="py-24 px-5 sm:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-200 dark:border-violet-500/25 bg-violet-50 dark:bg-violet-500/10 px-4 py-2 text-sm font-semibold text-violet-700 dark:text-violet-400 mb-5">
                        <Check class="h-3.5 w-3.5" /> Why Zavelyx
                    </div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Built for reliability at scale</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400 max-w-xl mx-auto">Every feature designed to deliver OTPs fast, reliably, and affordably.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div v-for="f in features" :key="f.title"
                         class="premium-card premium-shine group rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] p-5 shadow-sm dark:shadow-[0_4px_24px_rgba(0,0,0,0.35)] transition-all duration-200 hover:border-sky-200 dark:hover:border-sky-500/30 hover:-translate-y-1 hover:shadow-lg dark:hover:shadow-[0_12px_36px_rgba(14,165,233,0.10)]">
                        <div :class="['flex h-10 w-10 items-center justify-center rounded-xl mb-4 transition-transform duration-200 group-hover:scale-110',
                            f.color==='sky'    &&'bg-sky-100 dark:bg-sky-500/15',
                            f.color==='emerald'&&'bg-emerald-100 dark:bg-emerald-500/15',
                            f.color==='violet' &&'bg-violet-100 dark:bg-violet-500/15',
                            f.color==='amber'  &&'bg-amber-100 dark:bg-amber-500/15',
                            f.color==='rose'   &&'bg-rose-100 dark:bg-rose-500/15',
                            f.color==='teal'   &&'bg-teal-100 dark:bg-teal-500/15',
                            f.color==='indigo' &&'bg-indigo-100 dark:bg-indigo-500/15',
                            f.color==='pink'   &&'bg-pink-100 dark:bg-pink-500/15',
                        ]">
                            <component :is="f.icon" :class="['h-5 w-5',
                                f.color==='sky'    &&'text-sky-600 dark:text-sky-400',
                                f.color==='emerald'&&'text-emerald-600 dark:text-emerald-400',
                                f.color==='violet' &&'text-violet-600 dark:text-violet-400',
                                f.color==='amber'  &&'text-amber-600 dark:text-amber-400',
                                f.color==='rose'   &&'text-rose-600 dark:text-rose-400',
                                f.color==='teal'   &&'text-teal-600 dark:text-teal-400',
                                f.color==='indigo' &&'text-indigo-600 dark:text-indigo-400',
                                f.color==='pink'   &&'text-pink-600 dark:text-pink-400',
                            ]" />
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">{{ f.title }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── FAQ ── -->
        <section id="faq" class="py-24 px-5 sm:px-8 bg-slate-50/70 dark:bg-[#050b16] border-y border-slate-100 dark:border-white/[0.07]">
            <div class="mx-auto max-w-3xl">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 dark:border-amber-500/25 bg-amber-50 dark:bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-700 dark:text-amber-400 mb-5">
                        <MessageCircle class="h-3.5 w-3.5" /> FAQ
                    </div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Common questions</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400">Everything you need to know about Zavelyx services.</p>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(faq, i) in faqs" :key="i"
                         class="premium-card rounded-2xl border border-slate-200/80 dark:border-white/[0.09] bg-white dark:bg-[#0a1628] overflow-hidden shadow-sm dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)] transition-all duration-200"
                         :class="faqOpen === i && 'dark:border-sky-500/25 dark:shadow-[0_4px_24px_rgba(14,165,233,0.08)]'">
                        <button class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]" @click="toggleFaq(i)">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ faq.q }}</span>
                            <ChevronDown :class="['h-4 w-4 flex-shrink-0 text-slate-400 dark:text-slate-400 transition-transform duration-200', faqOpen === i && 'rotate-180 text-sky-500 dark:text-sky-400']" />
                        </button>
                        <div v-if="faqOpen === i" class="px-6 pb-5 border-t border-slate-100 dark:border-white/[0.07]">
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-7 mt-4">{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-10 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Still have questions?
                        <a :href="supportLink" class="text-sky-600 dark:text-sky-400 font-semibold hover:underline ml-1">Contact support →</a>
                    </p>
                </div>
            </div>
        </section>

        <!-- ── CTA Banner ── -->
        <section class="py-20 px-5 sm:px-8">
            <div class="mx-auto max-w-4xl">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-500 via-blue-600 to-sky-500 p-12 text-center shadow-2xl shadow-sky-500/25 dark:shadow-sky-500/35">
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute -top-20 -right-20 h-60 w-60 rounded-full bg-white/10 blur-3xl"></div>
                        <div class="absolute -bottom-20 -left-20 h-60 w-60 rounded-full bg-white/8 blur-3xl"></div>
                    </div>
                    <div class="relative">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold text-white mb-6">
                            <Zap class="h-3.5 w-3.5" /> Free to start · No credit card required
                        </div>
                        <h2 class="text-4xl font-black tracking-tight text-white mb-4">Ready to get started?</h2>
                        <p class="text-blue-100 text-lg mb-8 max-w-xl mx-auto">Create your free account and receive your first OTP in under 60 seconds. Virtual numbers, SMS inbox, and API access — all in one platform.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link :href="route('register')" class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-8 py-4 text-base font-black text-sky-600 shadow-xl transition-all hover:-translate-y-0.5 hover:shadow-2xl active:scale-[0.98]">
                                Create Free Account <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                            </Link>
                            <Link v-if="canLogin" :href="route('login')" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-8 py-4 text-base font-bold text-white backdrop-blur-sm transition-all hover:bg-white/20 active:scale-[0.98]">
                                Sign in instead
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ── -->
        <footer class="border-t border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[var(--bg-page)] py-16 px-5 sm:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-2 gap-10 lg:grid-cols-4 mb-12">
                    <div class="col-span-2 lg:col-span-1">
                        <div class="flex items-center gap-3 mb-4">
                            <img v-if="siteSettings.logo_footer || siteSettings.logo_url"
                                :src="siteSettings.logo_footer || siteSettings.logo_url"
                                alt="Logo" class="h-9 max-w-[140px] object-contain" />
                            <template v-else>
                                <div class="relative">
                                    <div class="absolute inset-0 rounded-xl blur-md" style="background: color-mix(in srgb, var(--color-primary) 30%, transparent)"></div>
                                    <div class="relative flex h-9 w-9 items-center justify-center rounded-xl shadow-lg"
                                        style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary))">
                                        <Zap class="h-4 w-4 text-white" />
                                    </div>
                                </div>
                                <span class="text-base font-black">{{ siteSettings.name || 'Zavelyx' }}</span>
                            </template>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-7 max-w-[210px]">Global SMS, OTP &amp; virtual number infrastructure for individuals and enterprises.</p>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-4">Platform</p>
                        <ul class="space-y-3">
                            <li><a href="#services"    class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Virtual Numbers</a></li>
                            <li><a href="#receive-sms" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Receive SMS</a></li>
                            <li><a href="#otp"         class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">OTP Marketplace</a></li>
                            <li><a href="#api"         class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Developer API</a></li>
                            <li><a href="#coverage"    class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Global Coverage</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-4">Company</p>
                        <ul class="space-y-3">
                            <li><a href="#features" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Why Zavelyx</a></li>
                            <li><a href="#faq"       class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">FAQ</a></li>
                            <li><a :href="supportLink" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Contact Support</a></li>
                            <li><a :href="supportLink" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">API Inquiries</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400 dark:text-slate-400 mb-4">Legal</p>
                        <ul class="space-y-3">
                            <li><Link :href="route('terms')"   class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Terms of Service</Link></li>
                            <li><Link :href="route('privacy')" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Privacy Policy</Link></li>
                            <li><a :href="supportLink" class="text-sm text-slate-600 dark:text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">Legal Inquiries</a></li>
                        </ul>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100 dark:border-white/[0.07] pt-8">
                    <p class="text-sm text-slate-400 dark:text-slate-400">{{ siteSettings.footer_text || '© 2026 Zavelyx. All rights reserved.' }}</p>
                    <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-400">
                        <ShieldCheck class="h-3.5 w-3.5 text-sky-500/60" />
                        <span>Secured with 256-bit TLS encryption</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Premium homepage finish */
.premium-page {
    isolation: isolate;
}

.premium-atmosphere {
    background:
        linear-gradient(120deg, rgba(14, 165, 233, 0.07), transparent 28%, rgba(34, 211, 238, 0.06) 58%, transparent 84%),
        repeating-linear-gradient(90deg, rgba(14, 165, 233, 0.05) 0 1px, transparent 1px 120px);
    mask-image: linear-gradient(to bottom, black, transparent 82%);
}

.premium-badge,
.premium-secondary,
.premium-card,
.premium-terminal {
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.68) inset,
        0 18px 50px rgba(15, 23, 42, 0.08);
}

:global(.dark .premium-badge),
:global(.dark .premium-secondary),
:global(.dark .premium-card),
:global(.dark .premium-terminal) {
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.08) inset,
        0 22px 70px rgba(0, 0, 0, 0.42),
        0 0 0 1px rgba(14, 165, 233, 0.04);
}

.premium-title {
    text-wrap: balance;
}

.premium-primary {
    position: relative;
    overflow: hidden;
    box-shadow:
        0 18px 44px rgba(14, 165, 233, 0.32),
        0 1px 0 rgba(255, 255, 255, 0.32) inset;
}

.premium-primary::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, 0.34) 42%, transparent 62%);
    transform: translateX(-120%);
    animation: premium-sweep 4.8s ease-in-out infinite;
}

.premium-primary > * {
    position: relative;
    z-index: 1;
}

.premium-secondary {
    backdrop-filter: blur(18px);
}

.premium-trust-row > div {
    border-radius: 999px;
    padding: 0.35rem 0.65rem;
    background: rgba(255, 255, 255, 0.58);
    border: 1px solid rgba(148, 163, 184, 0.16);
}

:global(html.dark .hero-trust-pill) {
    background: rgba(15, 23, 42, 0.78) !important;
    color: #e5f0ff !important;
    border: 1px solid rgba(56, 189, 248, 0.25) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22) !important;
    backdrop-filter: blur(12px);
}

.premium-card {
    position: relative;
    backdrop-filter: blur(18px);
}

.premium-shine {
    overflow: hidden;
}

.premium-shine::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(125deg, rgba(255, 255, 255, 0.28), transparent 28%, transparent 72%, rgba(14, 165, 233, 0.12));
    opacity: 0;
    transition: opacity 220ms ease;
}

.premium-shine:hover::after {
    opacity: 1;
}

.hero-console::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(14, 165, 233, 0.16), transparent 35%),
        linear-gradient(315deg, rgba(34, 211, 238, 0.12), transparent 42%);
    opacity: 0.9;
}

.hero-console-grid {
    background-image:
        linear-gradient(rgba(14, 165, 233, 0.12) 1px, transparent 1px),
        linear-gradient(90deg, rgba(14, 165, 233, 0.12) 1px, transparent 1px);
    background-size: 34px 34px;
    mask-image: radial-gradient(ellipse 70% 70% at 50% 45%, black, transparent 76%);
}

.signal-line {
    position: absolute;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.86), rgba(14, 165, 233, 0.38), transparent);
    filter: drop-shadow(0 0 14px rgba(34, 211, 238, 0.45));
    transform-origin: left center;
}

.signal-line-one {
    left: 64px;
    right: 88px;
    top: 42%;
    transform: rotate(-12deg);
    animation: signal-pulse 3.4s ease-in-out infinite;
}

.signal-line-two {
    left: 110px;
    right: 60px;
    top: 52%;
    transform: rotate(13deg);
    animation: signal-pulse 4.1s ease-in-out 0.6s infinite;
}

.signal-line-three {
    left: 82px;
    right: 120px;
    top: 33%;
    transform: rotate(22deg);
    animation: signal-pulse 4.6s ease-in-out 1.2s infinite;
}

.premium-terminal {
    position: relative;
}

.premium-terminal::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(120deg, rgba(14, 165, 233, 0.16), transparent 28%, rgba(34, 211, 238, 0.10) 72%, transparent);
    opacity: 0.32;
    z-index: 1;
}

.premium-terminal > * {
    position: relative;
    z-index: 2;
}

@keyframes premium-sweep {
    0%, 48% { transform: translateX(-130%); }
    72%, 100% { transform: translateX(130%); }
}

@keyframes signal-pulse {
    0%, 100% { opacity: 0.28; }
    50% { opacity: 1; }
}

/* ── Hero dot grid ── */
.hero-grid {
    background-image: radial-gradient(circle, rgba(34,211,238,0.10) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(ellipse 80% 70% at 50% 0%, black 50%, transparent 100%);
}

/* ── Hero floating cards ── */
.animate-float         { animation: float 5s ease-in-out infinite; }
.animate-float-delayed { animation: float 5s ease-in-out 2.5s infinite; }
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-10px); }
}

/* ── Background glow drift ── */
.animate-drift-slow         { animation: drift 16s ease-in-out infinite; }
.animate-drift-slow-reverse { animation: drift 20s ease-in-out reverse infinite; }
@keyframes drift {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(30px, -20px) scale(1.06); }
}

/* ── Live feed TransitionGroup — prevents layout shift ── */
/* Items move smoothly via FLIP */
.live-feed-move {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
/* Entering item fades + rises in */
.live-feed-enter-active {
    transition: opacity 0.4s ease, transform 0.4s ease;
}
/* Leaving item is taken out of flow so container height stays fixed */
.live-feed-leave-active {
    transition: opacity 0.3s ease;
    position: absolute;
    width: 100%;
    left: 0;
}
.live-feed-enter-from { opacity: 0; transform: translateY(8px); }
.live-feed-leave-to   { opacity: 0; }

/* ── Inbox slide TransitionGroup — prevents layout shift ── */
.inbox-slide-move {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.inbox-slide-enter-active {
    transition: opacity 0.4s ease;
}
.inbox-slide-leave-active {
    transition: opacity 0.25s ease;
    position: absolute;
    width: 100%;
    left: 0;
}
.inbox-slide-enter-from { opacity: 0; }
.inbox-slide-leave-to   { opacity: 0; }
</style>

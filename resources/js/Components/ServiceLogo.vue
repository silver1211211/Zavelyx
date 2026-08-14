<script setup>
const props = defineProps({
    service: { type: String, required: true },
    size:    { type: Number, default: 36 },
});

// Brand colors (background fallback when SVG not available)
const BRAND = {
    telegram:    { bg: '#2AABEE', text: '#fff' },
    whatsapp:    { bg: '#25D366', text: '#fff' },
    google:      { bg: '#fff',    text: '#4285F4', border: '#e5e7eb' },
    discord:     { bg: '#5865F2', text: '#fff' },
    openai:      { bg: '#10a37f', text: '#fff' },
    tiktok:      { bg: '#010101', text: '#fff' },
    instagram:   { bg: 'linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)', text: '#fff' },
    facebook:    { bg: '#1877F2', text: '#fff' },
    twitter:     { bg: '#000',    text: '#fff' },
    amazon:      { bg: '#FF9900', text: '#000' },
    microsoft:   { bg: '#f3f3f3', text: '#00A4EF' },
    binance:     { bg: '#F3BA2F', text: '#000' },
    bybit:       { bg: '#F7A600', text: '#000' },
    okx:         { bg: '#000',    text: '#fff' },
    paypal:      { bg: '#003087', text: '#fff' },
    netflix:     { bg: '#E50914', text: '#fff' },
    apple:       { bg: '#000',    text: '#fff' },
    uber:        { bg: '#000',    text: '#fff' },
    linkedin:    { bg: '#0A66C2', text: '#fff' },
    snapchat:    { bg: '#FFFC00', text: '#000' },
    steam:       { bg: '#1B2838', text: '#fff' },
    coinbase:    { bg: '#0052FF', text: '#fff' },
    wise:        { bg: '#00B9FF', text: '#fff' },
    revolut:     { bg: '#191C1F', text: '#fff' },
    airbnb:      { bg: '#FF5A5F', text: '#fff' },
    ebay:        { bg: '#fff',    text: '#e53238', border: '#e5e7eb' },
    tinder:      { bg: '#FD5068', text: '#fff' },
    viber:       { bg: '#7360F2', text: '#fff' },
    line:        { bg: '#00B900', text: '#fff' },
    wechat:      { bg: '#07C160', text: '#fff' },
    kakao:       { bg: '#FEE500', text: '#000' },
    yahoo:       { bg: '#720E9E', text: '#fff' },
    shopify:     { bg: '#96BF48', text: '#fff' },
    stripe:      { bg: '#635BFF', text: '#fff' },
    nike:        { bg: '#000',    text: '#fff' },
    vk:          { bg: '#0077FF', text: '#fff' },
    grab:        { bg: '#00B14F', text: '#fff' },
    lazada:      { bg: '#F57224', text: '#fff' },
    shopee:      { bg: '#EE4D2D', text: '#fff' },
    gojek:       { bg: '#00880A', text: '#fff' },
    avito:       { bg: '#00AAFF', text: '#fff' },
    ozon:        { bg: '#005BFF', text: '#fff' },
};

const id = (props.service ?? '').toLowerCase().trim();
const brand = BRAND[id];
const initial = (props.service?.[0] ?? '?').toUpperCase();

// Fallback colors for unknown services — deterministic from service name
function hashColor(str) {
    const COLORS = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#0ea5e9','#ef4444','#14b8a6','#f97316'];
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) & 0xffff;
    return COLORS[h % COLORS.length];
}

const fallbackBg   = brand?.bg ?? hashColor(id);
const fallbackText = brand?.text ?? '#fff';
</script>

<template>
    <div :style="`width:${size}px;height:${size}px;flex-shrink:0`" class="relative rounded-xl overflow-hidden">

        <!-- ── Known brand logos (inline SVG) ──────────────────────────── -->

        <!-- Telegram -->
        <svg v-if="id==='telegram'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#2AABEE"/>
            <path d="M55.3 115.1l122.8-47.3c5.7-2.1 10.7 1.4 8.9 10L166 181c-1.5 6.7-5.6 8.4-11.4 5.2l-31.4-23.1-15.2 14.6c-1.7 1.7-3.1 3.1-6.2 3.1l2.2-31.6L161 93.4c2.5-2.2-.5-3.4-3.9-1.2l-70.9 44.6-30.5-9.5c-6.7-2.1-6.7-6.6 1.7-9.5z" fill="white"/>
        </svg>

        <!-- WhatsApp -->
        <svg v-else-if="id==='whatsapp'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#25D366"/>
            <path d="M120 44C78.1 44 44 78.1 44 120c0 13.4 3.5 26.5 10.2 38L44 196l39.5-10.4C94 191.7 107 195 120 195c41.9 0 76-34.1 76-76s-34.1-75-76-75zm37.6 107.6c-1.6 4.5-9.4 8.7-13 9.2-3.3.5-7.5.7-12.1-1-2.8-1-6.4-2.4-11.1-4.7-19.5-9.4-32.3-29.6-33.2-31-1-1.4-7.8-10.4-7.8-19.8 0-9.4 4.9-14 6.7-15.9 1.8-1.9 3.9-2.4 5.2-2.4 1.3 0 2.6 0 3.8.1 1.2.1 2.8-.5 4.4 3.4 1.6 3.9 5.5 13.4 6 14.4.5.9.8 2 .2 3.2-.7 1.2-1 2-2 3.1-1 1.1-2.1 2.4-3 3.2-1 .9-2 1.8-1 3.5 1 1.7 4.5 7.4 9.6 12 6.6 5.9 12.2 7.8 13.9 8.7 1.7.9 2.7.7 3.7-.4 1-1.1 4.3-5 5.5-6.8 1.2-1.7 2.3-1.4 3.9-.8 1.6.5 10.2 4.8 11.9 5.7 1.7.9 2.9 1.3 3.3 2 .5.8.5 4.5-1.1 9z" fill="white"/>
        </svg>

        <!-- Google -->
        <svg v-else-if="id==='google'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="white"/>
            <path d="M120 52c18.4 0 34.7 6.6 47 17.3L146.3 90C140.2 84.5 130.6 81 120 81c-22.7 0-41.9 15.3-48.8 36H52.7C60.5 84.3 87.9 52 120 52z" fill="#EA4335"/>
            <path d="M185.4 124c0-4-.4-7.9-1-11.7H120v22.1h37.2c-1.6 8.6-6.5 15.9-13.9 20.8l21.9 17c12.8-11.8 20.2-29.2 20.2-48.2z" fill="#4285F4"/>
            <path d="M71.2 142.3A52 52 0 0 1 68 120a52 52 0 0 1 3.2-22.3L51.8 81.6A79.7 79.7 0 0 0 43.5 120c0 13.5 3.2 26.2 8.3 37.4l19.4-15.1z" fill="#FBBC05"/>
            <path d="M120 188c17.6 0 32.4-5.8 43.2-15.8l-21.9-17c-6 4-13.7 6.4-21.3 6.4-22.7 0-41.9-15.3-48.8-36.3L29.8 141.4C37.7 163.5 77.1 188 120 188z" fill="#34A853"/>
        </svg>

        <!-- Discord -->
        <svg v-else-if="id==='discord'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#5865F2"/>
            <path d="M177.7 66.5A155.8 155.8 0 0 0 143.2 55a1.1 1.1 0 0 0-1.2.6c-1.6 2.9-3.4 6.7-4.7 9.7a143.9 143.9 0 0 0-43.1 0 98 98 0 0 0-4.7-9.7 1.2 1.2 0 0 0-1.2-.6 155.5 155.5 0 0 0-34.5 11.5 1.1 1.1 0 0 0-.5.4C31 109.9 24.9 152 27.7 193.5a1.3 1.3 0 0 0 .5.9c14.5 10.7 28.6 17.2 42.4 21.5a1.2 1.2 0 0 0 1.3-.4 112 112 0 0 0 9.7-15.8 1.2 1.2 0 0 0-.6-1.6 102.4 102.4 0 0 1-14.6-7 1.2 1.2 0 0 1-.1-2c1-.7 2-1.5 2.9-2.2a1.1 1.1 0 0 1 1.2-.2c30.7 14 64 14 94.3 0a1.1 1.1 0 0 1 1.2.2c1 .8 1.9 1.5 2.9 2.2a1.2 1.2 0 0 1-.1 2 95.9 95.9 0 0 1-14.7 7 1.2 1.2 0 0 0-.6 1.6 125.7 125.7 0 0 0 9.7 15.8c.3.4.8.6 1.3.4 13.9-4.3 28-10.8 42.5-21.5a1.2 1.2 0 0 0 .5-.9c3.4-47.1-5.7-88.5-24-123.6a1 1 0 0 0-.5-.4zm-83.6 99.1c-9.3 0-17-8.5-17-19s7.5-19 17-19 17.2 8.5 17 19-7.5 19-17 19zm62.8 0c-9.3 0-17-8.5-17-19s7.5-19 17-19 17.1 8.5 17 19-7.5 19-17 19z" fill="white"/>
        </svg>

        <!-- OpenAI -->
        <svg v-else-if="id==='openai'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#10a37f"/>
            <path d="M178.8 110.3a37.6 37.6 0 0 0-3.2-30.8 38 38 0 0 0-41-18.3A38 38 0 0 0 105.9 50a38 38 0 0 0-36.2 26.3 38 38 0 0 0-25.4 18.4 38.1 38.1 0 0 0 4.7 44.7 37.6 37.6 0 0 0 3.2 30.8 38 38 0 0 0 41 18.3 38 38 0 0 0 28.7 11.2 38 38 0 0 0 36.3-26.4 38 38 0 0 0 25.3-18.4 38.1 38.1 0 0 0-4.7-44.6zm-56.6 79.1a28.2 28.2 0 0 1-18-6.5l.9-.5 29.9-17.3a5 5 0 0 0 2.5-4.3v-42.2l12.6 7.3v35a28.3 28.3 0 0 1-27.9 28.5zm-60-26a28.2 28.2 0 0 1-3.4-19l.9.5 29.9 17.3a4.9 4.9 0 0 0 4.9 0L185 144v14.5a28.3 28.3 0 0 1-45 8.2l-17.8-10.3zm-8.4-65.7a28.2 28.2 0 0 1 14.7-12.4v35.5a5 5 0 0 0 2.5 4.3l42.1 24.3-12.6 7.3-29.8-17.3a28.3 28.3 0 0 1-16.9-41.7zm103.5 24.2-42.1-24.3 12.6-7.3 29.8 17.3a28.3 28.3 0 0 1-4.3 51v-35.4a5 5 0 0 0-2.5-4.3h.5zm12.6-19-.9-.5-29.9-17.3a4.9 4.9 0 0 0-4.9 0l-42.1 24.3V95.7a28.3 28.3 0 0 1 46.5-8.3l17.3 10zm-91.3 30-12.6-7.3V97a28.3 28.3 0 0 1 46.4-21.7l-.9.5-29.9 17.3a5 5 0 0 0-2.5 4.3v42.3-.1zm6.8-14.8 18.7-10.8 18.7 10.8v21.6l-18.7 10.8-18.7-10.8V119z" fill="white"/>
        </svg>

        <!-- TikTok -->
        <svg v-else-if="id==='tiktok'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#010101"/>
            <path d="M169 70.6a40.7 40.7 0 0 1-24.8-22.6h-27v133.3l.1 7.6a24.2 24.2 0 0 1-24.2 21.4 24.2 24.2 0 0 1-24.2-24.2 24.2 24.2 0 0 1 24.2-24.2c2.3 0 4.6.3 6.7 1V134a51.4 51.4 0 0 0-6.7-.4A51.5 51.5 0 0 0 41.6 185 51.5 51.5 0 0 0 93 236.5a51.5 51.5 0 0 0 51.5-51.4V120a97.5 97.5 0 0 0 56.8 18.1v-27a40.7 40.7 0 0 1-32.3-40.5z" fill="#EE1D52"/>
            <path d="M169 70.6a40.7 40.7 0 0 0 32.3 40.5v-27a41 41 0 0 1-24.8-22.6h-34.1V185a24.2 24.2 0 0 1-24.2 24.2A24.2 24.2 0 0 1 94 186.2a24.2 24.2 0 0 1-23.4-6a24.2 24.2 0 0 0 45.5-11.1V48h27.1A40.7 40.7 0 0 0 169 70.6z" fill="#EE1D52"/>
            <path d="M169 44a40.7 40.7 0 0 0 8 26.6A40.7 40.7 0 0 1 169 44zM93 134v-7.4a51.4 51.4 0 0 0-51.4 51.5A51.5 51.5 0 0 0 93 229.5a51.5 51.5 0 0 0 51.5-51.4v-65a97.5 97.5 0 0 0 56.8 18v-27a41 41 0 0 1-7.3-4.5V138a97.5 97.5 0 0 1-56.8-18.1V185A51.5 51.5 0 0 1 86 229.5a51.5 51.5 0 0 1-50-50 51.4 51.4 0 0 1 57-45.5z" fill="#69C9D0"/>
        </svg>

        <!-- Instagram -->
        <svg v-else-if="id==='instagram'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <defs>
                <radialGradient id="ig-grad" cx="30%" cy="107%" r="150%">
                    <stop offset="0%" stop-color="#ffd600"/>
                    <stop offset="50%" stop-color="#ff0100"/>
                    <stop offset="100%" stop-color="#d800b9"/>
                </radialGradient>
                <radialGradient id="ig-grad2" cx="150%" cy="0%" r="200%">
                    <stop offset="0%" stop-color="#00aed8"/>
                    <stop offset="30%" stop-color="#7638fa" stop-opacity=".6"/>
                    <stop offset="100%" stop-color="#7638fa" stop-opacity="0"/>
                </radialGradient>
            </defs>
            <rect width="240" height="240" rx="54" fill="url(#ig-grad)"/>
            <rect width="240" height="240" rx="54" fill="url(#ig-grad2)"/>
            <rect x="46" y="46" width="148" height="148" rx="36" stroke="white" stroke-width="14" fill="none"/>
            <circle cx="120" cy="120" r="36" stroke="white" stroke-width="14" fill="none"/>
            <circle cx="168" cy="74" r="9" fill="white"/>
        </svg>

        <!-- Facebook -->
        <svg v-else-if="id==='facebook'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#1877F2"/>
            <path d="M165 120h-31v85h-31v-85H82V91h21V72c0-26 11-40 38-40 11 0 22 2 24 2v26h-16c-11 0-14 5-14 14v17h29l-4 29h-25v85h-31v-85z" fill="white"/>
        </svg>

        <!-- Twitter / X -->
        <svg v-else-if="id==='twitter'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#000"/>
            <path d="M133 110.9 181.6 55h-11.5L128 103.4 96.4 55H54l51 74.2-51 59.6h11.5L107 135l33.6 53.8H183L133 111zM70 64h18.5l84.1 114h-18.5L70 64z" fill="white"/>
        </svg>

        <!-- Amazon -->
        <svg v-else-if="id==='amazon'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#FF9900"/>
            <path d="M125 130c-26 14-62 20-94 6" stroke="#000" stroke-width="8" stroke-linecap="round" fill="none"/>
            <path d="M150 142c8-3 18-3 22 4-11 10-27 14-43 15" stroke="#000" stroke-width="8" stroke-linecap="round" fill="none"/>
            <text x="70" y="118" font-size="60" font-family="Arial" font-weight="bold" fill="#000">a</text>
        </svg>

        <!-- Microsoft -->
        <svg v-else-if="id==='microsoft'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#f3f3f3"/>
            <rect x="50" y="50" width="62" height="62" fill="#F25022"/>
            <rect x="128" y="50" width="62" height="62" fill="#7FBA00"/>
            <rect x="50" y="128" width="62" height="62" fill="#00A4EF"/>
            <rect x="128" y="128" width="62" height="62" fill="#FFB900"/>
        </svg>

        <!-- Binance -->
        <svg v-else-if="id==='binance'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#F3BA2F"/>
            <path d="M120 52L89 83l12 12 19-19 19 19 12-12zM57 95l12 12 19-19v-1l1-1 12 12-12 12L57 95zM52 120l12 12 12-12-12-12zM57 145l31 31 12-12-19-19-1-1-12-12zM183 95l-31-31-12 12 19 19 1 1 12 12zM188 120l-12 12-12-12 12-12zM183 145l-12 12-12-12 12-12zM133 119l-13-13-13 13 13 13z" fill="#000" fill-rule="evenodd"/>
        </svg>

        <!-- PayPal -->
        <svg v-else-if="id==='paypal'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#003087"/>
            <path d="M157 78c-5-21-24-28-46-28H66L47 175h33l5-30h23c32 0 55-16 59-45 2-10 1-18-7-22zm-13 20c-3 19-17 27-35 27H94l7-42h16c18 0 30 5 27 15z" fill="#009CDE"/>
            <path d="M175 84c5 21-12 37-35 39l-5 2H117l-5 30H80l19-115h49c22 0 36 14 27 44z" fill="white" opacity=".5"/>
        </svg>

        <!-- Netflix -->
        <svg v-else-if="id==='netflix'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#E50914"/>
            <path d="M70 50h28l22 73V50h28v140H121l-23-74v74H70z" fill="white"/>
        </svg>

        <!-- Apple -->
        <svg v-else-if="id==='apple'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#000"/>
            <path d="M155.7 127.4c-.2-20.3 16.6-30.1 17.4-30.6-9.5-13.9-24.2-15.8-29.4-16-12.5-1.3-24.4 7.4-30.7 7.4-6.3 0-16.1-7.2-26.5-7-13.6.2-26.2 8-33.2 20.2-14.2 24.6-3.6 61.1 10.2 81.1 6.8 9.7 14.7 20.7 25.2 20.3 10.1-.4 13.9-6.5 26.2-6.5 12.2 0 15.6 6.5 26.3 6.3 10.9-.2 17.9-9.8 24.6-19.6 7.7-11.2 10.9-22.2 11.1-22.8-.3-.1-21.2-8.1-21.2-32.8zM135.8 64.6c5.6-6.9 9.4-16.4 8.4-26-8.1.3-18 5.5-23.7 12.3-5.2 6-9.8 15.8-8.5 25 9 .7 18.1-4.5 23.8-11.3z" fill="white"/>
        </svg>

        <!-- LinkedIn -->
        <svg v-else-if="id==='linkedin'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#0A66C2"/>
            <path d="M68 94h28v82H68zM82 80a16 16 0 1 0 0-32 16 16 0 0 0 0 32zM148 94h-28v82h28v-42c0-22 28-24 28 0v42h28v-50c0-40-50-38-56-18v-14z" fill="white"/>
        </svg>

        <!-- Steam -->
        <svg v-else-if="id==='steam'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#1B2838"/>
            <path d="M120 44c-40.4 0-73.4 30.4-76.6 69.8l41 17a22 22 0 0 1 12.4-3.8c.4 0 .9 0 1.3.1l18.2-26.4V100c0-17.7 14.3-32 32-32s32 14.3 32 32-14.3 32-32 32h-.7l-26 18.4c0 .4 0 .8.1 1.2A22 22 0 1 1 100 130c0-.3 0-.6.1-.9L63.8 113c7.6 25.3 31.2 43.7 59 43.7a62 62 0 0 0 0-124l-2.8.3zm-27.3 117l-7.8-3.2a16.5 16.5 0 1 0 17 5.7l7.4 3a11.9 11.9 0 1 1-16.6-5.5zm75.3-99a21.3 21.3 0 1 0 0 42.6 21.3 21.3 0 0 0 0-42.6zm0 5.3a16 16 0 1 1 0 32 16 16 0 0 1 0-32z" fill="white"/>
        </svg>

        <!-- Snapchat -->
        <svg v-else-if="id==='snapchat'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#FFFC00"/>
            <path d="M120 44c-22 0-40 18-40 40v14c-6 0-12 4-12 10s6 10 12 10c1 0 2 0 3-.2-2.7 6-7 11-12 14 0 4 8 6 16 8 2 6 8 10 16 10s14-4 16-10c8-2 16-4 16-8-5-3-9.3-8-12-14 1 .2 2 .2 3 .2 6 0 12-4 12-10s-6-10-12-10V84c0-22-18-40-40-40z" fill="#000"/>
        </svg>

        <!-- Coinbase -->
        <svg v-else-if="id==='coinbase'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#0052FF"/>
            <circle cx="120" cy="120" r="68" fill="white"/>
            <path d="M120 67a53 53 0 1 0 0 106 53 53 0 0 0 0-106zm0 92a39 39 0 1 1 0-78 39 39 0 0 1 0 78z" fill="#0052FF"/>
        </svg>

        <!-- Viber -->
        <svg v-else-if="id==='viber'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#7360F2"/>
            <path d="M120 44c-40 0-74 32-74 72a70.4 70.4 0 0 0 40 63.5V200l28-14.7A78.2 78.2 0 0 0 120 187c40 0 74-32 74-71s-34-72-74-72zm36 92a8.1 8.1 0 0 1-8 6 76 76 0 0 1-57-56 8 8 0 0 1 5-9l11-4c3-1 6 0 8 3l8 16c2 3 1 7-2 9l-5 4a52 52 0 0 0 25 25l4-5c2-3 6-4 9-2l16 8c3 2 4 5 3 8l-4 11-13-4z" fill="white"/>
        </svg>

        <!-- Tinder -->
        <svg v-else-if="id==='tinder'" :width="size" :height="size" viewBox="0 0 240 240" fill="none">
            <rect width="240" height="240" rx="54" fill="#FD5068"/>
            <path d="M148 88c-4-30-30-48-30-48s2 34-26 54c-14 10-22 26-22 44a50 50 0 0 0 100 0c0-22-10-38-22-50zm-30 72a22 22 0 0 1-18-34c4-6 10-11 18-13-2 4-2 8 0 12a14 14 0 0 0 28-1c0-4-1-8-3-11 7 3 12 9 14 17a22 22 0 0 1-22 22 22 22 0 0 1-17-8v16z" fill="white"/>
        </svg>

        <!-- Generic colored fallback with initial -->
        <div v-else
            class="w-full h-full flex items-center justify-center font-black select-none"
            :style="`background:${fallbackBg};color:${fallbackText};font-size:${Math.round(size * 0.42)}px`">
            {{ initial }}
        </div>
    </div>
</template>

import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply theme CSS variables from Inertia shared props onto :root
function applyTheme(theme) {
    if (!theme) return;
    const root = document.documentElement;
    if (theme.primary)        root.style.setProperty('--color-primary',   theme.primary);
    if (theme.secondary)      root.style.setProperty('--color-secondary', theme.secondary);
    if (theme.accent)         root.style.setProperty('--color-accent',    theme.accent);
    if (theme.dark_bg)        root.style.setProperty('--color-dark-bg',   theme.dark_bg);
    if (theme.dark_card)      root.style.setProperty('--color-dark-card', theme.dark_card);
    if (theme.glow)           root.style.setProperty('--color-glow',      theme.glow);
    if (theme.glow_intensity !== undefined)
                              root.style.setProperty('--glow-intensity',  theme.glow_intensity);
}

// Re-apply on every Inertia page navigation (covers SPA navigations after initial load)
router.on('navigate', (event) => {
    applyTheme(event.detail?.page?.props?.theme);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Apply theme from initial page props immediately
        applyTheme(props?.initialPage?.props?.theme);

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        app.config.errorHandler = (err, instance, info) => {
            console.error('[Vue] Uncaught error:', err, '\nComponent:', instance, '\nInfo:', info);
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

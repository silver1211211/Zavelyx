const STORAGE_KEY = 'nexahub-theme';

export function getStoredTheme(fallback = 'light') {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') return stored;
    } catch {}

    return fallback;
}

export function getPreferredTheme() {
    return getStoredTheme('light');
}

export function setThemeInstant(theme, persist = true) {
    const resolved = theme === 'light' ? 'light' : 'dark';
    const root = document.documentElement;

    // A theme change touches most of the page. Disable transitions for the two
    // paint frames surrounding the class swap to avoid hundreds of animations.
    root.classList.add('theme-switching');
    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.theme = resolved;
    root.style.colorScheme = resolved;

    if (persist) {
        try { localStorage.setItem(STORAGE_KEY, resolved); } catch {}
    }

    requestAnimationFrame(() => {
        requestAnimationFrame(() => root.classList.remove('theme-switching'));
    });

    return resolved;
}

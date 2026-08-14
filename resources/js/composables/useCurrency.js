import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Module-level shared state — one instance for all components in the tab
const displayCurrency  = ref(null);
let   watchCreated     = false;
let   userHasSelected  = false; // true once the user manually picks a currency this session

function saveCurrencyToServer(code) {
    setTimeout(async () => {
        try {
            await fetch('/settings/currency', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                    ),
                },
                body: JSON.stringify({ currency: code }),
            });
        } catch {
            // Non-critical — localStorage is the fallback for current session
        }
    }, 300);
}

export function useCurrency() {
    const page       = usePage();
    const currencies = computed(() => page.props.currencies ?? []);

    if (!watchCreated) {
        watchCreated = true;
        watch(
            currencies,
            (list) => {
                if (!list || list.length === 0) return;

                // After manual selection, localStorage is authoritative for this session
                if (userHasSelected) {
                    const stored = localStorage.getItem('nexahub-currency');
                    if (stored && list.find(c => c.code === stored)) return;
                }

                // On first load: server preference > localStorage > default currency
                const serverPref = page.props.preferred_currency ?? null;
                const stored     = localStorage.getItem('nexahub-currency');

                const candidate = serverPref || stored;
                const valid = candidate && list.find(c => c.code === candidate && c.is_active !== false);

                if (valid) {
                    displayCurrency.value = candidate;
                    localStorage.setItem('nexahub-currency', candidate);
                } else if (!displayCurrency.value || !list.find(c => c.code === displayCurrency.value)) {
                    displayCurrency.value = list.find(c => c.is_default)?.code ?? list[0]?.code ?? 'USD';
                }
            },
            { immediate: true },
        );
    }

    const current = computed(() =>
        currencies.value.find(c => c.code === displayCurrency.value)
        ?? currencies.value.find(c => c.is_default)
        ?? { code: 'USD', symbol: '$', exchange_rate: 1, is_default: true },
    );

    const symbol = computed(() => current.value?.symbol ?? '$');

    function setCurrency(code) {
        userHasSelected       = true;
        displayCurrency.value = code;
        localStorage.setItem('nexahub-currency', code);
        saveCurrencyToServer(code);
    }

    /**
     * Convert a USD amount to the currently selected currency.
     */
    function convertAmount(usdAmount) {
        const rate = parseFloat(current.value?.exchange_rate ?? 1);
        return parseFloat(usdAmount ?? 0) * rate;
    }

    /**
     * Format a USD amount as a plain number string in the current currency.
     * e.g. "1,600.00" for NGN, "1.00" for USD
     *
     * @param {number} usdAmount
     * @param {number} decimals - minimum decimal places
     */
    function formatAmount(usdAmount, decimals = 2) {
        const converted = convertAmount(usdAmount);
        // For large-unit currencies (NGN, INR, etc.) cap at 2dp;
        // for tiny-value currencies cap depends on magnitude
        const autoMax = converted >= 1 ? 2 : converted >= 0.01 ? 4 : 8;
        const maxDec  = Math.max(decimals, autoMax);
        return converted.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: maxDec,
        });
    }

    /**
     * Format a USD amount as a full money string with the currency symbol.
     * e.g. "₦1,600.00" for NGN, "$1.00" for USD
     *
     * Options:
     *   decimals  – minimum decimal places (default 2)
     *   showCode  – append currency code after value, e.g. "$1.00 USD" (default false)
     *   compact   – use compact notation for large values, e.g. "₦1.6M" (default false)
     *
     * @param {number} usdAmount
     * @param {{ decimals?: number, showCode?: boolean, compact?: boolean }} opts
     */
    function formatMoney(usdAmount, opts = {}) {
        const { decimals = 2, showCode = false, compact = false } = opts;
        const converted = convertAmount(usdAmount);
        const sym       = symbol.value;
        const code      = current.value?.code ?? 'USD';

        let formatted;
        if (compact && Math.abs(converted) >= 1_000_000) {
            formatted = (converted / 1_000_000).toLocaleString('en-US', {
                minimumFractionDigits: 1, maximumFractionDigits: 2,
            }) + 'M';
        } else if (compact && Math.abs(converted) >= 1_000) {
            formatted = (converted / 1_000).toLocaleString('en-US', {
                minimumFractionDigits: 1, maximumFractionDigits: 2,
            }) + 'K';
        } else {
            const autoMax = converted >= 1 ? 2 : converted >= 0.01 ? 4 : 8;
            formatted = converted.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: Math.max(decimals, autoMax),
            });
        }

        return showCode ? `${sym}${formatted} ${code}` : `${sym}${formatted}`;
    }

    return {
        displayCurrency,
        currencies,
        current,
        symbol,
        setCurrency,
        convertAmount,
        formatAmount,
        formatMoney,
    };
}

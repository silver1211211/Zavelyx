<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\PaymentGateways\GatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GatewayController extends Controller
{
    public const SUPPORTED_DRIVERS = [
        'oxapay_invoice' => [
            'name'            => 'OxaPay Invoice (Global)',
            'desc'            => 'Coin-locked invoices — user selects coin/network in your own UI, gets a payment address + QR code. Works globally. Merchant API Key only.',
            'live'            => true,
            'fields'          => ['api_key'],
            'requires_secret' => false,
            'badge'           => 'Global · All Coins',
        ],
        'oxapay' => [
            'name'            => 'OxaPay (Hosted Checkout)',
            'desc'            => 'Redirects user to OxaPay hosted checkout page where they pick their coin. Limited country support. Merchant API Key only.',
            'live'            => true,
            'fields'          => ['api_key'],
            'requires_secret' => false,
            'badge'           => 'Hosted · Limited',
        ],
        'paymento' => [
            'name'            => 'Paymento',
            'desc'            => 'Crypto payments via Paymento hosted checkout — user selects coin/network on their site',
            'live'            => true,
            'fields'          => ['api_key', 'secret_key'],
            'requires_secret' => true,
        ],
        'coinbase' => [
            'name'   => 'Coinbase Commerce',
            'desc'   => 'Accept crypto via Coinbase Commerce hosted checkout',
            'live'   => false,
        ],
        'binancepay' => [
            'name'   => 'Binance Pay',
            'desc'   => 'Pay with Binance account or crypto',
            'live'   => false,
        ],
        'stripe' => [
            'name'   => 'Stripe',
            'desc'   => 'Credit/debit card payments',
            'live'   => false,
        ],
        'paystack' => [
            'name'   => 'Paystack',
            'desc'   => 'Cards, bank transfers, USSD — Africa-focused',
            'live'   => false,
        ],
        'flutterwave' => [
            'name'   => 'Flutterwave',
            'desc'   => 'Multi-channel payments across Africa',
            'live'   => false,
        ],
        'cryptomus' => [
            'name'   => 'CryptoMus',
            'desc'   => 'Multi-coin crypto payment processor',
            'live'   => false,
        ],
    ];

    public function index(): Response
    {
        $gateways = PaymentGateway::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($g) => [
                'id'               => $g->id,
                'name'             => $g->name,
                'driver'           => $g->driver,
                'is_active'        => $g->is_active,
                'is_default'       => $g->is_default,
                'fee_percent'      => (float) $g->fee_percent,
                'min_amount'       => (float) $g->min_amount,
                'max_amount'       => (float) $g->max_amount,
                'sort_order'       => $g->sort_order,
                'extra_config'     => $g->extra_config ?? [],
                'has_api_key'      => !empty($g->api_key),
                'has_secret_key'   => !empty($g->ipn_secret),
                'requires_secret'  => (self::SUPPORTED_DRIVERS[$g->driver]['requires_secret'] ?? true),
                'created_at'       => $g->created_at->toISOString(),
            ]);

        // Build the URL reference block shown to admin
        $callbackUrls = [
            'oxapay_invoice' => [
                'ipn'     => route('oxapay_invoice.webhook'),
                'success' => url('/deposit/invoice/{reference}'),
            ],
            'oxapay' => [
                'ipn'     => route('oxapay.webhook'),
                'success' => route('payments.success'),
            ],
            'paymento' => [
                'ipn'     => route('paymento.ipn'),
                'success' => route('payments.success'),
                'cancel'  => route('payments.cancel'),
            ],
        ];

        return Inertia::render('Admin/Gateways/Index', [
            'gateways'        => $gateways,
            'supportedDrivers'=> self::SUPPORTED_DRIVERS,
            'callbackUrls'    => $callbackUrls,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver'      => ['required', 'string', 'in:' . implode(',', array_keys(self::SUPPORTED_DRIVERS))],
            'name'        => ['required', 'string', 'max:100'],
            'api_key'     => ['nullable', 'string', 'max:500'],
            'secret_key'  => ['nullable', 'string', 'max:500'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_amount'  => ['required', 'numeric', 'min:0.01'],
            'max_amount'  => ['required', 'numeric', 'min:1'],
            'sandbox'     => ['boolean'],
        ]);

        if (PaymentGateway::where('driver', $validated['driver'])->exists()) {
            return back()->with('error', 'A gateway with this driver already exists.');
        }

        // oxapay_invoice is the primary gateway (sort 0), oxapay is secondary (sort 1)
        $sortOrder = match ($validated['driver']) {
            'oxapay_invoice' => 0,
            'oxapay'         => 1,
            default          => (PaymentGateway::max('sort_order') ?? 1) + 1,
        };

        $gateway = PaymentGateway::create([
            'name'         => $validated['name'],
            'driver'       => $validated['driver'],
            'is_active'    => false,
            'is_default'   => false,
            'fee_percent'  => $validated['fee_percent'],
            'min_amount'   => $validated['min_amount'],
            'max_amount'   => $validated['max_amount'],
            'extra_config' => ['sandbox' => (bool) ($validated['sandbox'] ?? false)],
            'sort_order'   => $sortOrder,
        ]);

        if (!empty($validated['api_key'])) {
            $gateway->setApiKeyEncrypted($validated['api_key']);
        }

        if (!empty($validated['secret_key'])) {
            $gateway->setIpnSecretEncrypted($validated['secret_key']);
        }

        return back()->with('success', "{$gateway->name} added successfully. Configure it and enable when ready.");
    }

    public function update(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'api_key'     => ['nullable', 'string', 'max:500'],
            'secret_key'  => ['nullable', 'string', 'max:500'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_amount'  => ['required', 'numeric', 'min:0.01'],
            'max_amount'  => ['required', 'numeric', 'min:1'],
            'sandbox'     => ['boolean'],
        ]);

        $gateway->update([
            'name'         => $validated['name'],
            'fee_percent'  => $validated['fee_percent'],
            'min_amount'   => $validated['min_amount'],
            'max_amount'   => $validated['max_amount'],
            'extra_config' => array_merge($gateway->extra_config ?? [], [
                'sandbox' => (bool) ($validated['sandbox'] ?? false),
            ]),
        ]);

        if (!empty($validated['api_key'])) {
            $gateway->setApiKeyEncrypted($validated['api_key']);
        }

        if (!empty($validated['secret_key'])) {
            $gateway->setIpnSecretEncrypted($validated['secret_key']);
        }

        return back()->with('success', "{$gateway->name} updated.");
    }

    public function destroy(PaymentGateway $gateway): RedirectResponse
    {
        $name = $gateway->name;
        $gateway->delete();
        return back()->with('success', "{$name} removed.");
    }

    public function toggle(PaymentGateway $gateway): RedirectResponse
    {
        if (!$gateway->is_active && !$gateway->isConfigured()) {
            return back()->with('error', 'Cannot enable: API key is required first.');
        }

        $gateway->update(['is_active' => !$gateway->is_active]);
        $state = $gateway->fresh()->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "{$gateway->name} {$state}.");
    }

    public function setDefault(PaymentGateway $gateway): RedirectResponse
    {
        PaymentGateway::where('id', '!=', $gateway->id)->update(['is_default' => false]);
        $gateway->update(['is_default' => true]);

        return back()->with('success', "{$gateway->name} is now the default gateway.");
    }

    public function test(PaymentGateway $gateway): RedirectResponse
    {
        if (!$gateway->isConfigured()) {
            return back()->with('error', 'Gateway has no API key configured.');
        }

        try {
            $driver = GatewayManager::make($gateway);
            $result = $driver->getPaymentStatus('test-ping-' . time());

            // Any response (even 404 for unknown ID) means the API key is accepted
            return back()->with('success', "Connection to {$gateway->name} OK — API key is valid.");
        } catch (\Throwable $e) {
            Log::error('[Admin] Gateway test failed', [
                'gateway' => $gateway->driver,
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }
    }
}

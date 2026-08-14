<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Services\PaymentGateways\GatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DepositController extends Controller
{
    // IPN route names per driver (used to verify a handler exists)
    private const IPN_ROUTES = [
        'paymento' => 'paymento.ipn',
        'oxapay'   => 'oxapay.webhook',
    ];

    // IPN URL paths matched to the routes above (avoids APP_URL mismatch when using tunnels)
    private const IPN_PATHS = [
        'paymento' => '/api/payments/paymento/ipn',
        'oxapay'   => '/api/payments/oxapay/webhook',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $gateways = PaymentGateway::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($g) => [
                'id'          => $g->id,
                'name'        => $g->name,
                'driver'      => $g->driver,
                'min_amount'  => (float) $g->min_amount,
                'max_amount'  => (float) $g->max_amount,
                'fee_percent' => (float) $g->fee_percent,
                'configured'  => $g->isConfigured(),
            ]);

        $deposits = PaymentInvoice::where('user_id', $user->id)
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($inv) => self::formatInvoice($inv));

        $hasPending = $deposits->whereIn('status', ['waiting', 'confirming', 'confirmed', 'sending'])->isNotEmpty();

        return Inertia::render('Deposit/Index', [
            'gateways'    => $gateways,
            'deposits'    => $deposits,
            'hasPending'  => $hasPending,
            'maintenance' => Setting::get('payment.deposit.maintenance', '0') === '1',
        ]);
    }

    public function create(Request $request): mixed
    {
        if (Setting::get('payment.deposit.maintenance', '0') === '1') {
            return back()->withErrors(['amount' => 'Deposits are temporarily suspended for maintenance.']);
        }

        $validated = $request->validate([
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'gateway_id' => ['required', 'integer'],
        ]);

        $gateway = PaymentGateway::active()->find($validated['gateway_id']);

        if (!$gateway) {
            return back()->withErrors(['gateway_id' => 'Selected payment method is not available.']);
        }

        $amount = round((float) $validated['amount'], 2);

        if ($amount < (float) $gateway->min_amount) {
            return back()->withErrors(['amount' => "Minimum deposit is \${$gateway->min_amount}."]);
        }

        if ($amount > (float) $gateway->max_amount) {
            return back()->withErrors(['amount' => 'Maximum deposit is $' . number_format($gateway->max_amount, 0) . '.']);
        }

        if (!$gateway->isConfigured()) {
            return back()->withErrors(['gateway_id' => 'Payment gateway is not configured yet. Contact support.']);
        }

        // Resolve IPN route for this driver
        $ipnRouteName = self::IPN_ROUTES[$gateway->driver] ?? null;
        if (!$ipnRouteName) {
            return back()->withErrors(['gateway_id' => 'This gateway has no IPN endpoint registered.']);
        }

        try {
            $driver = GatewayManager::make($gateway);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['gateway_id' => 'Gateway driver not supported: ' . $e->getMessage()]);
        }

        $reference = Str::uuid()->toString();

        // Some gateways require HTTPS callback URLs. When running locally,
        // set PAYMENT_CALLBACK_BASE (or legacy PAYMENTO_CALLBACK_BASE) to an
        // HTTPS tunnel URL (e.g. ngrok) so the gateway accepts the request.
        $callbackBase = rtrim(
            env('PAYMENT_CALLBACK_BASE', env('PAYMENTO_CALLBACK_BASE', config('app.url'))),
            '/'
        );
        $httpsUrl = fn (string $path) => $callbackBase . $path;

        $ipnPath = self::IPN_PATHS[$gateway->driver] ?? '';

        $result = $driver->createInvoice(
            amount:      $amount,
            currency:    'USD',
            reference:   $reference,
            description: 'Wallet deposit',
            successUrl:  $httpsUrl('/payments/success'),
            cancelUrl:   $httpsUrl('/payments/cancel'),
            ipnUrl:      $httpsUrl($ipnPath),
        );

        if (!$result['success']) {
            return back()->withErrors(['amount' => $result['message'] ?? 'Payment gateway error. Please try again.']);
        }

        if (empty($result['invoice_url'])) {
            return back()->withErrors(['amount' => 'Could not retrieve payment URL. Please try again.']);
        }

        // Save invoice BEFORE redirecting so we have a record
        PaymentInvoice::create([
            'reference'          => $reference,
            'user_id'            => $request->user()->id,
            'gateway'            => $gateway->driver,
            'gateway_invoice_id' => $result['gateway_invoice_id'] ?? '',
            'price_amount'       => $amount,
            'price_currency'     => 'usd',
            'status'             => 'waiting',
            'payment_url'        => $result['invoice_url'],
        ]);

        // Inertia::location() performs a hard browser redirect to external URLs
        return Inertia::location($result['invoice_url']);
    }

    private static function formatInvoice(PaymentInvoice $inv): array
    {
        return [
            'id'                 => $inv->id,
            'reference'          => $inv->reference,
            'price_amount'       => (float) $inv->price_amount,
            'price_currency'     => strtoupper($inv->price_currency ?? 'USD'),
            'pay_currency'       => $inv->pay_currency ? strtoupper($inv->pay_currency) : null,
            'actually_paid'      => $inv->actually_paid ? (float) $inv->actually_paid : null,
            'gateway'            => $inv->gateway,
            'gateway_payment_id' => $inv->gateway_payment_id,
            'status'             => $inv->status,
            'status_label'       => PaymentInvoice::statusLabel($inv->status),
            'payment_url'        => $inv->payment_url,
            'blockchain_hash'    => $inv->blockchain_hash,
            'network'            => $inv->network,
            'credited_at'        => $inv->credited_at?->toISOString(),
            'created_at'         => $inv->created_at->toISOString(),
            'is_credited'        => $inv->isCredited(),
            'is_finished'        => $inv->isFinished(),
        ];
    }
}

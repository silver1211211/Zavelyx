<?php

namespace App\Http\Controllers;

use App\Models\DepositLog;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Services\PaymentGateways\GatewayManager;
use App\Services\PaymentGateways\OxaPayInvoiceGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles the coin-locked OxaPay invoice flow.
 *
 * Flow:
 *  1. User chooses "Invoice" gateway on /deposit, selects coin/network, enters amount.
 *  2. POST /deposit/invoice  → creates OxaPay invoice with toCurrency set.
 *  3. Redirect to /deposit/invoice/{reference} — our own payment page.
 *  4. Page shows QR code, pay_address, exact pay_amount, countdown timer.
 *  5. Frontend polls /api/deposits/{reference}/status every 5s until terminal.
 *  6. ProcessDepositJob credits the wallet on "Paid" webhook or poll detection.
 */
class InvoiceDepositController extends Controller
{
    private const IPN_PATH    = '/api/payments/oxapay-invoice/webhook';
    private const IPN_ROUTE   = 'oxapay_invoice.webhook';

    // ── Create invoice ────────────────────────────────────────────────────────

    public function store(Request $request): mixed
    {
        if (Setting::get('payment.deposit.maintenance', '0') === '1') {
            return back()->withErrors(['amount' => 'Deposits are temporarily suspended for maintenance.']);
        }

        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'min:1', 'max:10000'],
            'gateway_id'  => ['required', 'integer'],
            'to_currency' => ['required', 'string', 'in:USDT_BEP20'],
        ]);

        $gateway = PaymentGateway::active()
            ->where('driver', 'oxapay_invoice')
            ->find($validated['gateway_id']);

        if (!$gateway || !$gateway->isConfigured()) {
            return $this->errorResponse($request, 'gateway_id', 'Crypto deposit gateway is not available.');
        }

        $amount = round((float) $validated['amount'], 2);

        $reference = Str::uuid()->toString();

        $callbackBase = rtrim(
            env('PAYMENT_CALLBACK_BASE', env('PAYMENTO_CALLBACK_BASE', config('app.url'))),
            '/'
        );

        try {
            /** @var OxaPayInvoiceGateway $driver */
            $driver = GatewayManager::make($gateway);

            $result = $driver->createCoinInvoice(
                amount:      $amount,
                currency:    'USD',
                toCurrency:  strtoupper($validated['to_currency']),
                reference:   $reference,
                description: 'Wallet deposit',
                returnUrl:   $callbackBase . '/deposit/invoice/' . $reference,
                ipnUrl:      $callbackBase . self::IPN_PATH,
                email:       $request->user()->email ?? '',
            );
        } catch (\Throwable $e) {
            Log::error('[InvoiceDeposit] Driver exception', ['error' => $e->getMessage()]);
            return $this->errorResponse($request, 'amount', 'Payment gateway error. Please try again.');
        }

        if (!$result['success']) {
            return $this->errorResponse($request, 'amount', $result['message'] ?? 'Checkout creation failed. Please try again.');
        }

        // Determine fee amounts for record
        $feePercent  = (float) $gateway->fee_percent;
        $netAmount   = $feePercent > 0
            ? round($amount * (1 - $feePercent / 100), 8)
            : $amount;

        $gatewayPayload = [];
        if (!empty($result['memo'])) {
            $gatewayPayload['memo'] = $result['memo'];
        }
        if (!empty($result['raw_response']) && is_array($result['raw_response'])) {
            $gatewayPayload['raw_response'] = $result['raw_response'];
        }

        $invoice = PaymentInvoice::create([
            'reference'          => $reference,
            'user_id'            => $request->user()->id,
            'gateway'            => 'oxapay_invoice',
            'gateway_type'       => 'invoice',
            'gateway_invoice_id' => $result['track_id'] ?? '',
            'price_amount'       => $amount,
            'price_currency'     => 'usd',
            'pay_currency'       => $result['pay_currency']  ? strtoupper($result['pay_currency'])  : null,
            'network'            => $result['network']       ?? null,
            'pay_amount'         => $result['pay_amount']    ?? null,
            'pay_address'        => $result['pay_address']   ?? null,
            'payment_url'        => $result['invoice_url']   ?? null,
            'qr_code_url'        => $result['qr_code_url']   ?? null,
            'expires_at'         => $result['expires_at']    ?? null,
            'status'             => 'waiting',
            'ip_address'         => $request->ip(),
            'gateway_payload'    => $gatewayPayload ?: null,
        ]);

        DepositLog::record($invoice->id, 'invoice_created',
            "Coin invoice created: {$result['pay_currency']} on {$result['network']}",
            [
                'track_id'    => $result['track_id'] ?? '',
                'to_currency' => $validated['to_currency'],
                'pay_address' => $result['pay_address'] ?? '',
            ],
        );

        Log::info('[InvoiceDeposit] Invoice created', [
            'reference'  => $reference,
            'user_id'    => $request->user()->id,
            'amount'     => $amount,
            'currency'   => $validated['to_currency'],
            'track_id'   => $result['track_id'] ?? '',
        ]);

        $safe = [
            'reference'    => $invoice->reference,
            'amount'       => (float) $invoice->price_amount,
            'pay_amount'   => $invoice->pay_amount ? (float) $invoice->pay_amount : null,
            'pay_currency' => $invoice->pay_currency ? strtoupper($invoice->pay_currency) : null,
            'network'      => $invoice->network,
            'address'      => $invoice->pay_address,
            'qr_code'      => $invoice->qr_code_url,
            'track_id'     => $invoice->gateway_invoice_id,
            'expires_at'   => $invoice->expires_at?->toISOString(),
            'status'       => $invoice->status,
            'status_label' => PaymentInvoice::statusLabel($invoice->status),
        ];

        if ($request->expectsJson()) {
            return response()->json(['payment' => $safe]);
        }

        return redirect()->route('deposit.invoice.pay', $reference);
    }

    // ── Show payment page ─────────────────────────────────────────────────────

    public function show(Request $request, string $reference): Response|RedirectResponse
    {
        $invoice = PaymentInvoice::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->where('gateway', 'oxapay_invoice')
            ->first();

        if (!$invoice) {
            return redirect()->route('deposit.index')
                ->with('error', 'Invoice not found.');
        }

        return Inertia::render('Deposit/InvoicePay', [
            'invoice' => self::formatInvoice($invoice),
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    public static function formatInvoice(PaymentInvoice $inv): array
    {
        return [
            'id'              => $inv->id,
            'reference'       => $inv->reference,
            'price_amount'    => (float) $inv->price_amount,
            'price_currency'  => strtoupper($inv->price_currency ?? 'USD'),
            'pay_address'     => $inv->pay_address,
            'pay_amount'      => $inv->pay_amount  ? (float) $inv->pay_amount  : null,
            'pay_currency'    => $inv->pay_currency ? strtoupper($inv->pay_currency) : null,
            'network'         => $inv->network,
            'memo'            => $inv->gateway_payload['memo'] ?? null,
            'qr_code_url'     => $inv->qr_code_url,
            'payment_url'     => $inv->payment_url,
            'expires_at'      => $inv->expires_at?->toISOString(),
            'status'          => $inv->status,
            'status_label'    => PaymentInvoice::statusLabel($inv->status),
            'is_credited'     => $inv->isCredited(),
            'is_finished'     => $inv->isFinished(),
            'is_terminal'     => $inv->isTerminal(),
            'blockchain_hash' => $inv->blockchain_hash,
            'credited_at'     => $inv->credited_at?->toISOString(),
            'created_at'      => $inv->created_at->toISOString(),
        ];
    }

    private function errorResponse(Request $request, string $field, string $message): mixed
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => [$field => $message]], 422);
        }

        return back()->withErrors([$field => $message]);
    }
}

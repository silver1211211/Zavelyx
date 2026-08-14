<?php

namespace App\Http\Controllers;

use App\Models\PaymentInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function success(Request $request): Response
    {
        $user = $request->user();

        // Try to find the most recent completed/pending invoice
        $invoice = PaymentInvoice::where('user_id', $user->id)
            ->whereIn('status', ['finished', 'confirming', 'confirmed', 'sending', 'waiting'])
            ->latest()
            ->first();

        return Inertia::render('Payments/Success', [
            'invoice' => $invoice ? [
                'reference'    => $invoice->reference,
                'price_amount' => (float) $invoice->price_amount,
                'pay_currency' => $invoice->pay_currency,
                'status'       => $invoice->status,
                'status_label' => PaymentInvoice::statusLabel($invoice->status),
                'credited_at'  => $invoice->credited_at?->toISOString(),
                'created_at'   => $invoice->created_at->toISOString(),
            ] : null,
        ]);
    }

    public function cancel(Request $request): Response
    {
        return Inertia::render('Payments/Cancel');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TransactionController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $query = $user->transactions()->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->paginate(25)->withQueryString();

        $summary = [
            'total_in'  => (float) $user->transactions()
                ->whereIn('type', ['deposit', 'refund', 'bonus', 'admin_credit'])
                ->where('status', 'completed')
                ->sum('amount'),
            'total_out' => (float) $user->transactions()
                ->whereIn('type', ['order_debit', 'withdrawal', 'fee'])
                ->sum('amount'),
        ];
        $summary['net'] = round($summary['total_in'] - $summary['total_out'], 8);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters'      => $request->only(['type', 'status', 'search', 'date_from', 'date_to']),
            'summary'      => $summary,
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user  = $request->user();
        $query = $user->transactions()->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->limit(5000)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions-' . now()->format('Y-m-d') . '.csv"',
        ];

        return Response::stream(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Type', 'Description', 'Amount', 'Balance Before', 'Balance After', 'Status', 'Date']);

            foreach ($transactions as $tx) {
                fputcsv($out, [
                    $tx->reference,
                    $tx->type,
                    $tx->description,
                    number_format((float) $tx->amount, 8, '.', ''),
                    number_format((float) $tx->balance_before, 8, '.', ''),
                    number_format((float) $tx->balance_after, 8, '.', ''),
                    $tx->status,
                    $tx->created_at->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}

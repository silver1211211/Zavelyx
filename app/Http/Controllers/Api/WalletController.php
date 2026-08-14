<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet()->firstOrFail();

        return response()->json([
            'data' => [
                'balance' => (float) $wallet->balance,
                'ledger_balance' => (float) $wallet->ledger_balance,
                'currency' => $wallet->currency,
            ],
        ]);
    }
}

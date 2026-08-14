<?php

namespace App\Services\Wallets;

use App\DTOs\WalletTransactionData;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    public function __construct(
        private readonly WalletRepository $wallets,
        private readonly TransactionRepository $transactions,
    ) {
    }

    public function createForUser(User $user): Wallet
    {
        return $this->wallets->createForUser($user);
    }

    public function credit(User $user, WalletTransactionData $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $wallet = $this->wallets->lockForUser($user);
            $before = (float) $wallet->balance;
            $after = $before + $data->amount;

            $wallet->forceFill([
                'balance' => $after,
                'ledger_balance' => $after,
            ])->save();

            return $this->record($user, $wallet, $data, $before, $after);
        });
    }

    public function debit(User $user, WalletTransactionData $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $wallet = $this->wallets->lockForUser($user);
            $before = (float) $wallet->balance;

            if ($before < $data->amount) {
                throw new RuntimeException('Insufficient wallet balance.');
            }

            $after = $before - $data->amount;

            $wallet->forceFill([
                'balance' => $after,
                'ledger_balance' => $after,
            ])->save();

            return $this->record($user, $wallet, $data, $before, $after);
        });
    }

    private function record(User $user, Wallet $wallet, WalletTransactionData $data, float $before, float $after): Transaction
    {
        return $this->transactions->create([
            'reference' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'order_id' => $data->order?->id,
            'type' => $data->type,
            'amount' => $data->amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => $data->status,
            'description' => $data->description,
            'metadata' => $data->metadata,
        ]);
    }
}

<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;

class WalletRepository
{
    public function createForUser(User $user, string $currency = 'NGN'): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => $currency, 'balance' => 0, 'ledger_balance' => 0, 'is_active' => true],
        );
    }

    public function lockForUser(User $user): Wallet
    {
        return Wallet::query()
            ->whereBelongsTo($user)
            ->lockForUpdate()
            ->firstOrFail();
    }
}

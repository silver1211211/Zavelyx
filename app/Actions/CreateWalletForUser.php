<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallets\WalletService;

class CreateWalletForUser
{
    public function __construct(private readonly WalletService $wallets)
    {
    }

    public function handle(User $user): Wallet
    {
        return $this->wallets->createForUser($user);
    }
}

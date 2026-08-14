<?php

namespace App\Services\Payments;

use App\Models\User;

class VirtualAccountService
{
    public function placeholderFor(User $user): array
    {
        return [
            'status' => 'pending_provider',
            'account_name' => $user->name,
            'message' => 'Virtual account funding is reserved for the payment gateway phase.',
        ];
    }
}

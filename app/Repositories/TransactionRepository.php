<?php

namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    public function create(array $attributes): Transaction
    {
        return Transaction::create($attributes);
    }
}

<?php

namespace App\DTOs;

use App\Models\Order;

readonly class WalletTransactionData
{
    public function __construct(
        public string $type,
        public float $amount,
        public ?string $description = null,
        public ?Order $order = null,
        public array $metadata = [],
        public string $status = 'successful',
    ) {
    }
}

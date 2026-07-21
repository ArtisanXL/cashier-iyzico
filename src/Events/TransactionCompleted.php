<?php

namespace ArtisanXL\CashierIyzico\Events;

use ArtisanXL\CashierIyzico\Transaction;

class TransactionCompleted
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly Transaction $transaction, public readonly array $payload) {}
}

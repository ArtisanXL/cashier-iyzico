<?php

namespace ArtisanXL\CashierIyzico\Events;

use ArtisanXL\CashierIyzico\Subscription;

class SubscriptionCreated
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly Subscription $subscription, public readonly array $payload) {}
}

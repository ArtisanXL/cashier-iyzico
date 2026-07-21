<?php

namespace ArtisanXL\CashierIyzico\Events;

use ArtisanXL\CashierIyzico\Subscription;

class SubscriptionUpdated
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly Subscription $subscription, public readonly array $payload) {}
}

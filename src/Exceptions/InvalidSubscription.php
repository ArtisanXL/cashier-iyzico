<?php

namespace ArtisanXL\CashierIyzico\Exceptions;

use ArtisanXL\CashierIyzico\Subscription;

class InvalidSubscription extends IyzicoException
{
    public static function alreadyCanceled(Subscription $subscription): self
    {
        return new self("Subscription [{$subscription->iyzico_id}] is already canceled.");
    }

    public static function ended(Subscription $subscription): self
    {
        return new self("Subscription [{$subscription->iyzico_id}] has ended and can no longer be swapped.");
    }

    public static function notCanceled(Subscription $subscription): self
    {
        return new self("Subscription [{$subscription->iyzico_id}] is not canceled, so it cannot be resumed.");
    }
}

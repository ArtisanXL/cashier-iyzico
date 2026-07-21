<?php

namespace ArtisanXL\CashierIyzico\Concerns;

use ArtisanXL\CashierIyzico\Subscription;
use ArtisanXL\CashierIyzico\SubscriptionBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait ManagesSubscriptions
{
    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'billable')->orderByDesc('created_at');
    }

    public function newSubscription(string $type, string $product, string $pricingPlan): SubscriptionBuilder
    {
        return new SubscriptionBuilder($this, $type, $product, $pricingPlan);
    }

    public function subscription(string $type = 'default'): ?Subscription
    {
        return $this->subscriptions()->where('type', $type)->first();
    }

    public function subscribed(string $type = 'default'): bool
    {
        return (bool) $this->subscription($type)?->valid();
    }
}

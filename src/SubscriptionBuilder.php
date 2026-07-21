<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Fluent builder for creating a new Iyzico subscription and persisting the
 * resulting local Subscription row.
 */
final class SubscriptionBuilder
{
    private int $quantity = 1;

    private ?int $trialDays = null;

    public function __construct(
        private readonly Model $billable,
        private readonly string $type,
        private readonly string $product,
        private readonly string $pricingPlan,
    ) {}

    public function quantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function trialDays(int $trialDays): self
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $paymentCard
     */
    public function create(Buyer $buyer, Address $address, array $paymentCard = []): Subscription
    {
        $response = $this->gateway()->createSubscription($this->pricingPlan, $buyer, $address, $paymentCard);

        $subscription = new Subscription([
            'type' => $this->type,
            'iyzico_id' => (string) $response->getReferenceCode(),
            'product_id' => $this->product,
            'pricing_plan_id' => $this->pricingPlan,
            'status' => (string) $response->getSubscriptionStatus(),
            'quantity' => $this->quantity,
            'trial_ends_at' => $this->trialDays !== null
                ? $this->billable->freshTimestamp()->addDays($this->trialDays)
                : null,
        ]);

        $subscription->billable()->associate($this->billable);
        $subscription->save();

        $subscription->items()->create([
            'iyzico_id' => (string) $response->getReferenceCode(),
            'product_id' => $this->product,
            'pricing_plan_id' => $this->pricingPlan,
            'quantity' => $this->quantity,
        ]);

        return $subscription;
    }

    private function gateway(): IyzicoGatewayContract
    {
        return Cashier::gateway();
    }
}

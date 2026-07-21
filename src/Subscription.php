<?php

namespace ArtisanXL\CashierIyzico;

use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $iyzico_id
 * @property string $product_id
 * @property string $pricing_plan_id
 * @property string $status
 * @property int $quantity
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $paused_at
 * @property Carbon|null $ends_at
 * @property-read Model $billable
 */
class Subscription extends Model
{
    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SubscriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    public function active(): bool
    {
        return (is_null($this->ends_at) || $this->onGracePeriod()) && ! $this->paused();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function paused(): bool
    {
        return $this->paused_at !== null;
    }

    public function canceled(): bool
    {
        return $this->ends_at !== null;
    }

    public function onGracePeriod(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isFuture();
    }

    public function ended(): bool
    {
        return $this->canceled() && ! $this->onGracePeriod();
    }

    /**
     * Swap the subscription to a new pricing plan via the gateway.
     */
    public function swap(string $pricingPlan, ?string $product = null): static
    {
        $result = $this->gateway()->upgradeSubscription($this->iyzico_id, $pricingPlan);

        $this->fill([
            'pricing_plan_id' => $pricingPlan,
            'product_id' => $product ?? $this->product_id,
            'status' => (string) $result->getSubscriptionStatus(),
        ])->save();

        return $this;
    }

    public function cancel(): static
    {
        $this->gateway()->cancelSubscription($this->iyzico_id);

        $this->fill([
            'status' => 'canceled',
            'ends_at' => $this->freshTimestamp(),
        ])->save();

        return $this;
    }

    public function resume(): static
    {
        $this->gateway()->activateSubscription($this->iyzico_id);

        $this->fill([
            'status' => 'active',
            'ends_at' => null,
            'paused_at' => null,
        ])->save();

        return $this;
    }

    protected function gateway(): IyzicoGatewayContract
    {
        return Cashier::gateway();
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'trial_ends_at' => 'datetime',
            'paused_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}

<?php

namespace ArtisanXL\CashierIyzico\Concerns;

use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Illuminate\Database\Eloquent\Model;
use Iyzipay\Model\Subscription\SubscriptionCardUpdate;

/**
 * @mixin Model
 */
trait ManagesPaymentMethods
{
    abstract public function gateway(): IyzicoGatewayContract;

    abstract public function iyzicoIdOrFail(): string;

    /**
     * Initialize a card-on-file update. Iyzico returns a hosted checkout form
     * (token + html content) the customer completes to register the new card.
     */
    public function updateIyzicoCard(string $callbackUrl): SubscriptionCardUpdate
    {
        return $this->gateway()->updateCard($this->iyzicoIdOrFail(), $callbackUrl);
    }

    public function updatePaymentMethodInfo(?string $type, ?string $lastFour): static
    {
        $this->forceFill([
            'pm_type' => $type,
            'pm_last_four' => $lastFour,
        ])->save();

        return $this;
    }

    public function hasDefaultPaymentMethod(): bool
    {
        return $this->getAttribute('pm_type') !== null;
    }

    public function pmType(): ?string
    {
        $type = $this->getAttribute('pm_type');

        return $type === null ? null : (string) $type;
    }

    public function pmLastFour(): ?string
    {
        $lastFour = $this->getAttribute('pm_last_four');

        return $lastFour === null ? null : (string) $lastFour;
    }
}

<?php

namespace ArtisanXL\CashierIyzico\Concerns;

use ArtisanXL\CashierIyzico\Address;
use ArtisanXL\CashierIyzico\Buyer;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @mixin Model
 */
trait ManagesCustomer
{
    abstract public function gateway(): IyzicoGatewayContract;

    public function iyzicoId(): ?string
    {
        $id = $this->getAttribute('iyzico_customer_id');

        return $id === null ? null : (string) $id;
    }

    public function hasIyzicoId(): bool
    {
        return $this->iyzicoId() !== null;
    }

    public function iyzicoIdOrFail(): string
    {
        $id = $this->iyzicoId();

        if ($id === null) {
            // A dedicated InvalidCustomer exception ships in a later step (Step 8).
            throw new RuntimeException('The billable model does not have an Iyzico customer id.');
        }

        return $id;
    }

    public function createAsIyzicoCustomer(Buyer $buyer, Address $address): static
    {
        $customer = $this->gateway()->createCustomer($buyer, $address);

        $this->forceFill(['iyzico_customer_id' => (string) $customer->getReferenceCode()])->save();

        return $this;
    }

    public function updateIyzicoCustomer(Buyer $buyer, Address $address): static
    {
        $this->gateway()->updateCustomer($this->iyzicoIdOrFail(), $buyer, $address);

        return $this;
    }

    public function createOrGetIyzicoCustomer(Buyer $buyer, Address $address): static
    {
        if (! $this->hasIyzicoId()) {
            return $this->createAsIyzicoCustomer($buyer, $address);
        }

        return $this;
    }
}

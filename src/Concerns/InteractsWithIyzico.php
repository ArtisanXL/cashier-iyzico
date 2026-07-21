<?php

namespace ArtisanXL\CashierIyzico\Concerns;

use ArtisanXL\CashierIyzico\Cashier;
use ArtisanXL\CashierIyzico\Contracts\IyzicoGatewayContract;
use ArtisanXL\CashierIyzico\Gateway\IyzicoGateway;
use Iyzipay\Options;

trait InteractsWithIyzico
{
    /**
     * Resolve the injectable gateway. Tests bind a fake against the contract.
     */
    public function gateway(): IyzicoGatewayContract
    {
        return Cashier::gateway();
    }

    /**
     * Build a per-request Iyzico Options object from the package config.
     */
    public function iyzicoOptions(): Options
    {
        return IyzicoGateway::options();
    }
}
